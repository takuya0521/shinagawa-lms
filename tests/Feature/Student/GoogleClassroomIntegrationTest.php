<?php

namespace Tests\Feature\Student;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\GoogleDriveConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * 全ロール共通Google Classroom画面の一覧、詳細、課題、お知らせ取得を確認する。
 */
final class GoogleClassroomIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 各テストでClassroom API設定を登録し、意図しない外部通信を禁止する。
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->configureGoogleWorkspace();
        Cache::flush();
        Http::preventStrayRequests();
    }

    /**
     * 連携済み利用者でもClassroom画面本体の表示時にはGoogle APIを呼ばないことを確認する。
     *
     * 前提: Classroom参照権限を持つ連携済み教員を準備する。
     * 処理: Google Classroomの通常画面URLへアクセスする。
     * 期待結果: HTTP 200と読み込み表示だけが返り、Google API通信は0件となる。
     */
    public function test_classroom_page_shell_does_not_wait_for_google_api(): void
    {
        $teacher = $this->user(UserRole::Teacher);
        GoogleDriveConnection::factory()
            ->for($teacher)
            ->create(['scope' => implode(' ', $this->scopes())]);

        $this->actingAs($teacher)
            ->get(route('google-workspace.classroom.index'))
            ->assertOk()
            ->assertSeeText('Google Classroomを読み込んでいます');

        Http::assertNothingSent();
    }

    /**
     * 参加中のClassroomクラスを一覧表示し、状態条件をAPIへ渡すことを確認する。
     *
     * 前提: Classroom参照権限を持つ生徒とcourses.listの正常応答を準備する。
     * 処理: 利用中クラスの非同期表示ルートへアクセスする。
     * 期待結果: クラス名が表示され、ACTIVE条件付きでClassroom APIが呼ばれる。
     */
    public function test_classroom_content_lists_visible_courses(): void
    {
        Http::fake([
            'https://classroom.googleapis.com/v1/courses*' => Http::response([
                'courses' => [[
                    'id' => 'course001',
                    'name' => '英語コミュニケーションI',
                    'section' => '午前クラス',
                    'room' => '3F-301',
                    'courseState' => 'ACTIVE',
                    'alternateLink' => 'https://classroom.google.com/c/course001',
                    'updateTime' => '2026-08-10T01:00:00Z',
                ]],
            ]),
        ]);
        $student = $this->user(UserRole::Student);
        GoogleDriveConnection::factory()
            ->for($student)
            ->create(['scope' => implode(' ', $this->scopes())]);

        $this->actingAs($student)
            ->get(route('google-workspace.classroom.content', ['state' => 'ACTIVE']))
            ->assertOk()
            ->assertSeeText('英語コミュニケーションI')
            ->assertSeeText('午前クラス')
            ->assertSeeText('利用中');

        Http::assertSent(function (Request $request): bool {
            if (! str_starts_with($request->url(), 'https://classroom.googleapis.com/v1/courses')) {
                return false;
            }

            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return ($query['courseStates'] ?? null) === 'ACTIVE';
        });
    }

    /**
     * Classroomクラス詳細で課題とお知らせをまとめて表示できることを確認する。
     *
     * 前提: courses.get、courseWork.list、announcements.listの正常応答を準備する。
     * 処理: クラス詳細の非同期表示ルートへアクセスする。
     * 期待結果: クラス情報、課題、提出期限、お知らせが同じ画面へ表示される。
     */
    public function test_classroom_detail_shows_course_work_and_announcements(): void
    {
        Http::fake([
            'https://classroom.googleapis.com/v1/courses/course001' => Http::response([
                'id' => 'course001',
                'name' => '英語コミュニケーションI',
                'section' => '午前クラス',
                'description' => '英語の授業です。',
                'room' => '3F-301',
                'courseState' => 'ACTIVE',
                'alternateLink' => 'https://classroom.google.com/c/course001',
                'enrollmentCode' => 'abc123',
                'updateTime' => '2026-08-10T01:00:00Z',
            ]),
            'https://classroom.googleapis.com/v1/courses/course001/courseWork*' => Http::response([
                'courseWork' => [[
                    'id' => 'work001',
                    'title' => 'Unit 1 レポート',
                    'description' => '指定されたテーマで提出してください。',
                    'state' => 'PUBLISHED',
                    'workType' => 'ASSIGNMENT',
                    'alternateLink' => 'https://classroom.google.com/c/course001/a/work001',
                    'dueDate' => ['year' => 2026, 'month' => 8, 'day' => 20],
                    'dueTime' => ['hours' => 14, 'minutes' => 59, 'seconds' => 0],
                    'maxPoints' => 100,
                    'updateTime' => '2026-08-10T02:00:00Z',
                ]],
            ]),
            'https://classroom.googleapis.com/v1/courses/course001/announcements*' => Http::response([
                'announcements' => [[
                    'id' => 'announcement001',
                    'text' => '次回は教科書を持参してください。',
                    'state' => 'PUBLISHED',
                    'alternateLink' => 'https://classroom.google.com/c/course001/p/announcement001',
                    'updateTime' => '2026-08-10T03:00:00Z',
                ]],
            ]),
        ]);
        $teacher = $this->user(UserRole::Teacher);
        GoogleDriveConnection::factory()
            ->for($teacher)
            ->create(['scope' => implode(' ', $this->scopes())]);

        $this->actingAs($teacher)
            ->get(route('google-workspace.classroom.show-content', 'course001'))
            ->assertOk()
            ->assertSeeText('英語コミュニケーションI')
            ->assertSeeText('Unit 1 レポート')
            ->assertSeeText('2026/08/20 23:59')
            ->assertSeeText('100点')
            ->assertSeeText('次回は教科書を持参してください。');
    }

    /**
     * Classroom追加スコープがない既存連携では再承認を要求することを確認する。
     *
     * 前提: Drive権限だけを持つ既存連携ユーザーを準備する。
     * 処理: Google Classroom画面へアクセスする。
     * 期待結果: 追加権限の再承認が案内され、Google API通信は行われない。
     */
    public function test_classroom_page_requires_reauthorization_when_scopes_are_missing(): void
    {
        $admin = $this->user(UserRole::Admin);
        GoogleDriveConnection::factory()
            ->for($admin)
            ->create(['scope' => 'https://www.googleapis.com/auth/drive']);

        $this->actingAs($admin)
            ->get(route('google-workspace.classroom.index'))
            ->assertOk()
            ->assertSeeText('追加権限の再承認が必要です');

        Http::assertNothingSent();
    }

    /**
     * Classroom APIの403応答をAPI有効化と再承認の案内へ変換することを確認する。
     *
     * 前提: Classroom参照権限を持つ利用者と403応答を準備する。
     * 処理: Classroom一覧の非同期表示ルートへアクセスする。
     * 期待結果: Google CloudとClassroom権限を確認できるメッセージが表示される。
     */
    public function test_classroom_content_explains_forbidden_api_response(): void
    {
        Http::fake([
            'https://classroom.googleapis.com/v1/courses*' => Http::response([], 403),
        ]);
        $student = $this->user(UserRole::Student);
        GoogleDriveConnection::factory()
            ->for($student)
            ->create(['scope' => implode(' ', $this->scopes())]);

        $this->actingAs($student)
            ->get(route('google-workspace.classroom.content'))
            ->assertOk()
            ->assertHeader('X-Google-Workspace-Async-Error', '1')
            ->assertSeeText('Google Classroom APIの有効化、Classroom利用権限、OAuth再承認を確認してください。');
    }

    /**
     * Google WorkspaceとClassroom APIのテスト設定を登録する。
     */
    private function configureGoogleWorkspace(): void
    {
        config()->set('services.google_workspace.client_id', 'test-client-id');
        config()->set('services.google_workspace.client_secret', 'test-client-secret');
        config()->set('services.google_workspace.scopes', $this->scopes());
        config()->set('services.google_classroom.api_uri', 'https://classroom.googleapis.com/v1');
        config()->set('services.google_classroom.scopes', $this->scopes());
        config()->set('services.google_classroom.page_size', 50);
    }

    /**
     * Classroomの参照で要求するOAuthスコープ一覧を返す。
     *
     * @return list<string> OAuthスコープ一覧
     */
    private function scopes(): array
    {
        return [
            'https://www.googleapis.com/auth/classroom.courses.readonly',
            'https://www.googleapis.com/auth/classroom.coursework.me.readonly',
            'https://www.googleapis.com/auth/classroom.announcements.readonly',
        ];
    }

    /**
     * 指定ロールの有効なテストユーザーを作成する。
     *
     * @param  UserRole  $role  作成するロール
     * @return User 作成したユーザー
     */
    private function user(UserRole $role): User
    {
        return User::factory()->create(['role' => $role, 'status' => UserStatus::Active]);
    }
}
