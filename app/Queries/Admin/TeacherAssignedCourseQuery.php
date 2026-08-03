<?php

namespace App\Queries\Admin;

use App\Models\Course;
use App\Models\Teacher;
use App\Models\TimetableSlot;
use Illuminate\Support\Collection;

final class TeacherAssignedCourseQuery
{
    /**
     * 教員が担当する授業を取得する。
     *
     * @param Teacher $teacher 対象教員
     * @return Collection<int, Course>
     */
    public function execute(Teacher $teacher): Collection
    {
        $assignedCourses = Course::query()
            ->where('teacher_id', $teacher->id)
            ->with([
                'subject',
                'classGroup',
                'timetableSlots',
            ])
            ->orderByDesc('academic_year')
            ->orderBy('grade')
            ->orderBy('class_group_id')
            ->orderBy('course_name')
            ->limit(20)
            ->get();

        foreach ($assignedCourses as $assignedCourse) {
            $assignedCourse->setRelation(
                'timetableSlots',
                $assignedCourse->timetableSlots
                    ->sortBy(
                        static fn (TimetableSlot $slot): string => sprintf(
                            '%02d:%02d',
                            $slot->day_of_week->value,
                            $slot->period_no,
                        ),
                    )
                    ->values(),
            );
        }

        return $assignedCourses;
    }
}
