<?php

namespace Tests\Feature\Admin;

use App\Enums\MasterStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\ClassGroup;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ClassGroupManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_class_group_list(): void
    {
        $admin = $this->createAdmin();

        ClassGroup::factory()->create([
            'class_code' => 'TEST-AM',
            'class_name' => '一覧確認クラス',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.class-groups.index'));

        $response
            ->assertOk()
            ->assertSeeText('TEST-AM')
            ->assertSeeText('一覧確認クラス');
    }

    public function test_admin_can_create_class_group(): void
    {
        $admin = $this->createAdmin();

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.class-groups.store'), [
                'class_code' => 'new-class',
                'class_name' => '新規クラス',
                'description' => '新規登録確認',
                'status' => MasterStatus::Active->value,
            ]);

        $classGroup = ClassGroup::query()
            ->where('class_code', 'NEW-CLASS')
            ->firstOrFail();

        $response->assertRedirect(
            route('admin.class-groups.edit', $classGroup),
        );

        $this->assertDatabaseHas('class_groups', [
            'class_code' => 'NEW-CLASS',
            'class_name' => '新規クラス',
            'status' => MasterStatus::Active->value,
        ]);
    }

    public function test_duplicate_class_code_cannot_be_created(): void
    {
        $admin = $this->createAdmin();

        ClassGroup::factory()->create([
            'class_code' => 'DUPLICATE',
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.class-groups.create'))
            ->post(route('admin.class-groups.store'), [
                'class_code' => 'duplicate',
                'class_name' => '重複クラス',
                'description' => null,
                'status' => MasterStatus::Active->value,
            ]);

        $response
            ->assertRedirect(route('admin.class-groups.create'))
            ->assertSessionHasErrors('class_code');
    }

    public function test_admin_can_update_class_group(): void
    {
        $admin = $this->createAdmin();

        $classGroup = ClassGroup::factory()->create([
            'class_code' => 'BEFORE',
            'class_name' => '更新前クラス',
            'status' => MasterStatus::Active,
        ]);

        $response = $this
            ->actingAs($admin)
            ->put(
                route(
                    'admin.class-groups.update',
                    $classGroup,
                ),
                [
                    'class_code' => 'after',
                    'class_name' => '更新後クラス',
                    'description' => '更新後の説明',
                    'status' => MasterStatus::Inactive->value,
                ],
            );

        $response->assertRedirect(
            route('admin.class-groups.edit', $classGroup),
        );

        $this->assertDatabaseHas('class_groups', [
            'id' => $classGroup->id,
            'class_code' => 'AFTER',
            'class_name' => '更新後クラス',
            'status' => MasterStatus::Inactive->value,
        ]);
    }

    public function test_inactive_current_class_is_shown_on_student_edit(): void
    {
        $admin = $this->createAdmin();

        $classGroup = ClassGroup::factory()->create([
            'class_name' => '無効化済み所属クラス',
            'status' => MasterStatus::Inactive,
        ]);

        $student = Student::factory()->create([
            'class_group_id' => $classGroup->id,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.students.edit', $student));

        $response
            ->assertOk()
            ->assertSeeText('無効化済み所属クラス');
    }

    public function test_teacher_cannot_manage_class_groups(): void
    {
        $teacher = User::factory()->create([
            'role' => UserRole::Teacher,
            'status' => UserStatus::Active,
        ]);

        $response = $this
            ->actingAs($teacher)
            ->get(route('admin.class-groups.index'));

        $response->assertForbidden();
    }

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);
    }
}
