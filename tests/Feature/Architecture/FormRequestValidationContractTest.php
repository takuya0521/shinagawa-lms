<?php

namespace Tests\Feature\Architecture;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use ReflectionClass;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

/**
 * PJ内のFormRequestに定義された代表的な入力チェック規則を横断確認する。
 *
 * 個別Feature Testで業務フローを確認したうえで、このテストでは required / email /
 * max / min / integer / numeric / boolean / url / array / in などの入力規則が、
 * 各FormRequestに宣言された値どおりに機能することを確認する。
 */
final class FormRequestValidationContractTest extends TestCase
{
    /** 全FormRequestの入力チェック定義が空でなく、代表的な文字列ルールが期待どおり判定されることを確認する。 */
    public function test_declared_scalar_validation_rules_behave_as_defined(): void
    {
        $classes = $this->formRequestClasses();
        $this->assertNotEmpty($classes, 'FormRequestが1件も見つかりません。');

        foreach ($classes as $class) {
            /** @var FormRequest $request */
            $request = app($class);
            $rules = $request->rules();

            $this->assertIsArray($rules, $class.'::rules() は配列である必要があります。');
            $this->assertNotEmpty($rules, $class.'::rules() が空です。');

            foreach ($rules as $field => $fieldRules) {
                $this->assertIsString($field);
                $this->assertNotSame('', trim($field), $class.' に空の項目名があります。');
                $this->assertNotEmpty($fieldRules, $class.' の '.$field.' に入力チェック規則がありません。');

                foreach ($this->stringRules($fieldRules) as $rule) {
                    $this->assertRecognizedRuleBehavior($class, $field, $rule);
                }
            }
        }
    }

    private function assertRecognizedRuleBehavior(string $class, string $field, string $rule): void
    {
        [$name, $parameter] = array_pad(explode(':', $rule, 2), 2, null);
        $name = strtolower($name);

        $invalid = match ($name) {
            'required' => null,
            'email' => 'not-an-email-address',
            'integer' => 'not-an-integer',
            'numeric' => 'not-a-number',
            'boolean' => 'not-a-boolean',
            'url' => 'not-a-url',
            'array' => 'not-an-array',
            'in' => '__not_in_allowed_values__',
            default => '__skip__',
        };

        if ($invalid !== '__skip__') {
            $data = $name === 'required' ? [] : [$field => $invalid];
            $validator = Validator::make($data, [$field => [$rule]]);
            $this->assertTrue(
                $validator->errors()->has($field),
                sprintf('%s の %s に定義された %s が不正値を拒否していません。', $class, $field, $rule),
            );
        }

        if (($name === 'max' || $name === 'min') && $parameter !== null && ctype_digit($parameter)) {
            $limit = (int) $parameter;
            if ($limit <= 0 || $limit > 10000) {
                return;
            }

            // integer / numericが同一項目にある場合は数値、それ以外は文字列境界として確認する。
            $all = implode('|', $this->stringRules(app($class)->rules()[$field]));
            $numeric = str_contains($all, 'integer') || str_contains($all, 'numeric');

            if ($name === 'max') {
                $accepted = $numeric ? $limit : str_repeat('a', $limit);
                $rejected = $numeric ? $limit + 1 : str_repeat('a', $limit + 1);
            } else {
                $accepted = $numeric ? $limit : str_repeat('a', $limit);
                $rejected = $numeric ? $limit - 1 : str_repeat('a', max(0, $limit - 1));
            }

            $this->assertFalse(
                Validator::make([$field => $accepted], [$field => [$rule]])->errors()->has($field),
                sprintf('%s の %s に定義された %s が境界値を受け付けません。', $class, $field, $rule),
            );
            $this->assertTrue(
                Validator::make([$field => $rejected], [$field => [$rule]])->errors()->has($field),
                sprintf('%s の %s に定義された %s が境界外値を拒否していません。', $class, $field, $rule),
            );
        }
    }

    /** @param mixed $rules @return list<string> */
    private function stringRules(mixed $rules): array
    {
        if (is_string($rules)) {
            return array_values(array_filter(explode('|', $rules), static fn (string $rule): bool => $rule !== ''));
        }

        if (! is_array($rules)) {
            return [];
        }

        return array_values(array_filter($rules, 'is_string'));
    }

    /** @return list<class-string<FormRequest>> */
    private function formRequestClasses(): array
    {
        $base = app_path('Http/Requests');
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base));
        $classes = [];

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relative = str_replace(
                [$base.DIRECTORY_SEPARATOR, '.php', DIRECTORY_SEPARATOR],
                ['', '', '\\'],
                $file->getPathname(),
            );
            $class = 'App\\Http\\Requests\\'.$relative;
            if (! class_exists($class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);
            if ($reflection->isAbstract() || ! $reflection->isSubclassOf(FormRequest::class)) {
                continue;
            }

            $classes[] = $class;
        }

        sort($classes);

        return $classes;
    }
}
