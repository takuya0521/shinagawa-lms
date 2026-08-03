@extends('layouts.app')

@section('page-style', 'resources/css/pages/admin/interviews/edit.css')
@section('page-class', 'page-pattern-form page-admin-interviews-edit')

@section('title', '面談記録詳細・編集')
@section('header-title', '面談管理')

@section('content')
    <section class="mx-auto max-w-5xl space-y-6">
        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-sm font-semibold text-slate-500">A-033</p>
                    <h1 class="mt-1 text-2xl font-bold text-slate-900">面談記録詳細・編集</h1>
                    <p class="mt-2 text-sm text-slate-600">
                        登録済みの面談記録を確認し、必要に応じて修正します。
                    </p>
                </div>

                <a
                    href="{{ route('admin.interviews.index', ['student_id' => $interviewRecord->student_id]) }}"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold hover:bg-slate-50"
                >
                    この生徒の面談履歴
                </a>
            </div>

            <form method="POST" action="{{ route('admin.interviews.update', $interviewRecord) }}" class="mt-6">
                @csrf
                @method('PUT')
                @include('admin.interviews._form')
            </form>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900">更新情報</h2>

            <dl class="mt-4 grid gap-5 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-semibold text-slate-500">作成者</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $interviewRecord->creator->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-semibold text-slate-500">作成日時</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $interviewRecord->created_at?->format('Y/m/d H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-semibold text-slate-500">最終更新者</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $interviewRecord->updater?->name ?? '未更新' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-semibold text-slate-500">最終更新日時</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $interviewRecord->updated_at?->format('Y/m/d H:i') }}</dd>
                </div>
            </dl>
        </div>
    </section>
@endsection
