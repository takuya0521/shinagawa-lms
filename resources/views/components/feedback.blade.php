@props([
    'type' => 'success',
    'message' => null,
])

@php
    $content = is_string($message) && $message !== ''
        ? $message
        : trim((string) $slot);
    $role = $type === 'error' ? 'alert' : 'status';
@endphp

@if ($content !== '')
    <div {{ $attributes->class(["lms-flash", "lms-flash--{$type}"])->merge(['role' => $role]) }}>
        {{ $content }}
    </div>
@endif
