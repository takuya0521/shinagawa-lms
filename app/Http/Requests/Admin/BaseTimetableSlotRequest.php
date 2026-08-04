<?php

namespace App\Http\Requests\Admin;

use App\Enums\DayOfWeek;
use App\Enums\MasterStatus;
use App\Models\Course;
use App\Models\TimetableSlot;
use App\Queries\Admin\TimetableConflictQuery;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\Validator;

/**
 * 時間割登録・更新で共通する入力規則と重複検証を提供する。
 */
abstract class BaseTimetableSlotRequest extends FormRequest
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
     * 時間割登録・更新で共通する入力ルールを返す。
     *
     * @return array<string, mixed> 入力項目ごとの検証ルール
     */
    final public function rules(): array
    {
        $timetableSlot = $this->currentTimetableSlot();

        return [
            'course_id' => [
                'required',
                'integer',
                $this->availableCourseExistsRule($timetableSlot),
            ],
            'day_of_week' => [
                'required',
                'integer',
                Rule::in(
                    array_map(
                        static fn (DayOfWeek $day): int => $day->value,
                        DayOfWeek::cases(),
                    ),
                ),
            ],
            'period_no' => [
                'required',
                'integer',
                'between:1,6',
                $this->slotUniqueRule($timetableSlot),
            ],
            'start_time' => [
                'nullable',
                'date_format:H:i',
                'required_with:end_time',
            ],
            'end_time' => [
                'nullable',
                'date_format:H:i',
                'required_with:start_time',
                'after:start_time',
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
            'course_id' => '授業',
            'day_of_week' => '曜日',
            'period_no' => '時限',
            'start_time' => '開始時刻',
            'end_time' => '終了時刻',
            'status' => '状態',
        ];
    }

    /**
     * クラスおよび担当教員の時間割重複を検証する。
     *
     * @param  Validator  $validator  Laravelの入力検証器
     * @return void 戻り値なし
     */
    final public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->input('status') !== MasterStatus::Active->value) {
                return;
            }

            $course = Course::query()->find($this->integer('course_id'));
            $dayOfWeek = DayOfWeek::tryFrom(
                $this->integer('day_of_week'),
            );

            if ($course === null || $dayOfWeek === null) {
                return;
            }

            $excludedSlotId = $this->currentTimetableSlot()?->id;
            $conflictQuery = app(TimetableConflictQuery::class);

            if (
                $conflictQuery->findClassConflict(
                    $course,
                    $dayOfWeek,
                    $this->integer('period_no'),
                    $excludedSlotId,
                ) !== null
            ) {
                $validator->errors()->add(
                    'period_no',
                    '同じ年度・学年・クラスの同じ曜日・時限に、別の授業が登録されています。',
                );

                return;
            }

            if (
                $conflictQuery->findTeacherConflict(
                    $course,
                    $dayOfWeek,
                    $this->integer('period_no'),
                    $excludedSlotId,
                ) !== null
            ) {
                $validator->errors()->add(
                    'period_no',
                    '担当教員は同じ年度の同じ曜日・時限に、別の授業を担当しています。',
                );
            }
        });
    }

    /**
     * 時刻の空文字をnullへ変換する。
     *
     * @return void 戻り値なし
     */
    final protected function prepareForValidation(): void
    {
        $this->merge([
            'start_time' => $this->nullableTrimmed('start_time'),
            'end_time' => $this->nullableTrimmed('end_time'),
        ]);
    }

    /**
     * 更新対象の時間割枠を返す。
     *
     * 登録時はnull、更新時はルートモデルを返す。
     *
     * @return TimetableSlot|null 更新対象の時間割枠
     */
    abstract protected function currentTimetableSlot(): ?TimetableSlot;

    /**
     * 有効な授業、または更新前に選択されていた授業を許可する存在確認規則を返す。
     *
     * @param  TimetableSlot|null  $timetableSlot  更新対象の時間割枠
     * @return Exists 存在確認規則
     */
    private function availableCourseExistsRule(
        ?TimetableSlot $timetableSlot,
    ): Exists {
        $currentCourseId = $timetableSlot?->course_id;

        return Rule::exists('courses', 'id')->where(
            static function (Builder $query) use ($currentCourseId): void {
                $query
                    ->whereNull('deleted_at')
                    ->where(
                        static function (Builder $courseQuery) use ($currentCourseId): void {
                            $courseQuery->where(
                                'status',
                                MasterStatus::Active->value,
                            );

                            if ($currentCourseId !== null) {
                                $courseQuery->orWhere('id', $currentCourseId);
                            }
                        },
                    );
            },
        );
    }

    /**
     * 同一授業・曜日・時限の重複を確認する規則を返す。
     *
     * @param  TimetableSlot|null  $timetableSlot  更新対象の時間割枠
     * @return Unique 一意性確認規則
     */
    private function slotUniqueRule(
        ?TimetableSlot $timetableSlot,
    ): Unique {
        $rule = Rule::unique('timetable_slots', 'period_no')->where(
            function (Builder $query): void {
                $query
                    ->where('course_id', $this->integer('course_id'))
                    ->where('day_of_week', $this->integer('day_of_week'));
            },
        );

        return $timetableSlot === null
            ? $rule
            : $rule->ignore($timetableSlot->id);
    }

    /**
     * 入力値の前後空白を除去し、空文字の場合はnullを返す。
     *
     * @param  string  $key  取得する入力項目名
     * @return string|null 整形後の文字列
     */
    private function nullableTrimmed(string $key): ?string
    {
        $value = trim((string) $this->input($key, ''));

        return $value !== '' ? $value : null;
    }
}
