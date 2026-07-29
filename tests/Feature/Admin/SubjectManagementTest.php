<?php

namespace Tests\Feature\Admin;

use App\Enums\MasterStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SubjectManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_subject_list(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $subject = Subject::factory()->create([
            'subject_code' => 'MATH',
            'subject_name' => '数学',
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.subjects.index'))
            ->assertOk()
            ->assertSeeText(
                $subject->subject_code,
            )
            ->assertSeeText(
                $subject->subject_name,
            );
    }

    public function test_admin_can_view_subject_create_page(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $this
            ->actingAs($admin)
            ->get(route('admin.subjects.create'))
            ->assertOk()
            ->assertSeeText('科目登録')
            ->assertSeeText('科目コード')
            ->assertSeeText('科目名');
    }

    public function test_admin_can_create_subject(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.subjects.store'), [
                'subject_code' => ' eng ',
                'subject_name' => ' 英語 ',
                'status' => MasterStatus::Active->value,
            ]);

        $response
            ->assertRedirect(
                route('admin.subjects.index'),
            )
            ->assertSessionHas(
                'status',
                '科目を登録しました。',
            );

        $this->assertDatabaseHas('subjects', [
            'subject_code' => 'ENG',
            'subject_name' => '英語',
            'status' => MasterStatus::Active->value,
        ]);
    }

    public function test_invalid_subject_code_cannot_be_created(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.subjects.create'))
            ->post(route('admin.subjects.store'), [
                'subject_code' => '科目01',
                'subject_name' => '国語',
                'status' => MasterStatus::Active->value,
            ]);

        $response
            ->assertRedirect(
                route('admin.subjects.create'),
            )
            ->assertSessionHasErrors(
                'subject_code',
            );

        $this->assertDatabaseMissing('subjects', [
            'subject_code' => '科目01',
        ]);
    }

    public function test_duplicate_subject_code_cannot_be_created(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        Subject::factory()->create([
            'subject_code' => 'JAPANESE',
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.subjects.create'))
            ->post(route('admin.subjects.store'), [
                'subject_code' => 'japanese',
                'subject_name' => '国語',
                'status' => MasterStatus::Active->value,
            ]);

        $response
            ->assertRedirect(
                route('admin.subjects.create'),
            )
            ->assertSessionHasErrors(
                'subject_code',
            );
    }

    public function test_admin_can_view_subject_edit_page(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $subject = Subject::factory()->create([
            'subject_code' => 'SCIENCE',
            'subject_name' => '理科',
        ]);

        $this
            ->actingAs($admin)
            ->get(
                route(
                    'admin.subjects.edit',
                    $subject,
                ),
            )
            ->assertOk()
            ->assertSeeText('科目編集')
            ->assertSee(
                $subject->subject_code,
            )
            ->assertSee(
                $subject->subject_name,
            );
    }

    public function test_admin_can_update_subject(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $subject = Subject::factory()->create([
            'subject_code' => 'SCIENCE',
            'subject_name' => '理科',
            'status' => MasterStatus::Active,
        ]);

        $response = $this
            ->actingAs($admin)
            ->put(
                route(
                    'admin.subjects.update',
                    $subject,
                ),
                [
                    'subject_code' => ' science-01 ',
                    'subject_name' => ' 科学 ',
                    'status' => MasterStatus::Inactive->value,
                ],
            );

        $response
            ->assertRedirect(
                route('admin.subjects.index'),
            )
            ->assertSessionHas(
                'status',
                '科目を更新しました。',
            );

        $this->assertDatabaseHas('subjects', [
            'id' => $subject->id,
            'subject_code' => 'SCIENCE-01',
            'subject_name' => '科学',
            'status' => MasterStatus::Inactive->value,
        ]);
    }

    public function test_admin_can_update_subject_without_changing_code(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $subject = Subject::factory()->create([
            'subject_code' => 'MATH',
            'subject_name' => '数学',
            'status' => MasterStatus::Active,
        ]);

        $response = $this
            ->actingAs($admin)
            ->put(
                route(
                    'admin.subjects.update',
                    $subject,
                ),
                [
                    'subject_code' => 'MATH',
                    'subject_name' => '数学Ⅰ',
                    'status' => MasterStatus::Active->value,
                ],
            );

        $response->assertRedirect(
            route('admin.subjects.index'),
        );

        $this->assertDatabaseHas('subjects', [
            'id' => $subject->id,
            'subject_code' => 'MATH',
            'subject_name' => '数学Ⅰ',
        ]);
    }

    public function test_admin_can_search_subjects_by_keyword(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $matchingSubject = Subject::factory()->create([
            'subject_code' => 'MATH',
            'subject_name' => '数学',
        ]);

        $otherSubject = Subject::factory()->create([
            'subject_code' => 'ENGLISH',
            'subject_name' => '英語',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.subjects.index', [
                'keyword' => '数学',
            ]));

        $response
            ->assertOk()
            ->assertSeeText(
                $matchingSubject->subject_name,
            )
            ->assertDontSeeText(
                $otherSubject->subject_name,
            );
    }

    public function test_admin_can_search_subjects_by_code(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $matchingSubject = Subject::factory()->create([
            'subject_code' => 'MATH-01',
            'subject_name' => '数学',
        ]);

        $otherSubject = Subject::factory()->create([
            'subject_code' => 'ENGLISH-01',
            'subject_name' => '英語',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.subjects.index', [
                'keyword' => 'MATH',
            ]));

        $response
            ->assertOk()
            ->assertSeeText(
                $matchingSubject->subject_code,
            )
            ->assertDontSeeText(
                $otherSubject->subject_code,
            );
    }

    public function test_admin_can_filter_subjects_by_status(): void
    {
        $admin = $this->createUser(
            UserRole::Admin,
        );

        $activeSubject = Subject::factory()->create([
            'subject_code' => 'ACTIVE-SUBJECT',
            'subject_name' => '有効科目',
            'status' => MasterStatus::Active,
        ]);

        $inactiveSubject = Subject::factory()->create([
            'subject_code' => 'INACTIVE-SUBJECT',
            'subject_name' => '無効科目',
            'status' => MasterStatus::Inactive,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.subjects.index', [
                'status' => MasterStatus::Inactive->value,
            ]));

        $response
            ->assertOk()
            ->assertSeeText(
                $inactiveSubject->subject_name,
            )
            ->assertDontSeeText(
                $activeSubject->subject_name,
            );
    }

    public function test_teacher_cannot_manage_subjects(): void
    {
        $teacher = $this->createUser(
            UserRole::Teacher,
        );

        $subject = Subject::factory()->create();

        $this
            ->actingAs($teacher)
            ->get(route('admin.subjects.index'))
            ->assertForbidden();

        $this
            ->actingAs($teacher)
            ->get(route('admin.subjects.create'))
            ->assertForbidden();

        $this
            ->actingAs($teacher)
            ->post(route('admin.subjects.store'), [
                'subject_code' => 'MATH',
                'subject_name' => '数学',
                'status' => MasterStatus::Active->value,
            ])
            ->assertForbidden();

        $this
            ->actingAs($teacher)
            ->get(
                route(
                    'admin.subjects.edit',
                    $subject,
                ),
            )
            ->assertForbidden();

        $this
            ->actingAs($teacher)
            ->put(
                route(
                    'admin.subjects.update',
                    $subject,
                ),
                [
                    'subject_code' => 'MATH',
                    'subject_name' => '数学',
                    'status' => MasterStatus::Active->value,
                ],
            )
            ->assertForbidden();
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
