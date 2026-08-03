@extends('layouts.app')

@section('page-style', 'resources/css/pages/student/external-services/index.css')
@section('page-class', 'page-pattern-external-services page-student-external-services-index')

@section('title', $title)
@section('header-title', $title)

@section('content')
    <div class="space-y-6">
        <section class="rounded-2xl bg-white p-6 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">{{ $screenId }}</p>
            <h1 class="mt-1 text-2xl font-bold">{{ $title }}</h1>
            <p class="mt-2 text-sm text-slate-600">{{ $description }}</p>
        </section>

        @if ($embeddableLink !== null)
            <section class="overflow-hidden rounded-2xl bg-white p-4 shadow-sm">
                <iframe
                    src="{{ $embeddableLink }}"
                    class="h-[650px] w-full rounded-xl border border-slate-200"
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
                    class="rounded-2xl bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                >
                    <p class="text-sm font-semibold text-slate-500">{{ $link->link_type->label() }}</p>
                    <h2 class="mt-2 text-lg font-bold text-slate-900">{{ $link->link_name }}</h2>
                    <p class="mt-4 text-sm font-semibold text-blue-700">別タブで開く →</p>
                </a>
            @empty
                <div class="rounded-2xl bg-white px-6 py-12 text-center text-sm text-slate-500 shadow-sm md:col-span-2">
                    現在利用できるリンクは登録されていません。
                </div>
            @endforelse
        </section>
    </div>
@endsection
