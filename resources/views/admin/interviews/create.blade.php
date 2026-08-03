@extends('layouts.app')

@section('page-style', 'resources/css/pages/admin/interviews/create.css')
@section('page-class', 'page-pattern-form page-admin-interviews-create')

@section('title', '面談記録登録')
@section('header-title', '面談管理')

@section('content')
    <section class="mx-auto max-w-5xl space-y-6">
        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <div class="border-b border-slate-200 pb-5">
                <p class="text-sm font-semibold text-slate-500">A-032</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-900">面談記録登録</h1>
                <p class="mt-2 text-sm text-slate-600">
                    生徒との面談内容、次回対応、Google Drive・Meetへのリンクを登録します。
                </p>
            </div>

            <form method="POST" action="{{ route('admin.interviews.store') }}" class="mt-6">
                @csrf
                @include('admin.interviews._form')
            </form>
        </div>
    </section>
@endsection
