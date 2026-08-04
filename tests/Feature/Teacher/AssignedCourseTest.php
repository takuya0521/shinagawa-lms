<?php

namespace Tests\Feature\Teacher;

use App\Enums\Grade;
use App\Enums\MasterStatus;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 教員向け担当授業・受講生一覧を確認するフィーチャーテスト。
 *
 * 自身の有効な担当授業、学年・クラスに一致する生徒、他教員データのアクセス制御を検証する。
 */
final class AssignedCourseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 教員に自身が担当する有効な授業だけが表示されることを確認する。
     *
     * 前提: 教員、授業など、検証に必要なテストデータを準備する。
     * 処理: `teacher.courses.index`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示される、表示対象外の文言やデータが画面に出ないことを確認する。
     */
    public function test_teacher_sees_only_own_active_courses(): void
    {
        $teacher = Teacher::factory()->create();
        $otherTeacher = Teacher::factory()->create();

        $ownCourse = Course::factory()->create([
            'teacher_id' => $teacher->id,
            'course_name' => '自分の担当授業',
            'status' => MasterStatus::Active,
        ]);

        $otherCourse = Course::factory()->create([
            'teacher_id' => $otherTeacher->id,
            'course_name' => '他教員の授業',
            'status' => MasterStatus::Active,
        ]);

        $inactiveCourse = Course::factory()->create([
            'teacher_id' => $teacher->id,
            'course_name' => '無効な担当授業',
            'status' => MasterStatus::Inactive,
        ]);

        $this
            ->actingAs($teacher->user)
            ->get(route('teacher.courses.index'))
            ->assertOk()
            ->assertSeeText($ownCourse->course_name)
            ->assertDontSeeText($otherCourse->course_name)
            ->assertDontSeeText($inactiveCourse->course_name);
    }

    /**
     * 授業の生徒一覧に対象学年・クラスへ一致する生徒だけが表示されることを確認する。
     *
     * 前提: 教員、クラス、授業、生徒など、検証に必要なテストデータを準備する。
     * 処理: `teacher.courses.students.index`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示される、表示対象外の文言やデータが画面に出ないことを確認する。
     */
    public function test_course_student_list_contains_only_matching_grade_and_class(): void
    {
        $teacher = Teacher::factory()->create();
        $classGroup = ClassGroup::factory()->create();

        $course = Course::factory()->create([
            'teacher_id' => $teacher->id,
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
        ]);

        $matchingStudent = Student::factory()->create([
            'class_group_id' => $classGroup->id,
            'grade' => Grade::First,
            'student_name' => '対象生徒',
        ]);

        $otherGradeStudent = Student::factory()->create([
            'class_group_id' => $classGroup->id,
            'grade' => Grade::Second,
            'student_name' => '別学年生徒',
        ]);

        $otherClassStudent = Student::factory()->create([
            'grade' => Grade::First,
            'student_name' => '別クラス生徒',
        ]);

        $this
            ->actingAs($teacher->user)
            ->get(route('teacher.courses.students.index', $course))
            ->assertOk()
            ->assertSeeText($matchingStudent->student_name)
            ->assertDontSeeText($otherGradeStudent->student_name)
            ->assertDontSeeText($otherClassStudent->student_name);
    }

    /**
     * 教員が他教員の担当授業に属する生徒一覧を閲覧できないことを確認する。
     *
     * 前提: 教員、授業など、検証に必要なテストデータを準備する。
     * 処理: `teacher.courses.students.index`へGETリクエストを送信する。
     * 期待結果: 権限不足としてHTTP 403で拒否されることを確認する。
     */
    public function test_teacher_cannot_view_another_teachers_course_students(): void
    {
        $teacher = Teacher::factory()->create();
        $otherTeacher = Teacher::factory()->create();
        $course = Course::factory()->create([
            'teacher_id' => $otherTeacher->id,
        ]);

        $this
            ->actingAs($teacher->user)
            ->get(route('teacher.courses.students.index', $course))
            ->assertForbidden();
    }
}
