<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Announcement\CreateAnnouncementAction;
use App\Actions\Announcement\DeleteAnnouncementAction;
use App\Actions\Announcement\UpdateAnnouncementAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AnnouncementIndexRequest;
use App\Http\Requests\Admin\StoreAnnouncementRequest;
use App\Http\Requests\Admin\UpdateAnnouncementRequest;
use App\Models\Announcement;
use App\Models\User;
use App\Queries\Admin\AnnouncementFormDataQuery;
use App\Queries\Admin\AnnouncementIndexDataQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 管理者向けお知らせ画面の表示と登録・更新・削除を制御する。
 */
final class AnnouncementController extends Controller
{
    /**
     * 管理者向けお知らせ一覧を表示する。
     *
     * @param  AnnouncementIndexRequest  $request  検証済み検索条件を含むリクエスト
     * @param  AnnouncementIndexDataQuery  $indexDataQuery  一覧画面の表示データ取得処理
     * @return View お知らせ一覧画面
     */
    public function index(
        AnnouncementIndexRequest $request,
        AnnouncementIndexDataQuery $indexDataQuery,
    ): View {
        return view(
            'admin.announcements.index',
            $indexDataQuery
                ->execute($request->filters())
                ->toViewData(),
        );
    }

    /**
     * お知らせ登録画面を表示する。
     *
     * @param  AnnouncementFormDataQuery  $formDataQuery  フォーム表示データ取得処理
     * @return View お知らせ登録画面
     */
    public function create(
        AnnouncementFormDataQuery $formDataQuery,
    ): View {
        return view(
            'admin.announcements.create',
            $formDataQuery->forCreate()->toViewData(),
        );
    }

    /**
     * お知らせを登録する。
     *
     * @param  StoreAnnouncementRequest  $request  検証済みお知らせ情報を含むリクエスト
     * @param  CreateAnnouncementAction  $createAnnouncementAction  お知らせ登録処理
     * @return RedirectResponse 登録後のお知らせ編集画面へのリダイレクト
     */
    public function store(
        StoreAnnouncementRequest $request,
        CreateAnnouncementAction $createAnnouncementAction,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $announcement = $createAnnouncementAction->execute(
            $request->announcementAttributes(),
            $request->targetAttributes(),
            $user,
            $request->ip(),
        );

        return redirect()
            ->route('admin.announcements.edit', $announcement)
            ->with('success', 'お知らせを登録しました。');
    }

    /**
     * お知らせ編集画面を表示する。
     *
     * @param  Announcement  $announcement  編集対象のお知らせ
     * @param  AnnouncementFormDataQuery  $formDataQuery  フォーム表示データ取得処理
     * @return View お知らせ編集画面
     */
    public function edit(
        Announcement $announcement,
        AnnouncementFormDataQuery $formDataQuery,
    ): View {
        return view(
            'admin.announcements.edit',
            $formDataQuery->forEdit($announcement)->toViewData(),
        );
    }

    /**
     * お知らせを更新する。
     *
     * @param  UpdateAnnouncementRequest  $request  検証済みお知らせ情報を含むリクエスト
     * @param  Announcement  $announcement  更新対象のお知らせ
     * @param  UpdateAnnouncementAction  $updateAnnouncementAction  お知らせ更新処理
     * @return RedirectResponse 更新後のお知らせ編集画面へのリダイレクト
     */
    public function update(
        UpdateAnnouncementRequest $request,
        Announcement $announcement,
        UpdateAnnouncementAction $updateAnnouncementAction,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $updateAnnouncementAction->execute(
            $announcement,
            $request->announcementAttributes(),
            $request->targetAttributes(),
            $user,
            $request->ip(),
        );

        return redirect()
            ->route('admin.announcements.edit', $announcement)
            ->with('success', 'お知らせを更新しました。');
    }

    /**
     * お知らせを論理削除する。
     *
     * @param  Request  $request  操作者と操作元IPアドレスを含むHTTPリクエスト
     * @param  Announcement  $announcement  削除対象のお知らせ
     * @param  DeleteAnnouncementAction  $deleteAnnouncementAction  お知らせ削除処理
     * @return RedirectResponse お知らせ一覧画面へのリダイレクト
     */
    public function destroy(
        Request $request,
        Announcement $announcement,
        DeleteAnnouncementAction $deleteAnnouncementAction,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $deleteAnnouncementAction->execute(
            $announcement,
            $user,
            $request->ip(),
        );

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', 'お知らせを削除しました。');
    }
}
