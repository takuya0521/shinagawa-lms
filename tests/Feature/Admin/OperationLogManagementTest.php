<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\OperationLog;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OperationLogManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_operation_log_list_and_detail(): void
    {
        $admin = $this->admin();

        $operationLog = OperationLog::factory()->create([
            'user_id' => $admin->id,
            'action' => 'evaluation_correct',
            'target_table' => 'final_evaluations',
            'target_id' => 25,
            'detail' => [
                'correction_reason' => '入力内容を訂正したため',
                'ip_address' => '192.0.2.10',
            ],
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.operation-logs.index'))
            ->assertOk()
            ->assertSeeText('操作ログ')
            ->assertSeeText('成績修正')
            ->assertSeeText('入力内容を訂正したため')
            ->assertSee(
                'data-operation-log-id="'.$operationLog->id.'"',
                false,
            );

        $this
            ->actingAs($admin)
            ->get(
                route(
                    'admin.operation-logs.show',
                    $operationLog,
                ),
            )
            ->assertOk()
            ->assertSeeText('操作ログ詳細')
            ->assertSeeText('192.0.2.10')
            ->assertSeeText('入力内容を訂正したため');
    }

    public function test_admin_can_filter_operation_logs(): void
    {
        $admin = $this->admin();

        $targetLog = OperationLog::factory()->create([
            'user_id' => $admin->id,
            'action' => 'create_announcement',
            'target_table' => 'announcements',
            'target_id' => 101,
            'detail' => [
                'title' => '検索対象のお知らせ',
            ],
            'created_at' => now()->subDay(),
        ]);

        $otherLog = OperationLog::factory()->create([
            'action' => 'update_interview',
            'target_table' => 'interview_records',
            'target_id' => 202,
            'detail' => [
                'interview_type' => '対象外面談',
            ],
            'created_at' => now()->subDay(),
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.operation-logs.index', [
                'date_from' => now()
                    ->subDays(2)
                    ->format('Y-m-d'),
                'date_to' => now()->format('Y-m-d'),
                'user_id' => $admin->id,
                'action' => 'create_announcement',
                'target_table' => 'announcements',
                'target_id' => 101,
                'keyword' => '検索対象',
            ]))
            ->assertOk()
            ->assertSee(
                'data-operation-log-id="'.$targetLog->id.'"',
                false,
            )
            ->assertDontSee(
                'data-operation-log-id="'.$otherLog->id.'"',
                false,
            );
    }

    public function test_invalid_operation_log_date_range_is_rejected(): void
    {
        $this
            ->actingAs($this->admin())
            ->get(route('admin.operation-logs.index', [
                'date_from' => '2026-07-30',
                'date_to' => '2026-07-01',
            ]))
            ->assertSessionHasErrors('date_to');
    }

    public function test_admin_can_export_operation_logs_and_export_itself_is_logged(): void
    {
        $admin = $this->admin();

        $operationLog = OperationLog::factory()->create([
            'user_id' => $admin->id,
            'action' => 'assign_course_teacher',
            'target_table' => 'courses',
            'target_id' => 15,
            'detail' => [
                'course_name' => '現代文',
            ],
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.operation-logs.export'));

        $response
            ->assertOk()
            ->assertHeader(
                'content-type',
                'text/csv; charset=UTF-8',
            );

        $content = $response->streamedContent();

        $this->assertStringStartsWith(
            "\xEF\xBB\xBF",
            $content,
        );
        $this->assertStringContainsString(
            '担当教員設定',
            $content,
        );
        $this->assertStringContainsString(
            '現代文',
            $content,
        );
        $this->assertStringNotContainsString(
            'export_operation_logs',
            $content,
        );

        $this->assertDatabaseHas('operation_logs', [
            'user_id' => $admin->id,
            'action' => 'export_operation_logs',
            'target_table' => 'operation_logs',
        ]);

        $exportLog = OperationLog::query()
            ->where(
                'action',
                'export_operation_logs',
            )
            ->firstOrFail();

        $this->assertSame(
            1,
            $exportLog->detail['export_count'],
        );
        $this->assertSame(
            $operationLog->id,
            $exportLog->detail['max_exported_id'],
        );
    }

    public function test_csv_export_escapes_formula_like_user_name(): void
    {
        $admin = User::factory()->create([
            'name' => '=HYPERLINK("https://example.test")',
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);

        OperationLog::factory()->create([
            'user_id' => $admin->id,
            'action' => 'change_password',
            'target_table' => 'users',
            'target_id' => $admin->id,
        ]);

        $content = $this
            ->actingAs($admin)
            ->get(
                route(
                    'admin.operation-logs.export',
                ),
            )
            ->streamedContent();

        $this->assertStringContainsString(
            '\'=HYPERLINK',
            $content,
        );
    }

    public function test_teacher_cannot_access_operation_logs(): void
    {
        $teacher = Teacher::factory()->create();
        $operationLog = OperationLog::factory()->create();

        $this
            ->actingAs($teacher->user)
            ->get(route('admin.operation-logs.index'))
            ->assertForbidden();

        $this
            ->actingAs($teacher->user)
            ->get(
                route(
                    'admin.operation-logs.show',
                    $operationLog,
                ),
            )
            ->assertForbidden();

        $this
            ->actingAs($teacher->user)
            ->get(route('admin.operation-logs.export'))
            ->assertForbidden();
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);
    }
}
