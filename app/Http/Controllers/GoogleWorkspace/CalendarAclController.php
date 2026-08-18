<?php

namespace App\Http\Controllers\GoogleWorkspace;

use App\Exceptions\GoogleCalendar\GoogleCalendarException;
use App\Exceptions\GoogleWorkspace\GoogleWorkspaceException;
use App\Http\Requests\GoogleWorkspace\CalendarAclRequest;
use App\Services\GoogleCalendar\GoogleCalendarAclService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Google Calendarの共有ルール追加・削除を担当する。
 */
final class CalendarAclController extends GoogleWorkspaceController
{
    /**
     * カレンダー共有ルールを追加する。
     *
     * @param  CalendarAclRequest  $request  検証済み共有入力
     * @param  string  $calendarId  カレンダーID
     * @param  GoogleCalendarAclService  $service  Calendar共有処理
     * @return RedirectResponse Calendar画面へのリダイレクト
     */
    public function store(
        CalendarAclRequest $request,
        string $calendarId,
        GoogleCalendarAclService $service,
    ): RedirectResponse {
        $user = $this->resolveUser($request);

        try {
            $service->createAclRule(
                $user,
                $calendarId,
                $request->email(),
                $request->role(),
                $request->sendsNotification(),
            );
        } catch (ConnectionException|RequestException|GoogleCalendarException|GoogleWorkspaceException $exception) {
            return $this->operationFailure(
                $exception,
                $user,
                'google_calendar_acl_create',
                'Google Calendarの共有設定を追加できませんでした。',
            );
        }

        return back()->with('success', 'Google Calendarの共有設定を追加しました。');
    }

    /**
     * カレンダー共有ルールを削除する。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  string  $calendarId  カレンダーID
     * @param  string  $ruleId  ACLルールID
     * @param  GoogleCalendarAclService  $service  Calendar共有処理
     * @return RedirectResponse Calendar画面へのリダイレクト
     */
    public function destroy(
        Request $request,
        string $calendarId,
        string $ruleId,
        GoogleCalendarAclService $service,
    ): RedirectResponse {
        $user = $this->resolveUser($request);

        try {
            $service->deleteAclRule($user, $calendarId, $ruleId);
        } catch (ConnectionException|RequestException|GoogleCalendarException|GoogleWorkspaceException $exception) {
            return $this->operationFailure(
                $exception,
                $user,
                'google_calendar_acl_delete',
                'Google Calendarの共有設定を削除できませんでした。',
            );
        }

        return back()->with('success', 'Google Calendarの共有設定を削除しました。');
    }
}
