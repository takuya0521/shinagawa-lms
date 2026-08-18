<section class="gw-section">
    <div class="gw-section__header">
        <div>
            <p>{{ $keyword !== null ? '検索結果' : 'Google Drive上のフォーム' }}</p>
            <h2>フォーム一覧</h2>
        </div>
        <span class="gw-count">{{ $forms->count() }}件</span>
    </div>

    @if ($forms->isEmpty())
        <div class="gw-empty-state">
            <div class="gw-empty-state__icon"><x-google-icon name="forms" :size="34" /></div>
            <h3>フォームがありません</h3>
            <p>新しいフォームを作成するか、検索条件を変更してください。</p>
        </div>
    @else
        <div class="gw-forms-grid">
            @foreach ($forms as $formFile)
                <article class="gw-form-card">
                    <div class="gw-form-card__icon">
                        <img src="{{ asset('images/google/forms.svg') }}" alt="" width="30" height="30">
                    </div>
                    <div class="gw-form-card__body">
                        <h3>{{ $formFile->name }}</h3>
                        <p>
                            {{ $formFile->modifiedAt?->format('Y/m/d H:i') ?? '更新日時なし' }}
                            · {{ $formFile->canEdit ? '編集可' : '閲覧のみ' }}
                        </p>
                    </div>
                    <div class="gw-form-card__actions">
                        <a
                            class="gw-button gw-button--secondary gw-button--compact"
                            href="{{ route('google-workspace.forms.show', $formFile->id) }}"
                        >詳細</a>
                        @if ($formFile->webViewLink !== null)
                            <a
                                class="gw-icon-button gw-icon-button--text"
                                href="{{ $formFile->webViewLink }}"
                                target="_blank"
                                rel="noopener noreferrer"
                            >Googleで編集</a>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>
