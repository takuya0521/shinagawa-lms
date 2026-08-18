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
 * 全ロール共通Google Calendar管理画面の一覧表示と予定作成を確認する。
 */
final class GoogleCalendarIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 各テストでCalendar API設定を登録し、意図しない外部通信を禁止する。
     */
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.google_workspace.client_id', 'test-client-id');
        config()->set('services.google_workspace.client_secret', 'test-client-secret');
        config()->set('services.google_workspace.authorization_uri', 'https://accounts.google.com/o/oauth2/v2/auth');
        config()->set('services.google_workspace.token_uri', 'https://oauth2.googleapis.com/token');
        config()->set('services.google_workspace.revoke_uri', 'https://oauth2.googleapis.com/revoke');
        config()->set('services.google_workspace.scopes', $this->scopes());
        config()->set('services.google_calendar.api_uri', 'https://www.googleapis.com/calendar/v3');
        config()->set('services.google_calendar.scope', 'https://www.googleapis.com/auth/calendar');
        config()->set('services.google_calendar.page_size', 50);
        config()->set('services.google_calendar.days_ahead', 180);
        Cache::flush();
        Http::preventStrayRequests();
    }

    /**
     * 連携済み教員にカレンダーと予定が表示されることを確認する。
     *
     * 前提: 全管理スコープを持つ教員とCalendar APIの正常応答を準備する。
     * 処理: 共通Calendar画面へアクセスする。
     * 期待結果: カレンダー名、予定名、Meet参加導線が表示される。
     */
    public function test_connected_teacher_can_view_calendar_events(): void
    {
        Http::fake([
            'https://www.googleapis.com/calendar/v3/users/me/calendarList*' => Http::response([
                'items' => [[
                    'id' => 'primary',
                    'summary' => 'メインカレンダー',
                    'timeZone' => 'Asia/Tokyo',
                    'accessRole' => 'reader',
                    'primary' => true,
                ]],
            ]),
            'https://www.googleapis.com/calendar/v3/calendars/primary/events*' => Http::response([
                'items' => [[
                    'id' => 'event001',
                    'summary' => '英語オンライン授業',
                    'start' => ['dateTime' => '2026-08-10T10:00:00+09:00'],
                    'end' => ['dateTime' => '2026-08-10T11:00:00+09:00'],
                    'hangoutLink' => 'https://meet.google.com/abc-defg-hij',
                    'status' => 'confirmed',
                ]],
            ]),
        ]);
        $teacher = $this->user(UserRole::Teacher);
        GoogleDriveConnection::factory()->for($teacher)->create(['scope' => implode(' ', $this->scopes())]);

        $this->actingAs($teacher)
            ->get(route('google-workspace.calendar.content'))
            ->assertOk()
            ->assertSeeText('メインカレンダー')
            ->assertSeeText('英語オンライン授業')
            ->assertSeeText('Meetに参加');

        Http::assertSent(function (Request $request): bool {
            if (! str_contains($request->url(), '/users/me/calendarList')) {
                return false;
            }

            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return ($query['showDeleted'] ?? null) === 'false'
                && ($query['showHidden'] ?? null) === 'true';
        });

        Http::assertSent(function (Request $request): bool {
            if (! str_contains($request->url(), '/calendars/primary/events')) {
                return false;
            }

            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return ($query['showDeleted'] ?? null) === 'false'
                && ($query['singleEvents'] ?? null) === 'true';
        });
    }

    /**
     * 連携済み利用者でもCalendar画面本体の表示時にはGoogle APIを呼ばないことを確認する。
     *
     * 前提: 全管理スコープを持つ教員を準備し、外部通信を禁止する。
     * 処理: Google Calendarの通常画面URLへアクセスする。
     * 期待結果: HTTP 200と読み込み表示だけが返り、Google API通信は0件となる。
     */
    public function test_calendar_page_shell_does_not_wait_for_google_api(): void
    {
        $teacher = $this->user(UserRole::Teacher);
        GoogleDriveConnection::factory()->for($teacher)->create(['scope' => implode(' ', $this->scopes())]);

        $this->actingAs($teacher)
            ->get(route('google-workspace.calendar.index'))
            ->assertOk()
            ->assertSeeText('Google Calendarを読み込んでいます');

        Http::assertNothingSent();
    }

    /**
     * 表示月を移動してもカレンダー一覧を再取得しないことを確認する。
     *
     * 前提: メインカレンダーと空の予定一覧を返すCalendar APIを準備する。
     * 処理: 8月表示後に9月表示へ移動する。
     * 期待結果: calendarListはSWRキャッシュから再利用され、eventsだけが期間ごとに取得される。
     */
    public function test_calendar_catalog_is_reused_when_display_month_changes(): void
    {
        Http::fake([
            'https://www.googleapis.com/calendar/v3/users/me/calendarList*' => Http::response([
                'items' => [[
                    'id' => 'primary',
                    'summary' => 'メインカレンダー',
                    'timeZone' => 'Asia/Tokyo',
                    'accessRole' => 'owner',
                    'primary' => true,
                ]],
            ]),
            'https://www.googleapis.com/calendar/v3/calendars/primary/events*' => Http::response([
                'items' => [],
            ]),
        ]);
        $teacher = $this->user(UserRole::Teacher);
        GoogleDriveConnection::factory()->for($teacher)->create(['scope' => implode(' ', $this->scopes())]);

        $this->actingAs($teacher)
            ->get(route('google-workspace.calendar.content', ['anchor' => '2026-08-01']))
            ->assertOk();
        $this->get(route('google-workspace.calendar.content', ['anchor' => '2026-09-01']))
            ->assertOk();

        $recorded = Http::recorded();
        $calendarRequests = collect($recorded)
            ->filter(static fn (array $exchange): bool => str_contains(
                $exchange[0]->url(),
                '/users/me/calendarList',
            ));
        $eventRequests = collect($recorded)
            ->filter(static fn (array $exchange): bool => str_contains(
                $exchange[0]->url(),
                '/calendars/primary/events',
            ));

        $this->assertCount(1, $calendarRequests);
        $this->assertCount(2, $eventRequests);
    }

    /**
     * 未認証利用者がCalendar管理画面へ入れないことを確認する。
     *
     * 前提: LMSへログインしていない。
     * 処理: 共通Calendar画面へアクセスする。
     * 期待結果: ログイン画面へリダイレクトされる。
     */
    public function test_guest_cannot_view_google_calendar_management(): void
    {
        $this->get(route('google-workspace.calendar.index'))
            ->assertRedirect(route('login'));
    }

    /**
     * 管理操作で要求するOAuthスコープ一覧を返す。
     *
     * @return list<string> OAuthスコープ一覧
     */
    private function scopes(): array
    {
        return [
            'https://www.googleapis.com/auth/drive',
            'https://www.googleapis.com/auth/calendar',
            'https://www.googleapis.com/auth/chat.spaces',
            'https://www.googleapis.com/auth/chat.memberships',
            'https://www.googleapis.com/auth/chat.messages',
            'https://www.googleapis.com/auth/chat.messages.reactions',
            'https://www.googleapis.com/auth/chat.delete',
            'https://www.googleapis.com/auth/meetings.space.created',
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
