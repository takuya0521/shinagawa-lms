<?php

namespace App\Http\Controllers\GoogleWorkspace;

use App\Exceptions\GoogleCalendar\GoogleCalendarException;
use App\Exceptions\GoogleWorkspace\GoogleWorkspaceException;
use App\Http\Requests\GoogleWorkspace\CalendarIndexRequest;
use App\Http\Requests\GoogleWorkspace\CalendarRequest;
use App\Services\GoogleCalendar\GoogleCalendarAclService;
use App\Services\GoogleCalendar\GoogleCalendarCatalogService;
use App\Services\GoogleCalendar\GoogleCalendarOverviewService;
use App\Services\GoogleCalendar\Support\GoogleCalendarConfiguration;
use App\Services\GoogleWorkspace\GoogleWorkspaceService;
use App\Services\GoogleWorkspace\Support\GoogleWorkspaceCache;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Google Calendarの一覧表示とカレンダー自体の作成・更新・削除を担当する。
 */
final class CalendarController extends GoogleWorkspaceController
{
    /**
     * Google APIを待たず、Calendar画面の共通枠と連携状態だけを先に表示する。
     *
     * @param  CalendarIndexRequest  $request  検証済み一覧条件
     * @param  GoogleCalendarCatalogService  $catalogService  Calendar設定・スコープ参照処理
     * @param  GoogleWorkspaceService  $workspaceService  共通OAuth処理
     * @return View Google Calendar管理画面の軽量シェル
     */
    public function index(
        CalendarIndexRequest $request,
        GoogleCalendarCatalogService $catalogService,
        GoogleWorkspaceService $workspaceService,
    ): View {
        $user = $this->resolveUser($request);
        $state = $this->connectionState($user, $workspaceService, $catalogService->requiredScopes());
        $anchor = $request->anchorDate() !== null
            ? CarbonImmutable::parse($request->anchorDate())
            : CarbonImmutable::now((string) config('app.timezone'));

        return view('google-workspace.calendar.index', [
            ...$state,
            'calendarId' => $request->calendarId(),
            'keyword' => $request->keyword(),
            'displayMode' => $request->displayMode(),
            'anchor' => $anchor,
        ]);
    }

    /**
     * Calendar一覧と予定を取得し、画面内へ差し込むHTMLとして返す。
     * 共有ルールは管理パネルを開いた場合だけ取得し、通常表示のAPI待ちを増やさない。
     *
     * @param  CalendarIndexRequest  $request  検証済み一覧条件
     * @param  GoogleCalendarOverviewService  $overviewService  カレンダーと予定の並列取得処理
     * @param  GoogleCalendarCatalogService  $catalogService  Calendar設定・スコープ参照処理
     * @param  GoogleCalendarAclService  $aclService  共有ルール参照処理
     * @param  GoogleCalendarConfiguration  $configuration  Calendar取得期間設定
     * @param  GoogleWorkspaceService  $workspaceService  共通OAuth処理
     * @param  GoogleWorkspaceCache  $cache  Google API結果のSWRキャッシュ
     * @return Response Calendar一覧の非同期表示断片
     */
    public function indexContent(
        CalendarIndexRequest $request,
        GoogleCalendarOverviewService $overviewService,
        GoogleCalendarCatalogService $catalogService,
        GoogleCalendarAclService $aclService,
        GoogleCalendarConfiguration $configuration,
        GoogleWorkspaceService $workspaceService,
        GoogleWorkspaceCache $cache,
    ): Response {
        $user = $this->resolveUser($request);
        $state = $this->connectionState($user, $workspaceService, $catalogService->requiredScopes());
        $calendars = collect();
        $events = collect();
        $aclRules = collect();
        $calendarError = null;
        $calendarId = $request->calendarId();
        $displayMode = $request->displayMode();
        $anchor = $request->anchorDate() !== null
            ? CarbonImmutable::parse($request->anchorDate())
            : CarbonImmutable::now((string) config('app.timezone'));
        [$from, $to, $displayMode] = $this->displayPeriod(
            $request,
            $anchor,
            $displayMode,
            $configuration->daysAhead(),
        );

        if (! $state['isConfigured'] || ! $state['isAuthorized']) {
            return $this->asyncAuthorizationRequired();
        }

        $this->prepareAsyncCache($request, $cache, $user, 'calendar');

        try {
            $overview = $overviewService->overview(
                $user,
                $calendarId,
                $from,
                $to,
                $request->keyword(),
            );
            $calendars = $overview->calendars;
            $events = $overview->events;
            $selected = $calendars->first(
                static fn ($calendar): bool => $calendar->id === $calendarId,
            );

            if ($request->showsManagement() && $selected !== null && $selected->canManage()) {
                $aclRules = $cache->remember(
                    $user,
                    'calendar',
                    'acl:'.$calendarId,
                    60,
                    fn (): Collection => $aclService->aclRules($user, $calendarId),
                );
            }
        } catch (ConnectionException|RequestException|GoogleCalendarException|GoogleWorkspaceException $exception) {
            if ($request->boolean('swr_refresh')) {
                return $this->asyncOperationFailure(
                    $exception,
                    $user,
                    'google_calendar_index_swr',
                    'Google Calendarの最新情報を取得できませんでした。',
                );
            }

            $this->asyncOperationFailure(
                $exception,
                $user,
                'google_calendar_index_async',
                'Google Calendarの情報を取得できませんでした。',
            );
            $calendarError = 'Google Calendarの情報を取得できませんでした。時間をおいて、もう一度お試しください。';
        }

        return $this->asyncView('google-workspace.calendar.content', [
            'calendars' => $calendars,
            'events' => $events,
            'eventsByDate' => $events->groupBy(
                static fn ($event): string => $event->startsAt->format('Y-m-d'),
            ),
            'calendarDays' => $this->calendarDays($from, $to),
            'aclRules' => $aclRules,
            'calendarId' => $calendarId,
            'keyword' => $request->keyword(),
            'dateFrom' => $request->dateFrom(),
            'dateTo' => $request->dateTo(),
            'displayMode' => $displayMode,
            'anchor' => $anchor,
            'previousAnchor' => $anchor->subMonthNoOverflow()->format('Y-m-d'),
            'nextAnchor' => $anchor->addMonthNoOverflow()->format('Y-m-d'),
            'showsManagement' => $request->showsManagement(),
            'calendarError' => $calendarError,
            'freeBusy' => session('google_calendar_free_busy', []),
        ], $cache);
    }

    /**
     * 新しいカレンダーを作成する。
     *
     * @param  CalendarRequest  $request  検証済みカレンダー入力
     * @param  GoogleCalendarCatalogService  $service  カレンダー更新処理
     * @return RedirectResponse Calendar画面へのリダイレクト
     */
    public function store(CalendarRequest $request, GoogleCalendarCatalogService $service): RedirectResponse
    {
        $user = $this->resolveUser($request);

        try {
            $service->createCalendar(
                $user,
                $request->summary(),
                $request->description(),
                $request->timeZone(),
            );
        } catch (ConnectionException|RequestException|GoogleCalendarException|GoogleWorkspaceException $exception) {
            return $this->operationFailure(
                $exception,
                $user,
                'google_calendar_create',
                'Google Calendarを作成できませんでした。',
            );
        }

        return back()->with('success', 'Google Calendarを作成しました。');
    }

    /**
     * カレンダーの名称・説明・タイムゾーンを更新する。
     *
     * @param  CalendarRequest  $request  検証済みカレンダー入力
     * @param  string  $calendarId  カレンダーID
     * @param  GoogleCalendarCatalogService  $service  カレンダー更新処理
     * @return RedirectResponse Calendar画面へのリダイレクト
     */
    public function update(
        CalendarRequest $request,
        string $calendarId,
        GoogleCalendarCatalogService $service,
    ): RedirectResponse {
        $user = $this->resolveUser($request);

        try {
            $service->updateCalendar(
                $user,
                $calendarId,
                $request->summary(),
                $request->description(),
                $request->timeZone(),
            );
        } catch (ConnectionException|RequestException|GoogleCalendarException|GoogleWorkspaceException $exception) {
            return $this->operationFailure(
                $exception,
                $user,
                'google_calendar_update',
                'Google Calendarを更新できませんでした。',
            );
        }

        return back()->with('success', 'Google Calendarを更新しました。');
    }

    /**
     * 所有するサブカレンダーを削除する。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  string  $calendarId  カレンダーID
     * @param  GoogleCalendarCatalogService  $service  カレンダー更新処理
     * @return RedirectResponse Calendar画面へのリダイレクト
     */
    public function destroy(
        Request $request,
        string $calendarId,
        GoogleCalendarCatalogService $service,
    ): RedirectResponse {
        $user = $this->resolveUser($request);

        try {
            $service->deleteCalendar($user, $calendarId);
        } catch (ConnectionException|RequestException|GoogleCalendarException|GoogleWorkspaceException $exception) {
            return $this->operationFailure(
                $exception,
                $user,
                'google_calendar_delete',
                'Google Calendarを削除できませんでした。',
            );
        }

        return redirect()
            ->route('google-workspace.calendar.index')
            ->with('success', 'Google Calendarを削除しました。');
    }

    /**
     * 表示形式と入力期間からGoogle APIへ渡す開始・終了日時を決定する。
     *
     * @param  CalendarIndexRequest  $request  検証済み一覧条件
     * @param  CarbonImmutable  $anchor  月送り・期間計算の基準日
     * @param  string  $displayMode  monthまたはagenda
     * @param  int  $daysAhead  予定一覧表示時の最大将来日数
     * @return array{0: CarbonImmutable, 1: CarbonImmutable, 2: string} 開始日時、終了日時、確定表示形式
     */
    private function displayPeriod(
        CalendarIndexRequest $request,
        CarbonImmutable $anchor,
        string $displayMode,
        int $daysAhead,
    ): array {
        if ($request->dateFrom() !== null || $request->dateTo() !== null) {
            $from = $request->dateFrom() !== null
                ? CarbonImmutable::parse($request->dateFrom())->startOfDay()
                : $anchor->startOfDay();
            $to = $request->dateTo() !== null
                ? CarbonImmutable::parse($request->dateTo())->endOfDay()
                : $from->addDays($daysAhead)->endOfDay();

            return [$from, $to, 'agenda'];
        }

        if ($displayMode === 'agenda') {
            $from = $anchor->startOfDay();

            return [$from, $from->addDays($daysAhead)->endOfDay(), 'agenda'];
        }

        return [
            $anchor->startOfMonth()->startOfWeek(CarbonInterface::SUNDAY),
            $anchor->endOfMonth()->endOfWeek(CarbonInterface::SATURDAY),
            'month',
        ];
    }

    /**
     * 月表示の7列グリッドへ渡す日付一覧を生成する。
     *
     * @param  CarbonImmutable  $from  グリッド先頭日
     * @param  CarbonImmutable  $to  グリッド末尾日
     * @return Collection<int, CarbonImmutable> 先頭日から末尾日までの日付一覧
     */
    private function calendarDays(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return collect(range(0, (int) $from->diffInDays($to)))
            ->map(static fn (int|float $offset): CarbonImmutable => $from->addDays((int) $offset));
    }
}
