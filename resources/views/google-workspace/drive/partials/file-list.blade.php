<div class="gw-drive-list" role="table" aria-label="Google Driveのファイルとフォルダ">
    <div class="gw-drive-list__head" role="row">
        <span role="columnheader">名前</span>
        <span role="columnheader">オーナー</span>
        <span role="columnheader">最終更新</span>
        <span role="columnheader">ファイルサイズ</span>
        <span role="columnheader"><span class="sr-only">操作</span></span>
    </div>

    @forelse ($files as $file)
        <article class="gw-drive-row" role="row">
            <div class="gw-drive-row__name" role="cell">
                @if ($file->iconLink !== null)
                    <img
                        src="{{ $file->iconLink }}"
                        alt=""
                        width="24"
                        height="24"
                        referrerpolicy="no-referrer"
                        loading="lazy"
                    >
                @else
                    <span class="gw-file-placeholder" aria-hidden="true">
                        <x-google-icon :name="$file->isFolder() ? 'folder' : 'drive'" :size="22" />
                    </span>
                @endif

                @if ($file->isFolder() && ! $file->trashed)
                    <a
                        href="{{
                            route('google-workspace.drive.index', ['parent_id' => $file->id, 'view' => $viewMode,
                            'drive_id' => $driveId])
                        }}"
                    >{{ $file->name }}</a>
                @else
                    <a href="{{ route('google-workspace.drive.show', $file->id) }}">{{ $file->name }}</a>
                @endif

                @if ($file->starred)
                    <x-google-icon class="gw-starred" name="star" :size="16" />
                @endif
            </div>
            <span class="gw-drive-row__owner" role="cell">{{ $file->ownedByMe ? '自分' : '共有' }}</span>
            <time role="cell" datetime="{{ $file->modifiedAt?->toIso8601String() }}">
                {{ $file->modifiedAt?->format('Y年n月j日 H:i') ?? '—' }}
            </time>
            <span role="cell">{{ $file->formattedSize() ?? '—' }}</span>
            <div class="gw-drive-row__menu" role="cell">
                <details class="gw-action-menu" data-disclosure>
                    <summary class="gw-icon-button" aria-label="{{ $file->name }}の操作">
                        <x-google-icon name="more" :size="20" />
                    </summary>
                    <div class="gw-action-menu__panel">
                        <a href="{{ route('google-workspace.drive.show', $file->id) }}">詳細を表示</a>
                        @if ($file->webViewLink !== null)
                            <a href="{{ $file->webViewLink }}" target="_blank" rel="noopener noreferrer">Googleで開く</a>
                        @endif
                        @if ($file->trashed)
                            <form method="POST" action="{{ route('google-workspace.drive.restore', $file->id) }}">
                                @csrf
                                <button type="submit">復元</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('google-workspace.drive.trash', $file->id) }}">
                                @csrf
                                <button type="submit">ゴミ箱に移動</button>
                            </form>
                        @endif
                    </div>
                </details>
            </div>
        </article>
    @empty
        <div class="gw-empty-state">
            <span class="gw-empty-state__icon"><x-google-icon name="folder" :size="42" /></span>
            <h3>ここにはファイルがありません</h3>
            <p>新しいフォルダやファイルを作成すると、ここに表示されます。</p>
        </div>
    @endforelse
</div>

@if ($nextPageToken !== null)
    <div class="gw-pagination">
        <a
            class="gw-button gw-button--secondary"
            href="{{
                route('google-workspace.drive.index', ['parent_id' => $parentId, 'keyword' => $keyword, 'view' =>
                $viewMode, 'drive_id' => $driveId, 'page_token' => $nextPageToken])
            }}"
        >次のページ</a>
    </div>
@endif
