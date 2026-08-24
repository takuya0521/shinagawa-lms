<?php

namespace Tests\Feature\Architecture;

use App\Models\Course;
use App\Models\TimetableSlot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route;
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
            $request = $this->requestForRules($class);
            $rules = $request->rules();

            $this->assertIsArray($rules, $class.'::rules() は配列である必要があります。');
            $this->assertNotEmpty($rules, $class.'::rules() が空です。');

            foreach ($rules as $field => $fieldRules) {
                $this->assertIsString($field);
                $this->assertNotSame('', trim($field), $class.' に空の項目名があります。');
                $this->assertNotEmpty($fieldRules, $class.' の '.$field.' に入力チェック規則がありません。');

                $stringRules = $this->stringRules($fieldRules);

                foreach ($stringRules as $rule) {
                    $this->assertRecognizedRuleBehavior(
                        $class,
                        $field,
                        $rule,
                        $stringRules,
                    );
                }
            }
        }
    }

    /**
     * FormRequestをサービスコンテナから解決するとValidatesWhenResolvedにより即時検証されるため、
     * 入力規則だけを確認できる空のHTTPリクエストとして生成する。
     *
     * 更新系Requestの一部はルートモデルをrules()内で参照するため、
     * DBへ保存していない最小限のモデルをルートパラメータとして与える。
     *
     * @param  class-string<FormRequest>  $class
     */
    private function requestForRules(string $class): FormRequest
    {
        /** @var FormRequest $request */
        $request = $class::create('/', 'POST');
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make('redirect'));

        $route = new Route(['POST'], '/', static fn (): null => null);
        $route->bind($request);
        $route->setParameter('course', new Course());
        $route->setParameter('timetableSlot', new TimetableSlot());
        $request->setRouteResolver(static fn (): Route => $route);

        return $request;
    }

    /** @param list<string> $allRules */
    private function assertRecognizedRuleBehavior(
        string $class,
        string $field,
        string $rule,
        array $allRules,
    ): void
    {
        [$name, $parameter] = array_pad(explode(':', $rule, 2), 2, null);
        $name = strtolower($name);

        $invalid = match ($name) {
            'required' => null,
            'string' => ['not-a-string'],
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
            $data = $this->dataForField($field, $invalid);
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

            $values = $this->boundaryValues($name, $limit, $allRules);
            if ($values === null) {
                return;
            }

            [$accepted, $rejected] = $values;
            $boundaryRules = $this->boundaryRules($rule, $allRules);

            $this->assertFalse(
                Validator::make(
                    $this->dataForField($field, $accepted),
                    [$field => $boundaryRules],
                )->errors()->has($field),
                sprintf('%s の %s に定義された %s が境界値を受け付けません。', $class, $field, $rule),
            );
            $this->assertTrue(
                Validator::make(
                    $this->dataForField($field, $rejected),
                    [$field => $boundaryRules],
                )->errors()->has($field),
                sprintf('%s の %s に定義された %s が境界外値を拒否していません。', $class, $field, $rule),
            );
        }
    }

    /**
     * ドット記法・ワイルドカード記法の項目名に合わせた検証用データを生成する。
     *
     * @return array<string, mixed>
     */
    private function dataForField(string $field, mixed $value): array
    {
        $segments = array_reverse(explode('.', $field));
        $data = $value;

        foreach ($segments as $segment) {
            $data = $segment === '*'
                ? [$data]
                : [$segment => $data];
        }

        /** @var array<string, mixed> $data */
        return $data;
    }

    /**
     * max / min規則を項目の型に合わせて検証できる境界値を返す。
     *
     * ファイルサイズはUploadedFileが必要なため、この横断テストでは対象外とし、
     * 個別Feature Testと単体テスト仕様書対応テストへ委ねる。
     *
     * @param  list<string>  $allRules
     * @return array{0: mixed, 1: mixed}|null
     */
    private function boundaryValues(
        string $name,
        int $limit,
        array $allRules,
    ): ?array {
        if (in_array('file', $allRules, true)) {
            return null;
        }

        if (in_array('array', $allRules, true)) {
            $accepted = array_fill(0, $limit, 'value');
            $rejectedCount = $name === 'max'
                ? $limit + 1
                : max(0, $limit - 1);

            return [
                $accepted,
                array_fill(0, $rejectedCount, 'value'),
            ];
        }

        $numeric = in_array('integer', $allRules, true)
            || in_array('numeric', $allRules, true);

        if ($numeric) {
            return $name === 'max'
                ? [$limit, $limit + 1]
                : [$limit, $limit - 1];
        }

        return $name === 'max'
            ? [str_repeat('a', $limit), str_repeat('a', $limit + 1)]
            : [str_repeat('a', $limit), str_repeat('a', max(0, $limit - 1))];
    }

    /**
     * max / min単体では数値・配列のサイズ判定方法が変わるため、型規則も同時に返す。
     *
     * @param  list<string>  $allRules
     * @return list<string>
     */
    private function boundaryRules(string $boundaryRule, array $allRules): array
    {
        foreach (['integer', 'numeric', 'array', 'string'] as $typeRule) {
            if (in_array($typeRule, $allRules, true)) {
                return [$typeRule, $boundaryRule];
            }
        }

        return [$boundaryRule];
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
