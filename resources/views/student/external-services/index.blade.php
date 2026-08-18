@extends('layouts.app')

@section('page-class', 'page-pattern-external-services page-student-external-services-index')

@section('title', $title)
@section('header-title', $title)

@section('content')
    <div class="space-y-6">

        @if ($embeddableLink !== null)
            <section class="overflow-hidden p-4 lms-panel">
                <iframe
                    src="{{ $embeddableLink }}"
                    class="h-[650px] w-full rounded-xl border lms-border-neutral-subtle"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    title="Google Calendar"
                ></iframe>
            </section>
        @endif

        <section class="grid gap-4 md:grid-cols-2">
            @forelse ($links as $link)
                <a
                    href="{{ $link->url }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="p-6 lms-panel lms-panel--interactive"
                >
                    <p class="text-sm font-semibold lms-text-neutral-muted">{{ $link->link_type->label() }}</p>
                    <h2 class="mt-2 text-lg font-bold lms-text-neutral-strong">{{ $link->link_name }}</h2>
                    <p class="mt-4 text-sm font-semibold lms-text-primary">別タブで開く →</p>
                </a>
            @empty
                <div class="px-6 py-12 text-center text-sm lms-text-neutral-muted md:col-span-2 lms-panel">
                    現在利用できるリンクは登録されていません。
                </div>
            @endforelse
        </section>
    </div>
@endsection
