@props(['name'])

<svg {{ $attributes->merge(['class' => 'lms-nav-icon', 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round', 'aria-hidden' => 'true']) }}>
    @switch($name)
        @case('home')
            <path d="m3 10 9-7 9 7" />
            <path d="M5 9v11h14V9" />
            <path d="M9 20v-6h6v6" />
            @break
        @case('users')
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
            <circle cx="9" cy="7" r="4" />
            <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
            @break
        @case('student')
            <path d="m2 10 10-5 10 5-10 5Z" />
            <path d="M6 12v5c3 2 9 2 12 0v-5" />
            @break
        @case('teacher')
            <circle cx="12" cy="7" r="4" />
            <path d="M5.5 21a6.5 6.5 0 0 1 13 0" />
            <path d="m18 8 3-2" />
            @break
        @case('class')
            <rect x="3" y="4" width="18" height="16" rx="2" />
            <path d="M7 8h10M7 12h10M7 16h6" />
            @break
        @case('book')
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z" />
            @break
        @case('assignment')
            <path d="M9 5h6" />
            <path d="M9 3h6v4H9z" />
            <rect x="5" y="5" width="14" height="16" rx="2" />
            <path d="M8 11h8M8 15h8" />
            @break
        @case('calendar')
            <rect x="3" y="5" width="18" height="16" rx="2" />
            <path d="M16 3v4M8 3v4M3 10h18" />
            @break
        @case('check')
            <path d="M9 11 12 14 22 4" />
            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
            @break
        @case('chart')
            <path d="M3 3v18h18" />
            <path d="m7 16 4-5 4 3 5-7" />
            @break
        @case('chat')
            <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z" />
            <path d="M8 9h8M8 13h5" />
            @break
        @case('bell')
            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9" />
            <path d="M10 21h4" />
            @break
        @case('link')
            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
            @break
        @case('log')
            <path d="M4 4h16v16H4z" />
            <path d="M8 8h8M8 12h8M8 16h5" />
            @break
        @case('key')
            <circle cx="7.5" cy="15.5" r="3.5" />
            <path d="m10 13 9-9M15 8l2 2M17 6l2 2" />
            @break
        @case('form')
            <path d="M4 3h16v18H4z" />
            <path d="M8 7h8M8 11h8M8 15h5" />
            @break
        @default
            <circle cx="12" cy="12" r="9" />
            <path d="M12 8v4l3 2" />
    @endswitch
</svg>
