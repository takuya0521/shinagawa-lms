<?php

namespace App\Queries\Interview;

use App\Models\InterviewRecord;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * 面談記録で使用する面談種別の選択肢を取得する。
 */
final class InterviewTypeOptionsQuery
{
    /**
     * 面談種別の選択肢を取得する。
     *
     * 教員が指定された場合は、その教員が過去に使用した面談種別へ限定する。
     * 標準の面談種別は、利用実績の有無にかかわらず常に含める。
     *
     * @param  Teacher|null  $teacher  対象教員。管理者向け全件取得時はnull
     * @return Collection<int, string> 面談種別選択肢
     */
    public function execute(?Teacher $teacher = null): Collection
    {
        $query = InterviewRecord::query()
            ->whereNotNull('interview_type')
            ->where('interview_type', '<>', '');

        $this->applyTeacherScope($query, $teacher);

        return $query
            ->distinct()
            ->orderBy('interview_type')
            ->pluck('interview_type')
            ->filter(
                static fn (mixed $value): bool => is_string($value),
            )
            ->map(
                static fn (mixed $value): string => (string) $value,
            )
            ->prepend('進路面談')
            ->prepend('希望面談')
            ->prepend('定期面談')
            ->unique()
            ->values();
    }

    /**
     * 教員が指定されている場合に担当教員条件を適用する。
     *
     * @param  Builder<InterviewRecord>  $query  面談記録クエリ
     * @param  Teacher|null  $teacher  対象教員。全件取得時はnull
     * @return void 戻り値なし
     */
    private function applyTeacherScope(
        Builder $query,
        ?Teacher $teacher,
    ): void {
        if ($teacher === null) {
            return;
        }

        $query->where('teacher_id', $teacher->id);
    }
}
