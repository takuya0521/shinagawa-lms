@extends('layouts.app')

@section('page-class', 'page-pattern-form page-admin-interviews-edit')

@section('title', '面談記録詳細・編集')
@section('header-title', '面談管理')

@section('content')
    <section class="mx-auto max-w-5xl space-y-6">
        <div class="p-6 lms-panel">
            <div
                class="flex flex-col gap-4 border-b lms-border-neutral-subtle pb-5 sm:flex-row sm:items-start
                    sm:justify-between"
            >
                <div>
                    <h1 class="text-2xl font-bold lms-text-neutral-strong">面談記録詳細・編集</h1>
                </div>

                <a
                    href="{{ route('admin.interviews.index', ['student_id' => $interviewRecord->student_id]) }}"
                    class="rounded-lg border lms-border-neutral-default px-4 py-2 text-sm font-semibold
                        lms-hover-bg-neutral-subtle"
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

        <div class="p-6 lms-panel">
            <h2 class="text-lg font-bold lms-text-neutral-strong">更新情報</h2>

            <dl class="mt-4 grid gap-5 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-semibold lms-text-neutral-muted">作成者</dt>
                    <dd class="mt-1 text-sm lms-text-neutral-strong">{{ $interviewRecord->creator->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-semibold lms-text-neutral-muted">作成日時</dt>
                    <dd
                        class="mt-1 text-sm lms-text-neutral-strong"
                    >{{ $interviewRecord->created_at?->format('Y/m/d H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-semibold lms-text-neutral-muted">最終更新者</dt>
                    <dd
                        class="mt-1 text-sm lms-text-neutral-strong"
                    >{{ $interviewRecord->updater?->name ?? '未更新' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-semibold lms-text-neutral-muted">最終更新日時</dt>
                    <dd
                        class="mt-1 text-sm lms-text-neutral-strong"
                    >{{ $interviewRecord->updated_at?->format('Y/m/d H:i') }}</dd>
                </div>
            </dl>
        </div>
    </section>
@endsection
