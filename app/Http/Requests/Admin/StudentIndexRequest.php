<?php

namespace App\Http\Requests\Admin;

use App\Enums\Grade;
use App\Enums\StudentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StudentIndexRequest extends FormRequest
{
    /**
     * 管理者制御はルートMiddlewareで実施する。
     *
     * @return bool 判定結果
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 生徒一覧の検索条件を返す。
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'keyword' => [
                'nullable',
                'string',
                'max:255',
            ],
            'grade' => [
                'nullable',
                Rule::enum(Grade::class),
            ],
            'affiliation' => [
                'nullable',
                'string',
                'max:100',
            ],
            'class_group_id' => [
                'nullable',
                'integer',
                Rule::exists('class_groups', 'id'),
            ],
            'status' => [
                'nullable',
                Rule::enum(StudentStatus::class),
            ],
        ];
    }

    /**
     * 検索キーワードを取得する。
     *
     * @return ?string 取得した文字列。未指定時はnull
     */
    public function keyword(): ?string
    {
        return $this->stringValue('keyword');
    }

    /**
     * 対象学年を取得する。
     *
     * @return ?Grade 処理結果。取得できない場合はnull
     */
    public function grade(): ?Grade
    {
        $grade = $this->validated('grade');

        return is_string($grade)
            ? Grade::tryFrom($grade)
            : null;
    }

    /**
     * 所属条件を取得する。
     *
     * @return ?string 取得した文字列。未指定時はnull
     */
    public function affiliation(): ?string
    {
        return $this->stringValue('affiliation');
    }

    /**
     * 対象クラスIDを取得する。
     *
     * @return ?int 取得した整数。未指定時はnull
     */
    public function classGroupId(): ?int
    {
        $classGroupId = $this->validated('class_group_id');

        return is_numeric($classGroupId)
            ? (int) $classGroupId
            : null;
    }

    /**
     * 指定された状態を取得する。
     *
     * @return ?StudentStatus 処理結果。取得できない場合はnull
     */
    public function status(): ?StudentStatus
    {
        $status = $this->validated('status');

        return is_string($status)
            ? StudentStatus::tryFrom($status)
            : null;
    }

    /**
     * 検証前に検索文字列を正規化する。
     *
     * @return void 戻り値なし
     */
    protected function prepareForValidation(): void
    {
        foreach (['keyword', 'grade', 'affiliation'] as $key) {
            $value = $this->input($key);

            if (is_string($value)) {
                $this->merge([
                    $key => trim($value),
                ]);
            }
        }
    }

    /**
     * 指定項目を文字列として取得する。
     *
     * @param  string  $key  取得対象のキー
     * @return ?string 取得した文字列。未指定時はnull
     */
    private function stringValue(string $key): ?string
    {
        $value = $this->validated($key);

        return is_string($value) && $value !== ''
            ? $value
            : null;
    }
}
