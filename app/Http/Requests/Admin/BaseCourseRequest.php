<?php

namespace App\Http\Requests\Admin;

use App\Enums\Grade;
use App\Enums\MasterStatus;
use App\Models\Course;
use App\Rules\AvailableTeacher;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

/**
 * 授業登録・更新で共通する入力規則と入力整形を提供する。
 */
abstract class BaseCourseRequest extends FormRequest
{
    /**
     * 認証・権限制御は管理者ルートのミドルウェアで行う。
     *
     * @return bool 常にtrue
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 授業登録・更新で共通する入力ルールを返す。
     *
     * @return array<string, mixed> 入力項目ごとの検証ルール
     */
    final public function rules(): array
    {
        $course = $this->currentCourse();

        return [
            'academic_year' => [
                'required',
                'integer',
                'between:2000,2100',
            ],
            'grade' => [
                'required',
                Rule::enum(Grade::class),
            ],
            'class_group_id' => [
                'required',
                'integer',
                $this->activeMasterExistsRule(
                    'class_groups',
                    $course?->class_group_id,
                ),
            ],
            'subject_id' => [
                'required',
                'integer',
                $this->activeMasterExistsRule(
                    'subjects',
                    $course?->subject_id,
                ),
            ],
            'course_name' => [
                'required',
                'string',
                'max:100',
                $this->courseNameUniqueRule($course),
            ],
            'teacher_id' => [
                'bail',
                'nullable',
                'integer',
                new AvailableTeacher($course?->teacher_id),
            ],
            'google_classroom_url' => [
                'nullable',
                'string',
                'url',
                'max:500',
            ],
            'google_classroom_id' => [
                'nullable',
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
     * @return array<string, string> 入力項目名と日本語表示名の対応
     */
    final public function attributes(): array
    {
        return [
            'academic_year' => '年度',
            'grade' => '学年',
            'class_group_id' => 'クラス',
            'subject_id' => '科目',
            'course_name' => '授業名',
            'teacher_id' => '担当教員',
            'google_classroom_url' => 'Google Classroom URL',
            'google_classroom_id' => 'Google Classroom外部ID',
            'status' => '状態',
        ];
    }

    /**
     * 授業入力値を保存可能な形式へ整える。
     *
     * @return void 戻り値なし
     */
    final protected function prepareForValidation(): void
    {
        $this->merge([
            'grade' => trim((string) $this->input('grade', '')),
            'course_name' => trim((string) $this->input('course_name', '')),
            'teacher_id' => $this->filled('teacher_id')
                ? $this->input('teacher_id')
                : null,
            'google_classroom_url' => $this->nullableTrimmed(
                'google_classroom_url',
            ),
            'google_classroom_id' => $this->nullableTrimmed(
                'google_classroom_id',
            ),
        ]);
    }

    /**
     * 更新対象の授業を返す。
     *
     * 登録時はnull、更新時はルートモデルを返す。
     *
     * @return Course|null 更新対象の授業
     */
    abstract protected function currentCourse(): ?Course;

    /**
     * 有効なマスタ、または更新前に選択されていたマスタを許可する存在確認規則を返す。
     *
     * @param string $table 確認対象のテーブル名
     * @param int|null $currentId 更新前に選択されていたID
     * @return Exists 存在確認規則
     */
    private function activeMasterExistsRule(
        string $table,
        ?int $currentId,
    ): Exists {
        return Rule::exists($table, 'id')->where(
            static function (Builder $query) use ($currentId): void {
                $query->where(
                    static function (Builder $statusQuery) use ($currentId): void {
                        $statusQuery->where(
                            'status',
                            MasterStatus::Active->value,
                        );

                        if ($currentId !== null) {
                            $statusQuery->orWhere('id', $currentId);
                        }
                    },
                );
            },
        );
    }

    /**
     * 年度・クラス・学年・科目内で授業名が重複しないことを確認する規則を返す。
     *
     * @param Course|null $course 更新対象の授業。登録時はnull
     * @return Unique 一意性確認規則
     */
    private function courseNameUniqueRule(?Course $course): Unique
    {
        $rule = Rule::unique('courses', 'course_name')->where(
            function (Builder $query): void {
                $query
                    ->where('academic_year', $this->integer('academic_year'))
                    ->where('class_group_id', $this->integer('class_group_id'))
                    ->where('grade', (string) $this->input('grade'))
                    ->where('subject_id', $this->integer('subject_id'));
            },
        );

        return $course === null
            ? $rule
            : $rule->ignore($course->id);
    }

    /**
     * 入力値の前後空白を除去し、空文字の場合はnullを返す。
     *
     * @param string $key 取得する入力項目名
     * @return string|null 整形後の文字列
     */
    private function nullableTrimmed(string $key): ?string
    {
        $value = trim((string) $this->input($key, ''));

        return $value !== '' ? $value : null;
    }
}
