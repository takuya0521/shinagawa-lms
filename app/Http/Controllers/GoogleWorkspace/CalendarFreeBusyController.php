<?php

namespace App\Http\Controllers\GoogleWorkspace;

use App\Exceptions\GoogleCalendar\GoogleCalendarException;
use App\Exceptions\GoogleWorkspace\GoogleWorkspaceException;
use App\Http\Requests\GoogleWorkspace\CalendarFreeBusyRequest;
use App\Services\GoogleCalendar\GoogleCalendarFreeBusyService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;

/**
 * Google Calendarの複数カレンダー空き時間照会を担当する。
 */
final class CalendarFreeBusyController extends GoogleWorkspaceController
{
    /**
     * 複数カレンダーの予定あり時間帯を取得して一覧画面へ返す。
     *
     * @param  CalendarFreeBusyRequest  $request  検証済み空き時間条件
     * @param  GoogleCalendarFreeBusyService  $service  Calendar空き時間処理
     * @return RedirectResponse Calendar画面へのリダイレクト
     */
    public function store(
        CalendarFreeBusyRequest $request,
        GoogleCalendarFreeBusyService $service,
    ): RedirectResponse {
        $user = $this->resolveUser($request);

        try {
            $result = $service->freeBusy(
                $user,
                $request->calendarIds(),
                CarbonImmutable::parse($request->timeMin()),
                CarbonImmutable::parse($request->timeMax()),
            );
        } catch (ConnectionException|RequestException|GoogleCalendarException|GoogleWorkspaceException $exception) {
            return $this->operationFailure(
                $exception,
                $user,
                'google_calendar_free_busy',
                'Google Calendarの空き時間を確認できませんでした。',
            );
        }

        return back()
            ->with('google_calendar_free_busy', $result)
            ->with('success', 'Google Calendarの予定あり時間帯を取得しました。');
    }
}
