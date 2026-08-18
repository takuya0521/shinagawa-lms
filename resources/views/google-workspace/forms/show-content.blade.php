<section class="gw-detail-hero gw-forms-detail-hero">
    <div>
        <span class="gw-eyebrow">{{ $form->isQuiz ? 'テスト' : 'フォーム' }}</span>
        <h1>{{ $form->title }}</h1>
        <p>{{ $form->description ?? '説明は設定されていません。' }}</p>
    </div>
    <div class="gw-actions">
        @if ($form->responderUri !== null && $form->isPublished)
            <a
                class="gw-button gw-button--primary"
                href="{{ $form->responderUri }}"
                target="_blank"
                rel="noopener noreferrer"
            >回答画面を開く</a>
        @endif
        <a
            class="gw-button gw-button--secondary"
            href="{{ $form->editorUri() }}"
            target="_blank"
            rel="noopener noreferrer"
        >Googleで編集</a>
    </div>
</section>

<section class="gw-grid gw-grid--two">
    <div class="gw-section">
        <div class="gw-section__header">
            <div>
                <p>フォーム状態</p>
                <h2>公開・回答受付</h2>
            </div>
        </div>
        <dl class="gw-stat-grid">
            <div>
                <dt>項目数</dt>
                <dd>{{ $form->itemCount }}</dd>
            </div>
            <div>
                <dt>回答数</dt>
                <dd>{{ $responses->count() }}</dd>
            </div>
            <div>
                <dt>公開状態</dt>
                <dd>{{ $form->isPublished ? '公開中' : '非公開' }}</dd>
            </div>
            <div>
                <dt>回答受付</dt>
                <dd>{{ $form->isAcceptingResponses ? '受付中' : '停止中' }}</dd>
            </div>
        </dl>

        @if ($form->supportsPublishing)
            <form
                class="gw-form-publish"
                method="POST"
                action="{{ route('google-workspace.forms.publish.update', $form->id) }}"
            >
                @csrf
                @method('PATCH')
                <input type="hidden" name="published" value="{{ $form->isPublished ? 0 : 1 }}">
                <button
                    class="gw-button {{ $form->isPublished ? 'gw-button--secondary' : 'gw-button--primary' }}"
                    type="submit"
                >{{ $form->isPublished ? '非公開にする' : '公開して回答受付を開始' }}</button>
            </form>
        @else
            <p class="gw-notice">この既存フォームはForms APIの公開設定に対応していません。</p>
        @endif
    </div>

    <div class="gw-section">
        <div class="gw-section__header">
            <div>
                <p>Google Drive上の情報</p>
                <h2>フォーム情報</h2>
            </div>
        </div>
        <dl class="gw-info-list">
            <div><dt>文書名</dt><dd>{{ $form->documentTitle }}</dd></div>
            <div><dt>フォームID</dt><dd class="gw-code">{{ $form->id }}</dd></div>
        </dl>
    </div>
</section>

<section class="gw-section">
    <div class="gw-section__header">
        <div>
            <p>Forms APIから取得した回答概要</p>
            <h2>回答</h2>
        </div>
        <span class="gw-count">{{ $responses->count() }}件</span>
    </div>

    @if ($responses->isEmpty())
        <div class="gw-empty-state gw-empty-state--compact">
            <div class="gw-empty-state__icon"><x-google-icon name="forms" :size="34" /></div>
            <h3>回答はありません</h3>
            <p>回答が送信されると、この一覧へ表示されます。</p>
        </div>
    @else
        <div class="gw-table-wrap">
            <table class="gw-table">
                <thead>
                    <tr>
                        <th>送信日時</th>
                        <th>回答者</th>
                        <th>回答項目数</th>
                        <th>得点</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($responses as $formResponse)
                        <tr>
                            <td>{{ $formResponse->submittedAt->format('Y/m/d H:i') }}</td>
                            <td>{{ $formResponse->respondentEmail ?? '匿名' }}</td>
                            <td>{{ $formResponse->answerCount }}</td>
                            <td>
                                {{ $formResponse->totalScore !== null
                                    ? number_format($formResponse->totalScore, 2)
                                    : '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
