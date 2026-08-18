<?php

namespace App\Http\Controllers\GoogleWorkspace;

use App\Exceptions\GoogleForms\GoogleFormsException;
use App\Exceptions\GoogleWorkspace\GoogleWorkspaceException;
use App\Http\Requests\GoogleWorkspace\FormCreateRequest;
use App\Http\Requests\GoogleWorkspace\FormIndexRequest;
use App\Http\Requests\GoogleWorkspace\FormPublishRequest;
use App\Services\GoogleForms\GoogleFormsService;
use App\Services\GoogleForms\Support\GoogleFormsConfiguration;
use App\Services\GoogleWorkspace\GoogleWorkspaceService;
use App\Services\GoogleWorkspace\Support\GoogleWorkspaceCache;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Google Formsの一覧・詳細シェル、作成、公開状態変更を担当する。
 */
final class FormsController extends GoogleWorkspaceController
{
    /**
     * Google APIを待たず、Forms一覧画面の共通枠と連携状態だけを表示する。
     *
     * @param  FormIndexRequest  $request  検索条件付きHTTPリクエスト
     * @param  GoogleFormsConfiguration  $configuration  Forms API設定
     * @param  GoogleWorkspaceService  $workspaceService  共通OAuth処理
     * @return View Forms一覧画面の軽量シェル
     */
    public function index(
        FormIndexRequest $request,
        GoogleFormsConfiguration $configuration,
        GoogleWorkspaceService $workspaceService,
    ): View {
        $user = $this->resolveUser($request);
        $state = $this->connectionState(
            $user,
            $workspaceService,
            $configuration->requiredScopes(),
        );

        return view('google-workspace.forms.index', [
            ...$state,
            'keyword' => $request->keyword(),
        ]);
    }

    /**
     * Drive上のGoogleフォーム一覧を取得し、一覧画面へ差し込むHTMLを返す。
     *
     * @param  FormIndexRequest  $request  検索条件付きHTTPリクエスト
     * @param  GoogleFormsService  $formsService  Forms API処理
     * @param  GoogleFormsConfiguration  $configuration  Forms API設定
     * @param  GoogleWorkspaceService  $workspaceService  共通OAuth処理
     * @param  GoogleWorkspaceCache  $cache  Google API結果のSWRキャッシュ
     * @return Response Forms一覧の非同期表示断片
     */
    public function indexContent(
        FormIndexRequest $request,
        GoogleFormsService $formsService,
        GoogleFormsConfiguration $configuration,
        GoogleWorkspaceService $workspaceService,
        GoogleWorkspaceCache $cache,
    ): Response {
        $user = $this->resolveUser($request);
        $state = $this->connectionState(
            $user,
            $workspaceService,
            $configuration->requiredScopes(),
        );

        if (! $state['isConfigured'] || ! $state['isAuthorized']) {
            return $this->asyncAuthorizationRequired();
        }

        $this->prepareAsyncCache($request, $cache, $user, 'forms');
        $keyword = $request->keyword();

        try {
            $forms = $cache->remember(
                $user,
                'forms',
                'list:'.($keyword ?? ''),
                180,
                fn () => $formsService->forms($user, $keyword),
            );
        } catch (ConnectionException|RequestException|GoogleFormsException|GoogleWorkspaceException $exception) {
            return $this->asyncOperationFailure(
                $exception,
                $user,
                'google_forms_index_async',
                'Googleフォーム一覧を取得できませんでした。',
            );
        }

        return $this->asyncView('google-workspace.forms.content', [
            'forms' => $forms,
            'keyword' => $keyword,
        ], $cache);
    }

    /**
     * 新しいGoogleフォームを作成する。
     *
     * @param  FormCreateRequest  $request  フォーム作成入力
     * @param  GoogleFormsService  $formsService  Forms API処理
     * @return RedirectResponse 作成したフォーム詳細画面
     */
    public function store(
        FormCreateRequest $request,
        GoogleFormsService $formsService,
    ): RedirectResponse {
        $user = $this->resolveUser($request);

        try {
            $form = $formsService->createForm(
                $user,
                $request->title(),
                $request->description(),
                $request->shouldPublish(),
            );
        } catch (ConnectionException|RequestException|GoogleFormsException|GoogleWorkspaceException $exception) {
            return $this->operationFailure(
                $exception,
                $user,
                'google_forms_create',
                'Googleフォームを作成できませんでした。',
            );
        }

        return redirect()
            ->route('google-workspace.forms.show', $form->id)
            ->with('success', 'Googleフォームを作成しました。');
    }

    /**
     * Google APIを待たず、フォーム詳細画面の共通枠だけを表示する。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  string  $formId  GoogleフォームID
     * @param  GoogleFormsConfiguration  $configuration  Forms API設定
     * @param  GoogleWorkspaceService  $workspaceService  共通OAuth処理
     * @return View Forms詳細画面の軽量シェル
     */
    public function show(
        Request $request,
        string $formId,
        GoogleFormsConfiguration $configuration,
        GoogleWorkspaceService $workspaceService,
    ): View {
        $user = $this->resolveUser($request);
        $state = $this->connectionState(
            $user,
            $workspaceService,
            $configuration->requiredScopes(),
        );

        return view('google-workspace.forms.show', [
            ...$state,
            'formId' => $formId,
        ]);
    }

    /**
     * フォーム詳細と回答概要を取得し、詳細画面へ差し込むHTMLを返す。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  string  $formId  GoogleフォームID
     * @param  GoogleFormsService  $formsService  Forms API処理
     * @param  GoogleWorkspaceCache  $cache  Google API結果のSWRキャッシュ
     * @return Response Forms詳細の非同期表示断片
     */
    public function showContent(
        Request $request,
        string $formId,
        GoogleFormsService $formsService,
        GoogleWorkspaceCache $cache,
    ): Response {
        $user = $this->resolveUser($request);
        $this->prepareAsyncCache($request, $cache, $user, 'forms');

        try {
            $form = $cache->remember(
                $user,
                'forms',
                'detail:'.$formId,
                180,
                fn () => $formsService->form($user, $formId),
            );
            $responses = $cache->remember(
                $user,
                'forms',
                'responses:'.$formId,
                60,
                fn () => $formsService->responses($user, $formId),
            );
        } catch (ConnectionException|RequestException|GoogleFormsException|GoogleWorkspaceException $exception) {
            return $this->asyncOperationFailure(
                $exception,
                $user,
                'google_forms_show_async',
                'Googleフォームの詳細を取得できませんでした。',
            );
        }

        return $this->asyncView('google-workspace.forms.show-content', [
            'form' => $form,
            'responses' => $responses,
        ], $cache);
    }

    /**
     * Googleフォームの公開状態と回答受付状態を切り替える。
     *
     * @param  FormPublishRequest  $request  公開状態変更入力
     * @param  string  $formId  GoogleフォームID
     * @param  GoogleFormsService  $formsService  Forms API処理
     * @return RedirectResponse フォーム詳細画面
     */
    public function updatePublish(
        FormPublishRequest $request,
        string $formId,
        GoogleFormsService $formsService,
    ): RedirectResponse {
        $user = $this->resolveUser($request);

        try {
            $formsService->setPublished($user, $formId, $request->published());
        } catch (ConnectionException|RequestException|GoogleFormsException|GoogleWorkspaceException $exception) {
            return $this->operationFailure(
                $exception,
                $user,
                'google_forms_publish_update',
                'Googleフォームの公開状態を変更できませんでした。',
            );
        }

        return redirect()
            ->route('google-workspace.forms.show', $formId)
            ->with('success', $request->published() ? 'フォームを公開しました。' : 'フォームを非公開にしました。');
    }
}
