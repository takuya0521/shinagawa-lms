@php
    $locationLabel = match ($viewMode) {
        'shared' => '共有アイテム',
        'starred' => 'スター付き',
        'recent' => '最近使用したアイテム',
        'trash' => 'ゴミ箱',
        'shared-drive' => $sharedDrives->firstWhere('id', $driveId)?->name ?? '共有ドライブ',
        default => $parentId === null ? 'マイドライブ' : 'フォルダ',
    };
@endphp

@if ($driveError !== null)
    <section class="gw-alert gw-alert--error" role="alert">{{ $driveError }}</section>
@endif

<div class="gw-drive-shell">
    @include('google-workspace.drive.partials.navigation')

    <section class="gw-drive-content" aria-labelledby="drive-files-heading">
        <header class="gw-drive-toolbar">
            <div class="gw-drive-toolbar__title">
                <h2 id="drive-files-heading">{{ $locationLabel }}</h2>
                <span>{{ $files->count() }}件</span>
            </div>
            <div class="gw-drive-toolbar__actions">
                @if ($parentId !== null)
                    <a
                        class="gw-icon-button gw-icon-button--text"
                        href="{{
                            route('google-workspace.drive.index', ['view' => $viewMode, 'drive_id' => $driveId])
                        }}"
                    >
                        <x-google-icon name="folder" :size="18" />
                        <span>ルートへ</span>
                    </a>
                @endif
                <a
                    class="gw-icon-button"
                    href="{{
                        route('google-workspace.drive.index', array_filter(['parent_id' => $parentId, 'keyword' =>
                        $keyword, 'view' => $viewMode, 'drive_id' => $driveId, 'refresh' => 1]))
                    }}"
                    aria-label="Google Driveを更新"
                    title="更新"
                >
                    <x-google-icon name="refresh" :size="20" />
                </a>
                <span class="gw-icon-button is-selected" aria-label="リスト表示">
                    <x-google-icon name="list" :size="20" />
                </span>
            </div>
        </header>

        @include('google-workspace.drive.partials.file-list')
    </section>
</div>
