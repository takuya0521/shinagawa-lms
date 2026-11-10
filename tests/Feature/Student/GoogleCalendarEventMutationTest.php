<?php

namespace Tests\Feature\Student;

use App\Models\GoogleDriveConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class GoogleCalendarEventMutationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.google_workspace.client_id', 'test-client-id');
        config()->set('services.google_workspace.client_secret', 'test-client-secret');
        config()->set('services.google_workspace.scopes', ['https://www.googleapis.com/auth/calendar']);
        config()->set('services.google_calendar.api_uri', 'https://www.googleapis.com/calendar/v3');
        config()->set('services.google_calendar.scope', 'https://www.googleapis.com/auth/calendar');
        Cache::flush();
        Http::preventStrayRequests();
    }

    public static function rolesAndOperations(): array
    {
        $cases = [];
        foreach (['admin', 'teacher', 'student'] as $role) {
            foreach (['POST', 'PUT'] as $method) {
                $cases[$role.' '.$method] = [$role, $method];
            }
        }

        return $cases;
    }

    #[DataProvider('rolesAndOperations')]
    public function test_valid_event_reaches_google_with_normalized_payload(string $role, string $method): void
    {
        Http::fake(['https://www.googleapis.com/calendar/v3/*' => Http::response([
            'id' => 'fixture-event',
            'summary' => '回帰試験用予定',
            'start' => ['dateTime' => '2026-11-11T10:00:00+09:00'],
            'end' => ['dateTime' => '2026-11-11T10:30:00+09:00'],
        ])]);
        $this->signIn($role);

        $response = $this->from('/google-workspace/calendar')->call($method, $this->url($method), $this->payload());
        $response->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('success');
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === ($method === 'POST' ? 'POST' : 'PATCH')
            && str_contains($request->url(), '/calendars/fixture-calendar/events')
            && str_contains($request->url(), 'sendUpdates=none')
            && $request['summary'] === '回帰試験用予定'
            && $request['start']['dateTime'] === '2026-11-11T10:00:00+09:00'
            && $request['end']['dateTime'] === '2026-11-11T10:30:00+09:00'
            && $request['attendees'] === []
        );
    }

    #[DataProvider('rolesAndOperations')]
    public function test_invalid_event_returns_validation_errors_without_google_call(string $role, string $method): void
    {
        $this->signIn($role);
        $payload = $this->payload();
        $payload['title'] = '';
        $payload['end_at'] = '2026-11-11T09:30';

        $this->from('/google-workspace/calendar')->call($method, $this->url($method), $payload)
            ->assertRedirect('/google-workspace/calendar')
            ->assertSessionHasErrors(['title', 'end_at']);
        Http::assertNothingSent();
    }

    #[DataProvider('rolesAndOperations')]
    public function test_google_failure_returns_notice_instead_of_unhandled_error(string $role, string $method): void
    {
        Http::fake(['https://www.googleapis.com/calendar/v3/*' => Http::response([
            'error' => ['code' => 403, 'status' => 'PERMISSION_DENIED'],
        ], 403)]);
        $this->signIn($role);

        $this->from('/google-workspace/calendar')->call($method, $this->url($method), $this->payload())
            ->assertRedirect('/google-workspace/calendar')
            ->assertSessionHas('error', $method === 'POST'
                ? 'Google Calendarへ予定を作成できませんでした。'
                : 'Google Calendarの予定を更新できませんでした。');
        Http::assertSentCount(1);
    }

    private function signIn(string $role): void
    {
        $user = User::factory()->create(['role' => $role, 'status' => 'active']);
        GoogleDriveConnection::factory()->for($user)->create(['scope' => 'https://www.googleapis.com/auth/calendar']);
        $this->actingAs($user);
    }

    private function url(string $method): string
    {
        return $method === 'POST'
            ? '/google-workspace/calendar/events'
            : '/google-workspace/calendar/calendars/fixture-calendar/events/fixture-event';
    }

    private function payload(): array
    {
        return [
            'calendar_id' => 'fixture-calendar',
            'title' => '回帰試験用予定',
            'start_at' => '2026-11-11T10:00',
            'end_at' => '2026-11-11T10:30',
            'time_zone' => 'Asia/Tokyo',
            'recurrence' => 'none',
            'send_updates' => 'none',
            'attendees' => '',
        ];
    }
}
