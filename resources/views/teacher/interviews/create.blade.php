@extends('layouts.app')

@section('page-style', 'resources/css/pages/teacher/interviews/create.css')
@section('page-class', 'page-pattern-form page-teacher-interviews-create')

@section('title', '面談記録登録')
@section('header-title', '面談管理')

@section('content')
    <section class="mx-auto max-w-5xl space-y-6">
        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <div class="border-b border-slate-200 pb-5">
                <p class="text-sm font-semibold text-slate-500">T-009</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-900">面談記録登録</h1>
                <p class="mt-2 text-sm text-slate-600">
                    担当授業に参加する生徒の面談内容と次回対応を記録します。
                </p>
            </div>

            <form method="POST" action="{{ route('teacher.interviews.store') }}" class="mt-6">
                @csrf
                @include('teacher.interviews._form')
            </form>
        </div>
    </section>
@endsection
