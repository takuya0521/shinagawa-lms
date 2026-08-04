<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\OperationLog;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 操作ログ閲覧・CSV出力機能を確認するフィーチャーテスト。
 *
 * 一覧・詳細、検索条件、CSV出力、数式インジェクション対策、監査ログ、権限制御を検証する。
 */
final class OperationLogManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 管理者が操作ログの一覧と詳細を閲覧できることを確認する。
     *
     * 前提: 操作ログなど、検証に必要なテストデータを準備する。
     * 処理: `admin.operation-logs.index`へGETリクエスト、`admin.operation-logs.show`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な文言や対象データが画面に表示される、必要な内容がレスポンスに含まれることを確認する。
     */
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

    /**
     * 管理者が操作日時・操作者・操作種別等で操作ログを絞り込めることを確認する。
     *
     * 前提: 操作ログなど、検証に必要なテストデータを準備する。
     * 処理: `admin.operation-logs.index`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、必要な内容がレスポンスに含まれる、表示対象外の内容がレスポンスに含まれないことを確認する。
     */
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

    /**
     * PostgreSQLのJSONB詳細を大文字・小文字を区別せず検索できることを確認する。
     *
     * 前提: JSONB詳細に大文字を含む値を持つ操作ログを登録する。
     * 処理: 詳細値をすべて小文字にしたキーワードで操作ログ一覧を検索する。
     * 期待結果: JSONBをテキスト化したILIKE検索により、対象ログだけが表示されることを確認する。
     */
    public function test_admin_can_search_jsonb_detail_without_case_sensitivity(): void
    {
        $admin = $this->admin();

        $targetLog = OperationLog::factory()->create([
            'detail' => [
                'title' => 'PostgreSqlJsonbTarget',
            ],
        ]);

        $otherLog = OperationLog::factory()->create([
            'detail' => [
                'title' => 'OtherJsonbValue',
            ],
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.operation-logs.index', [
                'keyword' => 'postgresqljsonbtarget',
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

    /**
     * 開始日が終了日を超える操作ログ検索条件が拒否されることを確認する。
     *
     * 前提: 対象仕様を再現できる入力値と状態を準備する。
     * 処理: `admin.operation-logs.index`へGETリクエストを送信する。
     * 期待結果: 不正入力に対するバリデーションエラーが返ることを確認する。
     */
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

    /**
     * 管理者が操作ログをCSV出力でき、出力操作自体も監査ログへ記録されることを確認する。
     *
     * 前提: 操作ログなど、検証に必要なテストデータを準備する。
     * 処理: `admin.operation-logs.export`へGETリクエストを送信する。
     * 期待結果: HTTP 200の正常レスポンスとなる、データベースに期待する内容が保存される、取得値が期待値と一致することを確認する。
     */
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

    /**
     * CSV出力時に数式として解釈される可能性があるユーザー名を安全にエスケープすることを確認する。
     *
     * 前提: ユーザー、操作ログなど、検証に必要なテストデータを準備する。
     * 処理: `admin.operation-logs.export`へGETリクエストを送信する。
     * 期待結果: 実行結果と状態が設計上の期待値に一致することを確認する。
     */
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

    /**
     * 教員が操作ログ画面へアクセスできないことを確認する。
     *
     * 前提: 教員、操作ログなど、検証に必要なテストデータを準備する。
     * 処理: `admin.operation-logs.index`へGETリクエスト、`admin.operation-logs.show`へGETリクエスト、`admin.operation-logs.export`へGETリクエストを送信する。
     * 期待結果: 権限不足としてHTTP 403で拒否されることを確認する。
     */
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

    /**
     * テストで使用する有効な管理者ユーザーを作成して返す。
     */
    private function admin(): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);
    }
}
