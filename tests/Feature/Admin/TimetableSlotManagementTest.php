<?php

namespace Tests\Feature\Admin;

use App\Enums\DayOfWeek;
use App\Enums\Grade;
use App\Enums\MasterStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TimetableSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TimetableSlotManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_timetable_list(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $slot = TimetableSlot::factory()->create([
            'day_of_week' => DayOfWeek::Monday,
            'period_no' => 1,
        ]);

        $slot->load([
            'course.subject',
            'course.classGroup',
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.timetable-slots.index'))
            ->assertOk()
            ->assertSeeText('時間割管理')
            ->assertSeeText(
                $slot->course->course_name,
            )
            ->assertSeeText(
                $slot->course->subject->subject_name,
            )
            ->assertSeeText(
                DayOfWeek::Monday->label(),
            );
    }

    public function test_admin_can_view_timetable_create_page(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $course = Course::factory()->create([
            'course_name' => '登録対象授業',
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.timetable-slots.create', [
                'course_id' => $course->id,
            ]))
            ->assertOk()
            ->assertSeeText('時間割登録')
            ->assertSeeText('登録対象授業')
            ->assertSeeText('曜日')
            ->assertSeeText('時限');
    }

    public function test_admin_can_view_timetable_edit_page(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $slot = TimetableSlot::factory()->create([
            'day_of_week' => DayOfWeek::Tuesday,
            'period_no' => 2,
        ]);

        $this
            ->actingAs($admin)
            ->get(
                route(
                    'admin.timetable-slots.edit',
                    $slot,
                ),
            )
            ->assertOk()
            ->assertSeeText('時間割編集')
            ->assertSeeText(
                $slot->course->course_name,
            );
    }

    public function test_admin_can_create_timetable_slot(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $course = Course::factory()->create([
            'academic_year' => 2026,
            'grade' => Grade::First,
            'course_name' => '数学Ⅰ',
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(
                route('admin.timetable-slots.store'),
                $this->slotPayload(
                    $course,
                    [
                        'day_of_week' => DayOfWeek::Monday->value,
                        'period_no' => 1,
                        'start_time' => '09:00',
                        'end_time' => '09:50',
                    ],
                ),
            );

        $response
            ->assertRedirect(
                route('admin.timetable-slots.index'),
            )
            ->assertSessionHas(
                'status',
                '時間割を登録しました。',
            );

        $slot = TimetableSlot::query()->firstOrFail();

        $this->assertSame(
            $course->id,
            $slot->course_id,
        );
        $this->assertSame(
            DayOfWeek::Monday,
            $slot->day_of_week,
        );
        $this->assertSame(1, $slot->period_no);
        $this->assertSame(
            '09:00',
            substr((string) $slot->start_time, 0, 5),
        );
        $this->assertSame(
            '09:50',
            substr((string) $slot->end_time, 0, 5),
        );
        $this->assertSame(
            MasterStatus::Active,
            $slot->status,
        );
    }

    public function test_same_course_day_and_period_cannot_be_registered_twice(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $course = Course::factory()->create();

        TimetableSlot::factory()->create([
            'course_id' => $course->id,
            'day_of_week' => DayOfWeek::Wednesday,
            'period_no' => 3,
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.timetable-slots.create'))
            ->post(
                route('admin.timetable-slots.store'),
                $this->slotPayload(
                    $course,
                    [
                        'day_of_week' => DayOfWeek::Wednesday->value,
                        'period_no' => 3,
                    ],
                ),
            );

        $response
            ->assertRedirect(
                route('admin.timetable-slots.create'),
            )
            ->assertSessionHasErrors('period_no');
    }

    public function test_same_class_day_and_period_cannot_have_multiple_active_courses(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $classGroup = ClassGroup::factory()->create();

        $firstCourse = Course::factory()->create([
            'academic_year' => 2026,
            'grade' => Grade::First,
            'class_group_id' => $classGroup->id,
            'subject_id' => Subject::factory(),
            'course_name' => '数学Ⅰ',
        ]);

        $secondCourse = Course::factory()->create([
            'academic_year' => 2026,
            'grade' => Grade::First,
            'class_group_id' => $classGroup->id,
            'subject_id' => Subject::factory(),
            'course_name' => '英語Ⅰ',
        ]);

        TimetableSlot::factory()->create([
            'course_id' => $firstCourse->id,
            'day_of_week' => DayOfWeek::Thursday,
            'period_no' => 2,
            'status' => MasterStatus::Active,
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.timetable-slots.create'))
            ->post(
                route('admin.timetable-slots.store'),
                $this->slotPayload(
                    $secondCourse,
                    [
                        'day_of_week' => DayOfWeek::Thursday->value,
                        'period_no' => 2,
                    ],
                ),
            );

        $response
            ->assertRedirect(
                route('admin.timetable-slots.create'),
            )
            ->assertSessionHasErrors('period_no');
    }

    public function test_same_teacher_cannot_teach_multiple_active_courses_at_same_time(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $teacher = Teacher::factory()->create();

        $firstCourse = Course::factory()->create([
            'academic_year' => 2026,
            'grade' => Grade::First,
            'class_group_id' => ClassGroup::factory(),
            'subject_id' => Subject::factory(),
            'teacher_id' => $teacher->id,
            'course_name' => '午前数学',
        ]);

        $secondCourse = Course::factory()->create([
            'academic_year' => 2026,
            'grade' => Grade::Second,
            'class_group_id' => ClassGroup::factory(),
            'subject_id' => Subject::factory(),
            'teacher_id' => $teacher->id,
            'course_name' => '午後数学',
        ]);

        TimetableSlot::factory()->create([
            'course_id' => $firstCourse->id,
            'day_of_week' => DayOfWeek::Friday,
            'period_no' => 4,
            'status' => MasterStatus::Active,
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.timetable-slots.create'))
            ->post(
                route('admin.timetable-slots.store'),
                $this->slotPayload(
                    $secondCourse,
                    [
                        'day_of_week' => DayOfWeek::Friday->value,
                        'period_no' => 4,
                    ],
                ),
            );

        $response
            ->assertRedirect(
                route('admin.timetable-slots.create'),
            )
            ->assertSessionHasErrors('period_no');
    }

    public function test_inactive_slot_does_not_block_another_course_in_same_class(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $classGroup = ClassGroup::factory()->create();

        $inactiveCourse = Course::factory()->create([
            'academic_year' => 2026,
            'grade' => Grade::First,
            'class_group_id' => $classGroup->id,
            'subject_id' => Subject::factory(),
            'course_name' => '旧授業',
        ]);

        $activeCourse = Course::factory()->create([
            'academic_year' => 2026,
            'grade' => Grade::First,
            'class_group_id' => $classGroup->id,
            'subject_id' => Subject::factory(),
            'course_name' => '新授業',
        ]);

        TimetableSlot::factory()->create([
            'course_id' => $inactiveCourse->id,
            'day_of_week' => DayOfWeek::Monday,
            'period_no' => 5,
            'status' => MasterStatus::Inactive,
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(
                route('admin.timetable-slots.store'),
                $this->slotPayload(
                    $activeCourse,
                    [
                        'day_of_week' => DayOfWeek::Monday->value,
                        'period_no' => 5,
                    ],
                ),
            );

        $response->assertRedirect(
            route('admin.timetable-slots.index'),
        );

        $this->assertDatabaseHas('timetable_slots', [
            'course_id' => $activeCourse->id,
            'day_of_week' => DayOfWeek::Monday->value,
            'period_no' => 5,
            'status' => MasterStatus::Active->value,
        ]);
    }

    public function test_inactive_course_cannot_be_selected_when_creating_slot(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $course = Course::factory()->create([
            'status' => MasterStatus::Inactive,
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.timetable-slots.create'))
            ->post(
                route('admin.timetable-slots.store'),
                $this->slotPayload($course),
            );

        $response->assertSessionHasErrors('course_id');
    }

    public function test_start_and_end_time_must_be_entered_together(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $course = Course::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.timetable-slots.create'))
            ->post(
                route('admin.timetable-slots.store'),
                $this->slotPayload(
                    $course,
                    [
                        'start_time' => '09:00',
                        'end_time' => null,
                    ],
                ),
            );

        $response->assertSessionHasErrors('end_time');
    }

    public function test_end_time_must_be_after_start_time(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $course = Course::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.timetable-slots.create'))
            ->post(
                route('admin.timetable-slots.store'),
                $this->slotPayload(
                    $course,
                    [
                        'start_time' => '10:00',
                        'end_time' => '09:50',
                    ],
                ),
            );

        $response->assertSessionHasErrors('end_time');
    }

    public function test_admin_can_update_timetable_slot(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $slot = TimetableSlot::factory()->create([
            'day_of_week' => DayOfWeek::Monday,
            'period_no' => 1,
            'status' => MasterStatus::Active,
        ]);

        $response = $this
            ->actingAs($admin)
            ->put(
                route(
                    'admin.timetable-slots.update',
                    $slot,
                ),
                $this->slotPayload(
                    $slot->course,
                    [
                        'day_of_week' => DayOfWeek::Tuesday->value,
                        'period_no' => 3,
                        'start_time' => '11:00',
                        'end_time' => '11:50',
                        'status' => MasterStatus::Inactive->value,
                    ],
                ),
            );

        $response
            ->assertRedirect(
                route('admin.timetable-slots.index'),
            )
            ->assertSessionHas(
                'status',
                '時間割を更新しました。',
            );

        $slot->refresh();

        $this->assertSame(
            DayOfWeek::Tuesday,
            $slot->day_of_week,
        );
        $this->assertSame(3, $slot->period_no);
        $this->assertSame(
            MasterStatus::Inactive,
            $slot->status,
        );
    }

    public function test_current_inactive_course_can_be_retained_when_updating_slot(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $slot = TimetableSlot::factory()->create([
            'day_of_week' => DayOfWeek::Wednesday,
            'period_no' => 2,
        ]);

        $slot->course->update([
            'status' => MasterStatus::Inactive,
        ]);

        $response = $this
            ->actingAs($admin)
            ->put(
                route(
                    'admin.timetable-slots.update',
                    $slot,
                ),
                $this->slotPayload(
                    $slot->course,
                    [
                        'day_of_week' => DayOfWeek::Wednesday->value,
                        'period_no' => 2,
                        'start_time' => '10:00',
                        'end_time' => '10:50',
                    ],
                ),
            );

        $response->assertRedirect(
            route('admin.timetable-slots.index'),
        );

        $this->assertDatabaseHas('timetable_slots', [
            'id' => $slot->id,
            'course_id' => $slot->course_id,
            'day_of_week' => DayOfWeek::Wednesday->value,
            'period_no' => 2,
        ]);
    }

    public function test_admin_can_filter_and_view_weekly_timetable(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $course = Course::factory()->create([
            'academic_year' => 2026,
            'grade' => Grade::Second,
            'course_name' => '週間表示対象',
        ]);

        TimetableSlot::factory()->create([
            'course_id' => $course->id,
            'day_of_week' => DayOfWeek::Thursday,
            'period_no' => 1,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.timetable-slots.index', [
                'academic_year' => 2026,
                'grade' => Grade::Second->value,
                'class_group_id' => $course->class_group_id,
            ]));

        $response
            ->assertOk()
            ->assertSeeText('週間時間割')
            ->assertSeeText('週間表示対象')
            ->assertSeeText(Grade::Second->label());
    }

    public function test_teacher_cannot_manage_timetable_slots(): void
    {
        $teacherUser = $this->createUser(
            UserRole::Teacher,
        );

        $slot = TimetableSlot::factory()->create();

        $payload = $this->slotPayload(
            $slot->course,
        );

        $this
            ->actingAs($teacherUser)
            ->get(route('admin.timetable-slots.index'))
            ->assertForbidden();

        $this
            ->actingAs($teacherUser)
            ->get(route('admin.timetable-slots.create'))
            ->assertForbidden();

        $this
            ->actingAs($teacherUser)
            ->post(
                route('admin.timetable-slots.store'),
                $payload,
            )
            ->assertForbidden();

        $this
            ->actingAs($teacherUser)
            ->get(
                route(
                    'admin.timetable-slots.edit',
                    $slot,
                ),
            )
            ->assertForbidden();

        $this
            ->actingAs($teacherUser)
            ->put(
                route(
                    'admin.timetable-slots.update',
                    $slot,
                ),
                $payload,
            )
            ->assertForbidden();
    }

    /**
     * 時間割登録で利用する有効な入力値を返す。
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function slotPayload(
        Course $course,
        array $overrides = [],
    ): array {
        return [
            'course_id' => $course->id,
            'day_of_week' => DayOfWeek::Monday->value,
            'period_no' => 1,
            'start_time' => null,
            'end_time' => null,
            'status' => MasterStatus::Active->value,
            ...$overrides,
        ];
    }

    private function createUser(
        UserRole $role,
    ): User {
        return User::factory()->create([
            'role' => $role,
            'status' => UserStatus::Active,
        ]);
    }
}
