@extends('layouts.app')

@section('page-style', 'resources/css/pages/admin/announcements/edit.css')
@section('page-class', 'page-pattern-form page-admin-announcements-edit')

@section('title', 'お知らせ編集')
@section('header-title', 'お知らせ編集')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">

        <section class="rounded-2xl bg-white p-6 shadow-sm">
            <div class="border-b border-slate-200 pb-5">
                <p class="text-sm font-semibold text-slate-500">A-036</p>
                <h1 class="mt-1 text-2xl font-bold">お知らせ編集</h1>
                <p class="mt-2 text-sm text-slate-600">
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

        <section class="rounded-2xl border border-red-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-red-700">お知らせの削除</h2>
            <p class="mt-2 text-sm text-slate-600">削除後は一覧と利用者画面に表示されません。</p>
            <form
                method="POST"
                action="{{ route('admin.announcements.destroy', $announcement) }}"
                class="mt-4"
                onsubmit="return confirm('このお知らせを削除しますか？');"
            >
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-lg border border-red-300 px-5 py-2.5 font-semibold text-red-700 hover:bg-red-50">
                    削除する
                </button>
            </form>
        </section>
    </div>
@endsection
