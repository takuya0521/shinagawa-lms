<?php

namespace App\Http\Controllers\GoogleWorkspace;

use App\Exceptions\GoogleCalendar\GoogleCalendarException;
use App\Exceptions\GoogleWorkspace\GoogleWorkspaceException;
use App\Http\Requests\GoogleWorkspace\CalendarEventRequest;
use App\Services\GoogleCalendar\GoogleCalendarEventService;
use App\Services\GoogleCalendar\Support\GoogleCalendarConfiguration;
use App\Services\GoogleWorkspace\GoogleWorkspaceService;
use App\Services\GoogleWorkspace\Support\GoogleWorkspaceCache;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Google Calendar予定の詳細表示・作成・更新・削除を担当する。
 */
final class CalendarEventController extends GoogleWorkspaceController
{
    /**
     * Google APIを待たず、予定詳細画面の共通枠だけを先に表示する。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  string  $calendarId  カレンダーID
     * @param  string  $eventId  予定ID
     * @param  GoogleCalendarConfiguration  $configuration  Calendar API設定
     * @param  GoogleWorkspaceService  $workspaceService  共通OAuth処理
     * @return View 予定詳細画面の軽量シェル
     */
    public function show(
        Request $request,
        string $calendarId,
        string $eventId,
        GoogleCalendarConfiguration $configuration,
        GoogleWorkspaceService $workspaceService,
    ): View {
        $user = $this->resolveUser($request);
        $state = $this->connectionState($user, $workspaceService, $configuration->requiredScopes());

        return view('google-workspace.calendar.show', [
            ...$state,
            'calendarId' => $calendarId,
            'eventId' => $eventId,
        ]);
    }

    /**
     * Google Calendarの予定詳細を取得して差し込み用HTMLとして返す。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  string  $calendarId  カレンダーID
     * @param  string  $eventId  予定ID
     * @param  GoogleCalendarEventService  $service  予定処理
     * @param  GoogleWorkspaceCache  $cache  SWR表示とバックグラウンド再検証に使用するキャッシュ
     * @return Response 予定詳細の非同期表示断片
     */
    public function showContent(
        Request $request,
        string $calendarId,
        string $eventId,
        GoogleCalendarEventService $service,
        GoogleWorkspaceCache $cache,
    ): Response {
        $user = $this->resolveUser($request);
        $this->prepareAsyncCache($request, $cache, $user, 'calendar');

        try {
            return $this->asyncView('google-workspace.calendar.show-content', [
                'event' => $service->event($user, $calendarId, $eventId),
                'calendarId' => $calendarId,
            ], $cache);
        } catch (ConnectionException|RequestException|GoogleCalendarException|GoogleWorkspaceException $exception) {
            return $this->asyncOperationFailure(
                $exception,
                $user,
                'google_calendar_event_show_async',
                'Google Calendarの予定詳細を取得できませんでした。',
            );
        }
    }

    /**
     * Google Calendarへ予定を作成する。
     *
     * @param  CalendarEventRequest  $request  検証済み予定入力
     * @param  GoogleCalendarEventService  $service  予定処理
     * @return RedirectResponse Calendar画面へのリダイレクト
     */
    public function store(
        CalendarEventRequest $request,
        GoogleCalendarEventService $service,
    ): RedirectResponse {
        $user = $this->resolveUser($request);

        try {
            $service->createEvent($user, $request->calendarId(), $request->attributes());
        } catch (ConnectionException|RequestException|GoogleCalendarException|GoogleWorkspaceException $exception) {
            return $this->operationFailure(
                $exception,
                $user,
                'google_calendar_event_create',
                'Google Calendarへ予定を作成できませんでした。',
            );
        }

        return redirect()
            ->route('google-workspace.calendar.index', ['calendar_id' => $request->calendarId()])
            ->with('success', 'Google Calendarへ予定を作成しました。');
    }

    /**
     * Google Calendarの予定を更新する。
     *
     * @param  CalendarEventRequest  $request  検証済み予定入力
     * @param  string  $calendarId  カレンダーID
     * @param  string  $eventId  予定ID
     * @param  GoogleCalendarEventService  $service  予定処理
     * @return RedirectResponse 予定詳細画面へのリダイレクト
     */
    public function update(
        CalendarEventRequest $request,
        string $calendarId,
        string $eventId,
        GoogleCalendarEventService $service,
    ): RedirectResponse {
        $user = $this->resolveUser($request);

        try {
            $service->updateEvent($user, $calendarId, $eventId, $request->attributes());
        } catch (ConnectionException|RequestException|GoogleCalendarException|GoogleWorkspaceException $exception) {
            return $this->operationFailure(
                $exception,
                $user,
                'google_calendar_event_update',
                'Google Calendarの予定を更新できませんでした。',
            );
        }

        return back()->with('success', 'Google Calendarの予定を更新しました。');
    }

    /**
     * Google Calendarの予定を削除する。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  string  $calendarId  カレンダーID
     * @param  string  $eventId  予定ID
     * @param  GoogleCalendarEventService  $service  予定処理
     * @return RedirectResponse Calendar画面へのリダイレクト
     */
    public function destroy(
        Request $request,
        string $calendarId,
        string $eventId,
        GoogleCalendarEventService $service,
    ): RedirectResponse {
        $user = $this->resolveUser($request);
        $requestedSendUpdates = $request->string('send_updates', 'all')->toString();
        $sendUpdates = in_array($requestedSendUpdates, ['all', 'externalOnly', 'none'], true)
            ? $requestedSendUpdates
            : 'all';

        try {
            $service->deleteEvent($user, $calendarId, $eventId, $sendUpdates);
        } catch (ConnectionException|RequestException|GoogleCalendarException|GoogleWorkspaceException $exception) {
            return $this->operationFailure(
                $exception,
                $user,
                'google_calendar_event_delete',
                'Google Calendarの予定を削除できませんでした。',
            );
        }

        return redirect()
            ->route('google-workspace.calendar.index', ['calendar_id' => $calendarId])
            ->with('success', 'Google Calendarの予定を削除しました。');
    }
}
