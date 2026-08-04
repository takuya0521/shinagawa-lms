<?php

namespace App\Http\Requests\Admin;

use App\Data\Admin\OperationLogFilters;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class OperationLogIndexRequest extends FormRequest
{
    /**
     * 認証・権限制御は管理者ルートのミドルウェアで行う。
     *
     * @return bool 判定結果
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 操作ログ検索条件の入力ルールを返す。
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'date_from' => [
                'nullable',
                'date_format:Y-m-d',
            ],
            'date_to' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:date_from',
            ],
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
            ],
            'action' => [
                'nullable',
                'string',
                'max:100',
            ],
            'target_table' => [
                'nullable',
                'string',
                'max:100',
            ],
            'target_id' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'keyword' => [
                'nullable',
                'string',
                'max:100',
            ],
        ];
    }

    /**
     * 検証済み入力から型付き検索条件を生成する。
     *
     * @return OperationLogFilters 操作ログの検索条件
     */
    public function filters(): OperationLogFilters
    {
        return new OperationLogFilters(
            dateFrom: $this->dateFrom(),
            dateTo: $this->dateTo(),
            userId: $this->nullableId('user_id'),
            action: $this->nullableString('action'),
            targetTable: $this->nullableString('target_table'),
            targetId: $this->nullableId('target_id'),
            keyword: $this->nullableString('keyword'),
        );
    }

    /**
     * 検索開始日時を返す。
     *
     * @return CarbonImmutable 検索開始日の00時00分00秒
     */
    public function dateFrom(): CarbonImmutable
    {
        $value = $this->validated('date_from');

        return is_string($value)
            ? CarbonImmutable::createFromFormat(
                'Y-m-d',
                $value,
            )->startOfDay()
            : CarbonImmutable::now()
                ->subDays(30)
                ->startOfDay();
    }

    /**
     * 検索終了日時を返す。
     *
     * @return CarbonImmutable 検索終了日の23時59分59秒
     */
    public function dateTo(): CarbonImmutable
    {
        $value = $this->validated('date_to');

        return is_string($value)
            ? CarbonImmutable::createFromFormat(
                'Y-m-d',
                $value,
            )->endOfDay()
            : CarbonImmutable::now()->endOfDay();
    }

    /**
     * 指定項目をnullableなIDとして返す。
     *
     * @param  string  $key  取得する入力項目名
     * @return int|null 数値へ変換したID。未指定の場合はnull
     */
    public function nullableId(
        string $key,
    ): ?int {
        $value = $this->validated($key);

        return is_numeric($value)
            ? (int) $value
            : null;
    }

    /**
     * 指定項目をnullableな文字列として返す。
     *
     * @param  string  $key  取得する入力項目名
     * @return string|null 入力文字列。未指定の場合はnull
     */
    public function nullableString(
        string $key,
    ): ?string {
        $value = $this->validated($key);

        return is_string($value) && $value !== ''
            ? $value
            : null;
    }

    /**
     * 操作ログ出力へ保存する検索条件を返す。
     *
     * @return array<string, int|string|null>
     */
    public function auditFilters(): array
    {
        return $this->filters()->toAuditData();
    }

    /**
     * 空文字と前後空白を正規化する。
     *
     * @return void 戻り値なし
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'action' => $this->nullableTrimmed('action'),
            'target_table' => $this->nullableTrimmed(
                'target_table',
            ),
            'keyword' => $this->nullableTrimmed('keyword'),
        ]);
    }

    /**
     * 指定項目をtrimし、空文字をnullへ変換する。
     *
     * @param  string  $key  正規化する入力項目名
     * @return string|null 正規化後の文字列
     */
    private function nullableTrimmed(
        string $key,
    ): ?string {
        $value = trim(
            (string) $this->input(
                $key,
                '',
            ),
        );

        return $value === ''
            ? null
            : $value;
    }
}
