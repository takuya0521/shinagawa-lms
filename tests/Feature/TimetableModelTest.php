<?php

namespace Tests\Feature;

use App\Enums\DayOfWeek;
use App\Enums\Grade;
use App\Enums\MasterStatus;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TimetableSlot;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 授業・時間割モデルの関連、型変換、一意制約を確認するテスト。
 *
 * 科目・クラス・教員との関連、Enum変換、論理削除、授業・曜日・時限の重複防止を検証する。
 */
final class TimetableModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 授業が科目・クラス・担当教員に所属するリレーションを持つことを確認する。
     *
     * 前提: 科目、クラス、教員、授業など、検証に必要なテストデータを準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: 対象条件が真になることを確認する。
     */
    public function test_course_belongs_to_subject_class_group_and_teacher(): void
    {
        $subject = Subject::factory()->create();
        $classGroup = ClassGroup::factory()->create();
        $teacher = Teacher::factory()->create();

        $course = Course::factory()->create([
            'subject_id' => $subject->id,
            'class_group_id' => $classGroup->id,
            'teacher_id' => $teacher->id,
        ]);

        $this->assertTrue(
            $course->subject->is($subject),
        );

        $this->assertTrue(
            $course->classGroup->is($classGroup),
        );

        $this->assertTrue(
            $course->teacher->is($teacher),
        );
    }

    /**
     * 科目・クラス・教員から授業へ関連付けられることを確認する。
     *
     * 前提: 科目、クラス、教員、授業など、検証に必要なテストデータを準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: 対象条件が真になることを確認する。
     */
    public function test_subject_class_group_and_teacher_have_courses(): void
    {
        $subject = Subject::factory()->create();
        $classGroup = ClassGroup::factory()->create();
        $teacher = Teacher::factory()->create();

        $course = Course::factory()->create([
            'subject_id' => $subject->id,
            'class_group_id' => $classGroup->id,
            'teacher_id' => $teacher->id,
        ]);

        $this->assertTrue(
            $subject->courses->contains($course),
        );

        $this->assertTrue(
            $classGroup->courses->contains($course),
        );

        $this->assertTrue(
            $teacher->courses->contains($course),
        );
    }

    /**
     * 授業の対象学年がGrade Enumへキャストされることを確認する。
     *
     * 前提: 授業など、検証に必要なテストデータを準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: 取得値が期待値と一致することを確認する。
     */
    public function test_course_grade_is_cast_to_enum(): void
    {
        $course = Course::factory()->create([
            'grade' => Grade::Third,
        ]);

        $this->assertSame(
            Grade::Third,
            $course->grade,
        );
    }

    /**
     * 授業が複数の時間割枠を持つことを確認する。
     *
     * 前提: 授業、時間割枠など、検証に必要なテストデータを準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: 対象条件が真になることを確認する。
     */
    public function test_course_has_timetable_slots(): void
    {
        $course = Course::factory()->create();

        $slot = TimetableSlot::factory()->create([
            'course_id' => $course->id,
        ]);

        $this->assertTrue(
            $course->timetableSlots->contains($slot),
        );

        $this->assertTrue(
            $slot->course->is($course),
        );
    }

    /**
     * 時間割枠の曜日・時限等がEnumへキャストされることを確認する。
     *
     * 前提: 時間割枠など、検証に必要なテストデータを準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: 取得値が期待値と一致することを確認する。
     */
    public function test_timetable_slot_values_are_cast_to_enums(): void
    {
        $slot = TimetableSlot::factory()->create([
            'day_of_week' => DayOfWeek::Wednesday,
            'status' => MasterStatus::Inactive,
        ]);

        $this->assertSame(
            DayOfWeek::Wednesday,
            $slot->day_of_week,
        );

        $this->assertSame(
            MasterStatus::Inactive,
            $slot->status,
        );
    }

    /**
     * 授業削除時にレコードが論理削除されることを確認する。
     *
     * 前提: 授業など、検証に必要なテストデータを準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: 対象レコードが論理削除されることを確認する。
     */
    public function test_course_is_soft_deleted(): void
    {
        $course = Course::factory()->create();

        $course->delete();

        $this->assertSoftDeleted('courses', [
            'id' => $course->id,
        ]);
    }

    /**
     * 同じ科目・年度・学年・クラスの授業を重複登録できないことを確認する。
     *
     * 前提: 授業など、検証に必要なテストデータを準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: 想定した例外または制約違反が発生することを確認する。
     */
    public function test_duplicate_course_cannot_be_created_for_same_target(): void
    {
        $course = Course::factory()->create();

        $this->expectException(
            QueryException::class,
        );

        Course::factory()->create([
            'academic_year' => $course->academic_year,
            'class_group_id' => $course->class_group_id,
            'grade' => $course->grade,
            'subject_id' => $course->subject_id,
            'course_name' => $course->course_name,
        ]);
    }

    /**
     * 同じ授業に同一曜日・時限の時間割枠を重複登録できないことを確認する。
     *
     * 前提: 授業、時間割枠など、検証に必要なテストデータを準備する。
     * 処理: 対象のモデル、リレーション、スコープ、サービスまたは制約処理を実行する。
     * 期待結果: 想定した例外または制約違反が発生することを確認する。
     */
    public function test_same_course_cannot_have_duplicate_day_and_period(): void
    {
        $course = Course::factory()->create();

        TimetableSlot::factory()->create([
            'course_id' => $course->id,
            'day_of_week' => DayOfWeek::Monday,
            'period_no' => 1,
        ]);

        $this->expectException(
            QueryException::class,
        );

        TimetableSlot::factory()->create([
            'course_id' => $course->id,
            'day_of_week' => DayOfWeek::Monday,
            'period_no' => 1,
        ]);
    }
}
