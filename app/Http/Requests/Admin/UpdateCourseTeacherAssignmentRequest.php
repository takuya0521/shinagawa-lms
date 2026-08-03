<?php

namespace App\Http\Requests\Admin;

use App\Enums\Grade;
use App\Enums\MasterStatus;
use App\Models\Course;
use App\Rules\AvailableTeacher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class UpdateCourseTeacherAssignmentRequest extends FormRequest
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
     * 担当講師設定時の入力ルールを返す。
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Course $course */
        $course = $this->route('course');

        return [
            'teacher_id' => [
                'bail',
                'nullable',
                'integer',
                new AvailableTeacher(
                    $course->teacher_id,
                ),
            ],
            'filter_keyword' => [
                'nullable',
                'string',
                'max:100',
            ],
            'filter_academic_year' => [
                'nullable',
                'integer',
                'between:2000,2100',
            ],
            'filter_grade' => [
                'nullable',
                Rule::enum(Grade::class),
            ],
            'filter_class_group_id' => [
                'nullable',
                'integer',
                'exists:class_groups,id',
            ],
            'filter_subject_id' => [
                'nullable',
                'integer',
                'exists:subjects,id',
            ],
            'filter_assignment_status' => [
                'nullable',
                Rule::in([
                    'assigned',
                    'unassigned',
                ]),
            ],
            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ];
    }


    /**
     * 検証済み入力から設定対象の教員IDを返す。
     *
     * @return int|null 設定する教員ID。担当解除の場合はnull
     */
    public function teacherId(): ?int
    {
        $value = $this->validated('teacher_id');

        return is_numeric($value)
            ? (int) $value
            : null;
    }

    /**
     * 更新後の一覧画面へ引き継ぐ検索条件を返す。
     *
     * @return array<string, int|string> 一覧画面へ引き継ぐクエリ文字列
     */
    public function redirectFilters(): array
    {
        $validated = $this->validated();
        $filterMap = [
            'filter_keyword' => 'keyword',
            'filter_academic_year' => 'academic_year',
            'filter_grade' => 'grade',
            'filter_class_group_id' => 'class_group_id',
            'filter_subject_id' => 'subject_id',
            'filter_assignment_status' => 'assignment_status',
            'page' => 'page',
        ];
        $filters = [];

        foreach ($filterMap as $source => $destination) {
            if (! array_key_exists($source, $validated)) {
                continue;
            }

            if ($source === 'filter_academic_year' && $validated[$source] === null) {
                // 年度の空指定は全年度を表すため、空文字として明示的に引き継ぐ。
                $filters[$destination] = '';

                continue;
            }

            if ($validated[$source] === null || $validated[$source] === '') {
                continue;
            }

            /** @var int|string $value */
            $value = $validated[$source];
            $filters[$destination] = $value;
        }

        return $filters;
    }

    /**
     * 授業自体が無効になっていないか追加検証する。
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var Course $course */
                $course = $this->route('course');

                $requestedTeacherId = $this->filled('teacher_id')
                    ? $this->integer('teacher_id')
                    : null;

                if (
                    $course->status !== MasterStatus::Active
                    && $requestedTeacherId !== null
                    && $requestedTeacherId !== $course->teacher_id
                ) {
                    $validator->errors()->add(
                        'teacher_id',
                        '無効な授業には新しい担当教員を設定できません。担当解除のみ可能です。',
                    );
                }
            },
        ];
    }

    /**
     * 入力値を保存・再表示に利用できる形式へ整える。
     *
     * @return void 戻り値なし
     */
    protected function prepareForValidation(): void
    {
        /** @var array<string, mixed> $normalized */
        $normalized = [
            'teacher_id' => $this->filled('teacher_id')
                ? $this->input('teacher_id')
                : null,
        ];

        // 送信されていない検索条件は追加せず、更新後のURLへ不要な空クエリを付けない。
        if ($this->exists('filter_keyword')) {
            $normalized['filter_keyword'] = $this->nullableTrimmed(
                'filter_keyword',
            );
        }

        if ($this->exists('filter_grade')) {
            $normalized['filter_grade'] = $this->nullableTrimmed(
                'filter_grade',
            );
        }

        if ($this->exists('filter_assignment_status')) {
            $normalized['filter_assignment_status'] = $this->nullableTrimmed(
                'filter_assignment_status',
            );
        }

        if ($this->exists('filter_academic_year')) {
            $normalized['filter_academic_year'] = $this->filled(
                'filter_academic_year',
            )
                ? $this->input('filter_academic_year')
                : null;
        }

        if ($this->exists('filter_class_group_id')) {
            $normalized['filter_class_group_id'] = $this->filled(
                'filter_class_group_id',
            )
                ? $this->input('filter_class_group_id')
                : null;
        }

        if ($this->exists('filter_subject_id')) {
            $normalized['filter_subject_id'] = $this->filled(
                'filter_subject_id',
            )
                ? $this->input('filter_subject_id')
                : null;
        }

        if ($this->exists('page')) {
            $normalized['page'] = $this->filled('page')
                ? $this->input('page')
                : null;
        }

        $this->merge($normalized);
    }

    /**
     * 入力項目名を日本語で返す。
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'teacher_id' => '担当教員',
        ];
    }

    /**
     * 空文字をnullへ変換した文字列を返す。
     *
     * @param string $key 取得対象のキー
     * @return ?string 取得した文字列。未指定時はnull
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
