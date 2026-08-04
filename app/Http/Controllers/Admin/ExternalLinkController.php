<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ExternalLink\CreateExternalLinkAction;
use App\Actions\ExternalLink\UpdateExternalLinkAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExternalLinkIndexRequest;
use App\Http\Requests\Admin\StoreExternalLinkRequest;
use App\Http\Requests\Admin\UpdateExternalLinkRequest;
use App\Models\ExternalLink;
use App\Models\User;
use App\Queries\Admin\ExternalLinkFormDataQuery;
use App\Queries\Admin\ExternalLinkIndexDataQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * 管理者向け外部リンク画面の表示と登録・更新を制御する。
 */
final class ExternalLinkController extends Controller
{
    /**
     * 外部リンク管理一覧を表示する。
     *
     * @param  ExternalLinkIndexRequest  $request  検証済み検索条件を含むリクエスト
     * @param  ExternalLinkIndexDataQuery  $indexDataQuery  一覧画面の表示データ取得処理
     * @return View 外部リンク一覧画面
     */
    public function index(
        ExternalLinkIndexRequest $request,
        ExternalLinkIndexDataQuery $indexDataQuery,
    ): View {
        return view(
            'admin.external-links.index',
            $indexDataQuery->execute(
                $request->keyword(),
                $request->linkType(),
                $request->scopeType(),
                $request->status(),
            )->toViewData(),
        );
    }

    /**
     * 外部リンク登録画面を表示する。
     *
     * @param  ExternalLinkFormDataQuery  $formDataQuery  フォーム表示データ取得処理
     * @return View 外部リンク登録画面
     */
    public function create(
        ExternalLinkFormDataQuery $formDataQuery,
    ): View {
        return view('admin.external-links.create', [
            ...$formDataQuery->execute()->toViewData(),
            'externalLink' => new ExternalLink,
        ]);
    }

    /**
     * 外部リンクを登録する。
     *
     * @param  StoreExternalLinkRequest  $request  検証済み外部リンク情報を含むリクエスト
     * @param  CreateExternalLinkAction  $createExternalLinkAction  外部リンク登録処理
     * @return RedirectResponse 登録後の外部リンク編集画面へのリダイレクト
     */
    public function store(
        StoreExternalLinkRequest $request,
        CreateExternalLinkAction $createExternalLinkAction,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $externalLink = $createExternalLinkAction->execute(
            $request->externalLinkAttributes(),
            $user,
            $request->ip(),
        );

        return redirect()
            ->route('admin.external-links.edit', $externalLink)
            ->with('success', '外部リンクを登録しました。');
    }

    /**
     * 外部リンク編集画面を表示する。
     *
     * @param  ExternalLink  $externalLink  編集対象の外部リンク
     * @param  ExternalLinkFormDataQuery  $formDataQuery  フォーム表示データ取得処理
     * @return View 外部リンク編集画面
     */
    public function edit(
        ExternalLink $externalLink,
        ExternalLinkFormDataQuery $formDataQuery,
    ): View {
        return view('admin.external-links.edit', [
            ...$formDataQuery->execute()->toViewData(),
            'externalLink' => $externalLink,
        ]);
    }

    /**
     * 外部リンクを更新する。
     *
     * @param  UpdateExternalLinkRequest  $request  検証済み外部リンク情報を含むリクエスト
     * @param  ExternalLink  $externalLink  更新対象の外部リンク
     * @param  UpdateExternalLinkAction  $updateExternalLinkAction  外部リンク更新処理
     * @return RedirectResponse 更新後の外部リンク編集画面へのリダイレクト
     */
    public function update(
        UpdateExternalLinkRequest $request,
        ExternalLink $externalLink,
        UpdateExternalLinkAction $updateExternalLinkAction,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $updateExternalLinkAction->execute(
            $externalLink,
            $request->externalLinkAttributes(),
            $user,
            $request->ip(),
        );

        return redirect()
            ->route('admin.external-links.edit', $externalLink)
            ->with('success', '外部リンクを更新しました。');
    }
}
