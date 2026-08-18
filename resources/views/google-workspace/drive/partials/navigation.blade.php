<aside class="gw-drive-sidebar" aria-label="Google Driveナビゲーション">
    @if (in_array($viewMode, ['my-drive', 'shared-drive'], true))
        <details class="gw-new-menu" data-disclosure>
            <summary class="gw-new-button">
                <x-google-icon name="plus" :size="24" />
                <span>新規</span>
                <x-google-icon name="chevron-down" :size="17" />
            </summary>
            <div class="gw-new-menu__panel">
                @include('google-workspace.drive.partials.create-item')
                @include('google-workspace.drive.partials.upload')
            </div>
        </details>
    @endif

    <nav class="gw-side-nav">
        <a
            href="{{ route('google-workspace.drive.index', ['view' => 'my-drive']) }}"
            @class(['is-active' => $viewMode === 'my-drive'])
            @if ($viewMode === 'my-drive') aria-current="page" @endif
        >
            <x-google-icon name="folder" :size="20" />
            <span>マイドライブ</span>
        </a>
        <a
            href="{{ route('google-workspace.drive.index', ['view' => 'shared']) }}"
            @class(['is-active' => $viewMode === 'shared'])
            @if ($viewMode === 'shared') aria-current="page" @endif
        >
            <x-google-icon name="shared" :size="20" />
            <span>共有アイテム</span>
        </a>
        <a
            href="{{ route('google-workspace.drive.index', ['view' => 'recent']) }}"
            @class(['is-active' => $viewMode === 'recent'])
            @if ($viewMode === 'recent') aria-current="page" @endif
        >
            <x-google-icon name="history" :size="20" />
            <span>最近使用したアイテム</span>
        </a>
        <a
            href="{{ route('google-workspace.drive.index', ['view' => 'starred']) }}"
            @class(['is-active' => $viewMode === 'starred'])
            @if ($viewMode === 'starred') aria-current="page" @endif
        >
            <x-google-icon name="star" :size="20" />
            <span>スター付き</span>
        </a>
        <a
            href="{{ route('google-workspace.drive.index', ['view' => 'trash']) }}"
            @class(['is-active' => $viewMode === 'trash'])
            @if ($viewMode === 'trash') aria-current="page" @endif
        >
            <x-google-icon name="trash" :size="20" />
            <span>ゴミ箱</span>
        </a>
    </nav>

    @if ($sharedDrives->isNotEmpty())
        <section class="gw-shared-drives">
            <h3>共有ドライブ</h3>
            <nav class="gw-side-nav gw-side-nav--shared">
                @foreach ($sharedDrives as $sharedDrive)
                    <a
                        href="{{
                            route('google-workspace.drive.index', ['view' => 'shared-drive', 'drive_id' =>
                            $sharedDrive->id])
                        }}"
                        @class(['is-active' => $viewMode === 'shared-drive' && $driveId === $sharedDrive->id])
                        @if ($viewMode === 'shared-drive' && $driveId === $sharedDrive->id) aria-current="page" @endif
                    >
                        <x-google-icon name="drive" :size="19" />
                        <span>{{ $sharedDrive->name }}</span>
                    </a>
                @endforeach
            </nav>
        </section>
    @endif
</aside>
