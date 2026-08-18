@php
    $previewUrl = match ($file->mimeType) {
        'application/vnd.google-apps.document' => 'https://docs.google.com/document/d/'.$file->id.'/preview',
        'application/vnd.google-apps.spreadsheet' => 'https://docs.google.com/spreadsheets/d/'.$file->id.'/preview',
        'application/vnd.google-apps.presentation' => 'https://docs.google.com/presentation/d/'.$file->id.'/preview',
        default => 'https://drive.google.com/file/d/'.$file->id.'/preview',
    };
    $isImagePreview = str_starts_with($file->mimeType, 'image/') && $file->thumbnailLink !== null;
@endphp

<header class="gw-file-header">
    <div class="gw-file-header__identity">
        <a
            class="gw-back"
            href="{{ route('google-workspace.drive.index', ['parent_id' => $file->parents[0] ?? null]) }}"
        >← Drive一覧へ</a>
        <div class="gw-file-header__title-row">
            <span class="gw-file-header__icon" aria-hidden="true">
                @if ($file->iconLink !== null)
                    <img src="{{ $file->iconLink }}" alt="" width="24" height="24">
                @else
                    <img src="{{ asset('images/google/drive.svg') }}" alt="" width="24" height="24">
                @endif
            </span>
            <div>
                <h1>{{ $file->name }}</h1>
                <p>
                    {{ $file->typeLabel() }}
                    <span aria-hidden="true">・</span>
                    {{ $file->modifiedAt?->format('Y年n月j日 H:i') ?? '更新日時なし' }}
                    @if ($file->formattedSize() !== null)
                        <span aria-hidden="true">・</span>
                        {{ $file->formattedSize() }}
                    @endif
                </p>
            </div>
        </div>
    </div>
    <div class="gw-actions">
        @if ($file->canDownload)
            <a class="gw-button gw-button--primary" href="{{ route('google-workspace.drive.download', $file->id) }}">
                ダウンロード
            </a>
        @endif
        @if ($file->webViewLink !== null)
            <a
                class="gw-button gw-button--secondary"
                href="{{ $file->webViewLink }}"
                target="_blank"
                rel="noopener noreferrer"
            >Googleで開く</a>
        @endif
    </div>
</header>

<div class="gw-file-detail-layout">
    <div class="gw-file-detail-main">
        <section class="gw-file-preview" aria-labelledby="gw-file-preview-title">
            <header class="gw-file-preview__header">
                <div>
                    <span>PREVIEW</span>
                    <h2 id="gw-file-preview-title">ファイルプレビュー</h2>
                </div>
                <span class="gw-file-preview__type">{{ $file->typeLabel() }}</span>
            </header>

            <div class="gw-file-preview__canvas">
                @if ($file->isFolder())
                    <div class="gw-file-preview__empty">
                        <img src="{{ asset('images/google/drive.svg') }}" alt="" width="56" height="56">
                        <strong>フォルダはプレビューできません</strong>
                        <span>Drive一覧からフォルダを開いて内容を確認してください。</span>
                    </div>
                @elseif ($isImagePreview)
                    <img
                        class="gw-file-preview__image"
                        src="{{ $file->thumbnailLink }}"
                        alt="{{ $file->name }}のプレビュー"
                    >
                @else
                    <iframe
                        class="gw-file-preview__frame"
                        src="{{ $previewUrl }}"
                        title="{{ $file->name }}のプレビュー"
                        loading="lazy"
                        allow="autoplay"
                        allowfullscreen
                    ></iframe>
                @endif
            </div>

            @if (! $file->isFolder())
                <footer class="gw-file-preview__footer">
                    <span>
                        プレビューが表示されない場合はGoogle Driveで直接開いてください。
                    </span>
                    @if ($file->webViewLink !== null)
                        <a
                            href="{{ $file->webViewLink }}"
                            target="_blank"
                            rel="noopener noreferrer"
                        >Googleで全画面表示</a>
                    @endif
                </footer>
            @endif
        </section>

        <div class="gw-grid gw-grid--two">
            @include('google-workspace.drive.partials.permissions')
            @include('google-workspace.drive.partials.revisions')
        </div>

        @include('google-workspace.drive.partials.comments')
    </div>

    <aside class="gw-file-detail-sidebar">
        <section class="gw-file-summary">
            <div class="gw-file-summary__heading">
                <span>DETAILS</span>
                <h2>ファイル情報</h2>
            </div>
            <dl class="gw-file-summary__list">
                <div>
                    <dt>種類</dt>
                    <dd>{{ $file->typeLabel() }}</dd>
                </div>
                <div>
                    <dt>更新日時</dt>
                    <dd>{{ $file->modifiedAt?->format('Y/m/d H:i') ?? '日時なし' }}</dd>
                </div>
                <div>
                    <dt>サイズ</dt>
                    <dd>{{ $file->formattedSize() ?? 'Google形式' }}</dd>
                </div>
                <div>
                    <dt>所有</dt>
                    <dd>{{ $file->ownedByMe ? '自分' : '共有ファイル' }}</dd>
                </div>
                <div>
                    <dt>スター</dt>
                    <dd>{{ $file->starred ? 'スター付き' : 'なし' }}</dd>
                </div>
                @if ($file->description !== null)
                    <div class="gw-file-summary__description">
                        <dt>説明</dt>
                        <dd>{{ $file->description }}</dd>
                    </div>
                @endif
            </dl>
        </section>

        @include('google-workspace.drive.partials.file-edit')
        @include('google-workspace.drive.partials.file-actions')
    </aside>
</div>
