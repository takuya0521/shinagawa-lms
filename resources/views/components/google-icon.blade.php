@props(['name', 'size' => 20])

<svg
    {{ $attributes->merge([
        'class' => 'gw-icon',
        'width' => $size,
        'height' => $size,
        'viewBox' => '0 0 24 24',
        'fill' => 'none',
        'stroke' => 'currentColor',
        'stroke-width' => '1.8',
        'stroke-linecap' => 'round',
        'stroke-linejoin' => 'round',
        'aria-hidden' => 'true',
    ]) }}
>
    @switch($name)
        @case('search')
            <circle cx="11" cy="11" r="7" />
            <path d="m20 20-3.6-3.6" />
            @break
        @case('plus')
            <path d="M12 5v14M5 12h14" />
            @break
        @case('upload')
            <path d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5" />
            <path d="M5 14v4a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4" />
            @break
        @case('folder')
            <path d="M3 6.5h6l2 2h10v9.5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z" />
            @break
        @case('shared')
            <circle cx="8" cy="9" r="3" />
            <circle cx="17" cy="8" r="2.5" />
            <path d="M2.5 20a5.5 5.5 0 0 1 11 0M13 20a4.5 4.5 0 0 1 8.5-2" />
            @break
        @case('history')
            <path d="M3 12a9 9 0 1 0 3-6.7L3 8" />
            <path d="M3 3v5h5M12 7v5l3 2" />
            @break
        @case('star')
            <path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 17.3 6.4 20.2 7.5 14 3 9.6l6.2-.9Z" />
            @break
        @case('trash')
            <path d="M4 7h16M9 7V4h6v3M7 7l1 13h8l1-13M10 11v5M14 11v5" />
            @break
        @case('drive')
            <path d="m9 3-6 10 3 5h12l3-5-6-10Z" />
            <path d="m9 3 6 10M15 3 9 13M3 13h18" />
            @break
        @case('calendar')
            <rect x="3" y="5" width="18" height="16" rx="2" />
            <path d="M8 3v4M16 3v4M3 10h18" />
            @break
        @case('classroom')
            <rect x="3" y="5" width="18" height="14" rx="2" />
            <circle cx="9" cy="11" r="2" />
            <circle cx="15" cy="11" r="2" />
            <path d="M6 17c.5-2 1.5-3 3-3s2.5 1 3 3M12 17c.5-2 1.5-3 3-3s2.5 1 3 3" />
            @break
        @case('chat')
            <path d="M20 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h9a4 4 0 0 1 4 4Z" />
            <path d="M8 9h8M8 13h5" />
            @break
        @case('refresh')
            <path d="M20 7v5h-5M4 17v-5h5" />
            <path d="M18.5 10A7 7 0 0 0 6.2 6.2L4 8M5.5 14A7 7 0 0 0 17.8 17.8L20 16" />
            @break
        @case('grid')
            <rect x="4" y="4" width="6" height="6" rx="1" />
            <rect x="14" y="4" width="6" height="6" rx="1" />
            <rect x="4" y="14" width="6" height="6" rx="1" />
            <rect x="14" y="14" width="6" height="6" rx="1" />
            @break
        @case('list')
            <path d="M8 6h12M8 12h12M8 18h12" />
            <circle cx="4" cy="6" r="1" fill="currentColor" stroke="none" />
            <circle cx="4" cy="12" r="1" fill="currentColor" stroke="none" />
            <circle cx="4" cy="18" r="1" fill="currentColor" stroke="none" />
            @break
        @case('more')
            <circle cx="5" cy="12" r="1.3" fill="currentColor" stroke="none" />
            <circle cx="12" cy="12" r="1.3" fill="currentColor" stroke="none" />
            <circle cx="19" cy="12" r="1.3" fill="currentColor" stroke="none" />
            @break
        @case('chevron-down')
            <path d="m7 9 5 5 5-5" />
            @break
        @case('chevron-left')
            <path d="m15 18-6-6 6-6" />
            @break
        @case('chevron-right')
            <path d="m9 18 6-6-6-6" />
            @break
        @case('today')
            <rect x="4" y="5" width="16" height="15" rx="2" />
            <path d="M8 3v4M16 3v4M4 10h16M9 14h6" />
            @break
        @case('settings')
            <circle cx="12" cy="12" r="3" />
            <path
                d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1
                    1.6V21h-4v-.1a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9A1.7
                    1.7 0 0 0 3 14H3v-4h.1a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9L4.2 7 7 4.2l.1.1A1.7 1.7 0 0 0 9
                    4.6a1.7 1.7 0 0 0 1-1.6V3h4v.1a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7
                    0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.1v4H21a1.7 1.7 0 0 0-1.6 1Z"
            />
            @break
        @case('users')
            <circle cx="9" cy="8" r="3" />
            <circle cx="17" cy="9" r="2.5" />
            <path d="M3 20a6 6 0 0 1 12 0M14 20a4.5 4.5 0 0 1 8-2.8" />
            @break
        @case('send')
            <path d="m3 11 18-8-8 18-2.3-7.7Z" />
            <path d="m11 13 4-4" />
            @break
        @case('attach')
            <path d="m20.5 11.5-8.2 8.2a6 6 0 0 1-8.5-8.5l9-9a4 4 0 0 1 5.7 5.7l-9.2 9.2a2 2 0 0 1-2.8-2.8l8.5-8.5" />
            @break
        @case('emoji')
            <circle cx="12" cy="12" r="9" />
            <path d="M8 14s1.5 2 4 2 4-2 4-2M9 9h.01M15 9h.01" />
            @break
        @case('meet')
            <rect x="3" y="6" width="13" height="12" rx="2" />
            <path d="m16 10 5-3v10l-5-3Z" />
            @break
        @case('forms')
            <rect x="5" y="3" width="14" height="18" rx="2" />
            <path d="M9 8h6M9 12h6M9 16h4" />
            <circle cx="8" cy="8" r=".6" fill="currentColor" stroke="none" />
            @break
        @case('close')
            <path d="m6 6 12 12M18 6 6 18" />
            @break
        @default
            <circle cx="12" cy="12" r="9" />
    @endswitch
</svg>
