<?php

namespace App\Http\Requests\Admin;

use App\Data\Admin\CourseTeacherAssignmentFilters;
use App\Enums\Grade;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 担当教員設定一覧の検索条件を検証する。
 */
final class CourseTeacherAssignmentIndexRequest extends FormRequest
{
    /**
     * 管理者ルートのミドルウェアで認証・認可を行うため常に許可する。
     *
     * @return bool 常にtrue
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 担当教員設定一覧の検索条件に適用する入力規則を返す。
     *
     * @return array<string, list<mixed>> 検索条件の入力規則
     */
    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:100'],
            'academic_year' => ['nullable', 'integer', 'between:2000,2100'],
            'grade' => ['nullable', Rule::enum(Grade::class)],
            'class_group_id' => ['nullable', 'integer', 'exists:class_groups,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'assignment_status' => [
                'nullable',
                Rule::in(['assigned', 'unassigned']),
            ],
        ];
    }

    /**
     * 検証済み入力から型付き検索条件を生成する。
     *
     * 初期表示では当年度を選択し、年度が明示的に空の場合は全年度として扱う。
     *
     * @return CourseTeacherAssignmentFilters 担当教員設定一覧の検索条件
     */
    public function filters(): CourseTeacherAssignmentFilters
    {
        return new CourseTeacherAssignmentFilters(
            keyword: $this->nullableString('keyword') ?? '',
            academicYear: $this->academicYear(),
            grade: Grade::tryFrom($this->nullableString('grade') ?? ''),
            classGroupId: $this->nullableId('class_group_id'),
            subjectId: $this->nullableId('subject_id'),
            assignmentStatus: $this->nullableString('assignment_status'),
        );
    }

    /**
     * 文字列の前後空白と空文字を正規化する。
     *
     * @return void 戻り値なし
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'keyword' => $this->nullableTrimmed('keyword'),
            'grade' => $this->nullableTrimmed('grade'),
            'assignment_status' => $this->nullableTrimmed('assignment_status'),
            'academic_year' => $this->nullableValue('academic_year'),
            'class_group_id' => $this->nullableValue('class_group_id'),
            'subject_id' => $this->nullableValue('subject_id'),
        ]);
    }

    /**
     * 検索条件の日本語項目名を返す。
     *
     * @return array<string, string> 入力項目名
     */
    public function attributes(): array
    {
        return [
            'keyword' => 'キーワード',
            'academic_year' => '年度',
            'grade' => '学年',
            'class_group_id' => 'クラス',
            'subject_id' => '科目',
            'assignment_status' => '担当状況',
        ];
    }

    /**
     * 年度条件を返す。
     *
     * @return int|null 選択年度。全年度の場合はnull
     */
    private function academicYear(): ?int
    {
        if (! $this->query->has('academic_year')) {
            return now()->year;
        }

        $value = $this->validated('academic_year');

        return is_numeric($value)
            ? (int) $value
            : null;
    }

    /**
     * 指定項目をnullableなIDとして返す。
     *
     * @param  string  $key  取得する入力項目名
     * @return int|null 正の整数ID。未指定の場合はnull
     */
    private function nullableId(string $key): ?int
    {
        $value = $this->validated($key);

        return is_numeric($value) && (int) $value > 0
            ? (int) $value
            : null;
    }

    /**
     * 指定項目をnullableな文字列として返す。
     *
     * @param  string  $key  取得する入力項目名
     * @return string|null 入力文字列。未指定の場合はnull
     */
    private function nullableString(string $key): ?string
    {
        $value = $this->validated($key);

        return is_string($value) && $value !== ''
            ? $value
            : null;
    }

    /**
     * 指定項目をtrimし、空文字をnullへ変換する。
     *
     * @param  string  $key  正規化する入力項目名
     * @return string|null 正規化後の文字列
     */
    private function nullableTrimmed(string $key): ?string
    {
        $value = trim((string) $this->input($key, ''));

        return $value === ''
            ? null
            : $value;
    }

    /**
     * 空文字をnullへ変換し、それ以外の入力値を返す。
     *
     * @param  string  $key  正規化する入力項目名
     * @return mixed 正規化後の入力値
     */
    private function nullableValue(string $key): mixed
    {
        return $this->filled($key)
            ? $this->input($key)
            : null;
    }
}
