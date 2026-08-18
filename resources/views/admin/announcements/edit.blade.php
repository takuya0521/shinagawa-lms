@extends('layouts.app')

@section('page-class', 'page-pattern-form page-admin-announcements-edit')

@section('title', 'お知らせ編集')
@section('header-title', 'お知らせ編集')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">

        <section class="p-6 lms-panel">
            <div class="border-b lms-border-neutral-subtle pb-5">
                <p class="text-sm lms-text-neutral-subtle">
                    作成：{{ $announcement->creator->name }}
                    @if ($announcement->updater !== null)
                        / 最終更新：{{ $announcement->updater->name }}
                    @endif
                </p>
            </div>

            <form method="POST" action="{{ route('admin.announcements.update', $announcement) }}" class="mt-6">
                @csrf
                @method('PUT')
                @include('admin.announcements._form')
            </form>
        </section>

        <section class="border lms-border-danger p-6 lms-panel">
            <h2 class="text-lg font-bold lms-text-danger-strong">お知らせの削除</h2>
            <p class="mt-2 text-sm lms-text-neutral-subtle">削除後は一覧と利用者画面に表示されません。</p>
            <form
                method="POST"
                action="{{ route('admin.announcements.destroy', $announcement) }}"
                class="mt-4"
                data-confirm-message="このお知らせを削除しますか？"
            >
                @csrf
                @method('DELETE')
                <button
                    type="submit"
                    class="rounded-lg border lms-border-danger-strong px-5 py-2.5 font-semibold lms-text-danger-strong
                        lms-hover-bg-danger-soft"
                >
                    削除する
                </button>
            </form>
        </section>
    </div>
@endsection
