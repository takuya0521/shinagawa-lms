<?php

namespace Tests\Feature\Student;

use App\Enums\DayOfWeek;
use App\Enums\Grade;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TimetableSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 生徒向け時間割表示機能を確認するフィーチャーテスト。
 *
 * 対象クラス・学年の時間割、年度切り替え、権限制御を検証する。
 */
final class TimetableTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 生徒ダッシュボードに自身の学年・クラスを対象とする時間割だけが表示されることを確認する。
     *
     * 前提: クラス、生徒、教員、授業、時間割枠など、検証に必要なテストデータを準備する。
     * 処理: `student.dashboard`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示される、表示対象外の文言やデータが画面に出ないことを確認する。
     */
    public function test_student_dashboard_shows_only_own_target_timetable(): void
    {
        $classGroup = ClassGroup::factory()->create();
        $student = Student::factory()->create([
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
        ]);
        $teacher = Teacher::factory()->create();

        $ownCourse = Course::factory()->create([
            'academic_year' => now()->year,
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
            'teacher_id' => $teacher->id,
            'course_name' => '本人対象授業',
        ]);
        TimetableSlot::factory()->create([
            'course_id' => $ownCourse->id,
            'day_of_week' => DayOfWeek::Monday,
        ]);

        $otherCourse = Course::factory()->create([
            'academic_year' => now()->year,
            'grade' => Grade::Second,
            'course_name' => '対象外授業',
        ]);
        TimetableSlot::factory()->create([
            'course_id' => $otherCourse->id,
            'day_of_week' => DayOfWeek::Tuesday,
        ]);

        $this
            ->actingAs($student->user)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSeeText($student->student_name)
            ->assertSeeText($ownCourse->course_name)
            ->assertDontSeeText($otherCourse->course_name);
    }

    /**
     * 生徒が年度を選択して週間時間割を閲覧できることを確認する。
     *
     * 前提: クラス、生徒、授業、時間割枠など、検証に必要なテストデータを準備する。
     * 処理: `student.timetable.index`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示される、表示対象外の文言やデータが画面に出ないことを確認する。
     */
    public function test_student_can_select_academic_year_for_weekly_timetable(): void
    {
        $classGroup = ClassGroup::factory()->create();
        $student = Student::factory()->create([
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
        ]);

        $course2026 = Course::factory()->create([
            'academic_year' => 2026,
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
            'course_name' => '2026年度授業',
        ]);
        TimetableSlot::factory()->create([
            'course_id' => $course2026->id,
        ]);

        $course2027 = Course::factory()->create([
            'academic_year' => 2027,
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
            'course_name' => '2027年度授業',
        ]);
        TimetableSlot::factory()->create([
            'course_id' => $course2027->id,
        ]);

        $this
            ->actingAs($student->user)
            ->get(route('student.timetable.index', [
                'academic_year' => 2026,
            ]))
            ->assertOk()
            ->assertSeeText($course2026->course_name)
            ->assertDontSeeText($course2027->course_name);
    }

    /**
     * 教員が生徒向け時間割画面へアクセスできないことを確認する。
     *
     * 前提: 教員など、検証に必要なテストデータを準備する。
     * 処理: `student.timetable.index`へGETリクエストを送信する。
     * 期待結果: 権限不足としてHTTP 403で拒否されることを確認する。
     */
    public function test_teacher_cannot_access_student_timetable(): void
    {
        $teacher = Teacher::factory()->create();

        $this
            ->actingAs($teacher->user)
            ->get(route('student.timetable.index'))
            ->assertForbidden();
    }
}
