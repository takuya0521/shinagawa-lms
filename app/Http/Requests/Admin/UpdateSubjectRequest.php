<?php

namespace App\Http\Requests\Admin;

use App\Enums\MasterStatus;
use App\Models\Subject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateSubjectRequest extends FormRequest
{
    /**
     * 認証・権限制御はルートの管理者ミドルウェアで行う。
     *
     * @return bool 判定結果
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 科目更新時の入力ルールを返す。
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Subject $subject */
        $subject = $this->route('subject');

        return [
            'subject_code' => [
                'required',
                'string',
                'max:30',
                'regex:/\A[A-Z0-9_-]+\z/',
                Rule::unique(
                    Subject::class,
                    'subject_code',
                )->ignore($subject),
            ],
            'subject_name' => [
                'required',
                'string',
                'max:100',
            ],
            'status' => [
                'required',
                Rule::enum(MasterStatus::class),
            ],
        ];
    }

    /**
     * 入力項目名を日本語で返す。
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'subject_code' => '科目コード',
            'subject_name' => '科目名',
            'status' => '状態',
        ];
    }

    /**
     * 科目コードを比較可能な形式へ統一する。
     *
     * @return void 戻り値なし
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'subject_code' => strtoupper(
                trim(
                    (string) $this->input(
                        'subject_code',
                        '',
                    ),
                ),
            ),
            'subject_name' => trim(
                (string) $this->input(
                    'subject_name',
                    '',
                ),
            ),
        ]);
    }
}
