@props([
    'service',
    'title',
    'icon',
    'searchAction' => null,
    'searchName' => 'keyword',
    'searchValue' => null,
    'searchPlaceholder' => null,
])

<header class="gw-service-header">
    <div class="gw-service-brand">
        <img src="{{ asset($icon) }}" alt="" width="40" height="40">
        <span>{{ $title }}</span>
    </div>

    @if ($searchAction !== null)
        <form class="gw-global-search" method="GET" action="{{ $searchAction }}" role="search">
            {{ $slot }}
            <x-google-icon name="search" :size="21" />
            <input
                type="search"
                name="{{ $searchName }}"
                value="{{ $searchValue }}"
                placeholder="{{ $searchPlaceholder ?? $title.'を検索' }}"
                aria-label="{{ $searchPlaceholder ?? $title.'を検索' }}"
            >
        </form>
    @else
        <div class="gw-service-header__spacer"></div>
    @endif

    <nav class="gw-service-switcher" aria-label="Google Workspaceサービス">
        <a
            href="{{ route('google-workspace.drive.index') }}"
            @class(['is-active' => $service === 'drive'])
            @if ($service === 'drive') aria-current="page" @endif
        >
            <x-google-icon name="drive" :size="19" />
            <span>Drive</span>
        </a>
        <a
            href="{{ route('google-workspace.classroom.index') }}"
            @class(['is-active' => $service === 'classroom'])
            @if ($service === 'classroom') aria-current="page" @endif
        >
            <x-google-icon name="classroom" :size="19" />
            <span>Classroom</span>
        </a>
        <a
            href="{{ route('google-workspace.calendar.index') }}"
            @class(['is-active' => $service === 'calendar'])
            @if ($service === 'calendar') aria-current="page" @endif
        >
            <x-google-icon name="calendar" :size="19" />
            <span>Calendar</span>
        </a>
        <a
            href="{{ route('google-workspace.chat.index') }}"
            @class(['is-active' => $service === 'chat'])
            @if ($service === 'chat') aria-current="page" @endif
        >
            <x-google-icon name="chat" :size="19" />
            <span>Chat</span>
        </a>
        <a
            href="{{ route('google-workspace.meet.index') }}"
            @class(['is-active' => $service === 'meet'])
            @if ($service === 'meet') aria-current="page" @endif
        >
            <x-google-icon name="meet" :size="19" />
            <span>Meet</span>
        </a>
        <a
            href="{{ route('google-workspace.forms.index') }}"
            @class(['is-active' => $service === 'forms'])
            @if ($service === 'forms') aria-current="page" @endif
        >
            <x-google-icon name="forms" :size="19" />
            <span>Forms</span>
        </a>
    </nav>
</header>
