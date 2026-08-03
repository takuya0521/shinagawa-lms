@extends('layouts.app')

@section('page-style', 'resources/css/pages/admin/announcements/create.css')
@section('page-class', 'page-pattern-form page-admin-announcements-create')

@section('title', 'お知らせ投稿')
@section('header-title', 'お知らせ投稿')

@section('content')
    <section class="mx-auto max-w-5xl rounded-2xl bg-white p-6 shadow-sm">
        <div class="border-b border-slate-200 pb-5">
            <p class="text-sm font-semibold text-slate-500">A-035</p>
            <h1 class="mt-1 text-2xl font-bold">お知らせ投稿</h1>
            <p class="mt-2 text-sm text-slate-600">学校共通・学年別・事務連絡のお知らせを登録します。</p>
        </div>

        <form method="POST" action="{{ route('admin.announcements.store') }}" class="mt-6">
            @csrf
            @include('admin.announcements._form')
        </form>
    </section>
@endsection
