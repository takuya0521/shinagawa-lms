<?php

namespace Tests\Feature\Specification;

use App\Support\Auth\LoginCredentialRules;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\RequiredIf;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * 単体テスト仕様書の項目定義と実装中の入力検証規則が一致することを確認する。
 *
 * データセット名にExcelのテスト番号を使用し、仕様書から実装テストを一意に追跡できるようにする。
 */
final class SpreadsheetValidationContractTest extends TestCase
{
    /**
     * Excelの1テスト観点に対し、対応する入力検証規則が実装されていることを確認する。
     *
     * @param  array<string, mixed>  $case
     */
    #[DataProvider('spreadsheetCases')]
    public function test_spreadsheet_validation_case_is_implemented(array $case): void
    {
        $rules = $this->rulesFor((string) $case['request']);
        $field = (string) $case['field'];

        self::assertArrayHasKey(
            $field,
            $rules,
            $case['case_id'].' '.$case['viewpoint'].' に対応する入力規則がありません。',
        );

        $fieldRules = is_array($rules[$field])
            ? $rules[$field]
            : [$rules[$field]];
        $params = is_array($case['params']) ? $case['params'] : [];

        match ($case['assertion']) {
            'required' => self::assertTrue(
                $this->hasRequiredRule($fieldRules),
                $case['case_id'].' に必須入力の規則がありません。',
            ),
            'email' => self::assertTrue(
                $this->hasStringRule($fieldRules, 'email'),
                $case['case_id'].' にメールアドレス形式の規則がありません。',
            ),
            'url' => self::assertTrue(
                $this->hasStringRule($fieldRules, 'url'),
                $case['case_id'].' にURL形式の規則がありません。',
            ),
            'max_length' => $this->assertLengthBoundary($case, $fieldRules, $params),
            'numeric_boundary' => $this->assertNumericBoundary($case, $fieldRules, $params),
            'allowed_values' => self::assertTrue(
                $this->hasAllowedValuesRule($fieldRules),
                $case['case_id'].' に許可値を制限する規則がありません。',
            ),
            'boolean' => self::assertTrue(
                $this->hasStringRule($fieldRules, 'boolean'),
                $case['case_id'].' に真偽値の規則がありません。',
            ),
            'file_size' => $this->assertFileSizeRule($case, $fieldRules, $params),
            'custom_email_list' => self::assertTrue(
                $this->hasClosureRule($fieldRules),
                $case['case_id'].' にメールアドレス一覧を検証する独自規則がありません。',
            ),
            'numeric_or_boolean' => self::assertTrue(
                $this->hasStringRule($fieldRules, 'integer')
                    || $this->hasStringRule($fieldRules, 'numeric')
                    || $this->hasStringRule($fieldRules, 'boolean')
                    || $this->hasAllowedValuesRule($fieldRules),
                $case['case_id'].' に数値・真偽値・許可値のいずれの規則もありません。',
            ),
            default => self::assertNotEmpty(
                $fieldRules,
                $case['case_id'].' に入力規則がありません。',
            ),
        };
    }

    /**
     * Excelテスト番号をキーにしたデータセットを返す。
     *
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function spreadsheetCases(): array
    {
        /** @var array<string, array<string, mixed>> $cases */
        $cases = require dirname(__DIR__, 2).'/Fixtures/spreadsheet_validation_cases.php';
        $datasets = [];

        foreach ($cases as $caseId => $case) {
            $datasets[$caseId] = [$case];
        }

        return $datasets;
    }

    /**
     * 対象クラスから実際の入力検証規則を取得する。
     *
     * @return array<string, mixed>
     */
    private function rulesFor(string $requestClass): array
    {
        if ($requestClass === LoginCredentialRules::class) {
            return LoginCredentialRules::rules();
        }

        self::assertTrue(
            is_a($requestClass, FormRequest::class, true),
            $requestClass.' はFormRequestではありません。',
        );

        /** @var FormRequest $request */
        $request = $requestClass::create('/', 'POST');
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make('redirect'));

        return $request->rules();
    }

    /** @param list<mixed> $rules */
    private function hasRequiredRule(array $rules): bool
    {
        foreach ($rules as $rule) {
            if (is_string($rule) && ($rule === 'required' || str_starts_with($rule, 'required_'))) {
                return true;
            }

            if ($rule instanceof RequiredIf) {
                return true;
            }
        }

        return false;
    }

    /** @param list<mixed> $rules */
    private function hasStringRule(array $rules, string $name): bool
    {
        foreach ($rules as $rule) {
            if (! is_string($rule)) {
                continue;
            }

            if ($rule === $name || str_starts_with($rule, $name.':')) {
                return true;
            }
        }

        return false;
    }

    /** @param list<mixed> $rules */
    private function hasAllowedValuesRule(array $rules): bool
    {
        foreach ($rules as $rule) {
            if ($rule instanceof Enum || $rule instanceof In) {
                return true;
            }

            if (is_string($rule)
                && (str_starts_with($rule, 'in:')
                    || str_starts_with($rule, 'regex:')
                    || $rule === 'boolean')) {
                return true;
            }
        }

        return false;
    }

    /** @param list<mixed> $rules */
    private function hasClosureRule(array $rules): bool
    {
        foreach ($rules as $rule) {
            if ($rule instanceof Closure) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $case
     * @param  list<mixed>  $rules
     * @param  array<string, mixed>  $params
     */
    private function assertLengthBoundary(array $case, array $rules, array $params): void
    {
        $max = $this->numericRuleValue($rules, 'max');
        $length = (int) ($params['length'] ?? -1);
        $shouldPass = (bool) ($params['should_pass'] ?? false);

        self::assertNotNull($max, $case['case_id'].' に最大文字数の規則がありません。');
        self::assertSame(
            $shouldPass,
            $length <= $max,
            $case['case_id'].' の文字数境界と実装のmax規則が一致しません。',
        );
    }

    /**
     * @param  array<string, mixed>  $case
     * @param  list<mixed>  $rules
     * @param  array<string, mixed>  $params
     */
    private function assertNumericBoundary(array $case, array $rules, array $params): void
    {
        $value = (int) ($params['value'] ?? 0);
        $shouldPass = (bool) ($params['should_pass'] ?? false);
        $min = $this->numericRuleValue($rules, 'min');
        $max = $this->numericRuleValue($rules, 'max');
        $between = $this->betweenRule($rules);

        if ($between !== null) {
            [$min, $max] = $between;
        }

        self::assertTrue(
            $min !== null || $max !== null,
            $case['case_id'].' に数値の上限・下限規則がありません。',
        );

        $passes = ($min === null || $value >= $min)
            && ($max === null || $value <= $max);

        self::assertSame(
            $shouldPass,
            $passes,
            $case['case_id'].' の数値境界と実装規則が一致しません。',
        );
    }

    /**
     * @param  array<string, mixed>  $case
     * @param  list<mixed>  $rules
     * @param  array<string, mixed>  $params
     */
    private function assertFileSizeRule(array $case, array $rules, array $params): void
    {
        self::assertTrue(
            $this->hasStringRule($rules, 'file'),
            $case['case_id'].' にファイル規則がありません。',
        );

        $max = $this->numericRuleValue($rules, 'max');
        self::assertNotNull($max, $case['case_id'].' にファイルサイズ上限がありません。');

        if (array_key_exists('max_kb', $params)) {
            self::assertSame((int) $params['max_kb'], $max);
        }
    }

    /** @param list<mixed> $rules */
    private function numericRuleValue(array $rules, string $ruleName): ?int
    {
        foreach ($rules as $rule) {
            if (! is_string($rule)) {
                continue;
            }

            if (preg_match('/(?:^|\\|)'.preg_quote($ruleName, '/').':(-?\\d+)/', $rule, $matches) === 1) {
                return (int) $matches[1];
            }
        }

        return null;
    }

    /**
     * @param  list<mixed>  $rules
     * @return array{0: int, 1: int}|null
     */
    private function betweenRule(array $rules): ?array
    {
        foreach ($rules as $rule) {
            if (! is_string($rule)) {
                continue;
            }

            if (preg_match('/(?:^|\\|)between:(-?\\d+),(-?\\d+)/', $rule, $matches) === 1) {
                return [(int) $matches[1], (int) $matches[2]];
            }
        }

        return null;
    }
}
