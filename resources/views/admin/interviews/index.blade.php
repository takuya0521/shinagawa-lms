@extends('layouts.app')

@section('page-class', 'page-pattern-list page-admin-interviews-index')

@section('title', '面談記録一覧')
@section('header-title', '面談管理')

@section('content')
    <section class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">

            <a
                href="{{ route('admin.interviews.create') }}"
                class="inline-flex shrink-0 items-center justify-center rounded-lg px-5 py-3 font-semibold
                    lms-button-primary"
            >
                面談記録を登録
            </a>
        </div>

        <div class="p-6 lms-panel">
            <form method="GET" action="{{ route('admin.interviews.index') }}" class="space-y-4">
                <div class="grid gap-4 lg:grid-cols-4">
                    <div>
                        <label
                            for="date_from"
                            class="block text-sm font-semibold lms-text-neutral-secondary"
                        >面談日（開始）</label>
                        <input
                            class="lms-form-control mt-2 block w-full rounded-lg border lms-border-neutral-default px-3
                                py-2"
                            id="date_from"
                            name="date_from"
                            type="date"
                            value="{{ $dateFrom->format('Y-m-d') }}"
                        >
                    </div>
                    <div>
                        <label
                            for="date_to"
                            class="block text-sm font-semibold lms-text-neutral-secondary"
                        >面談日（終了）</label>
                        <input
                            class="lms-form-control mt-2 block w-full rounded-lg border lms-border-neutral-default px-3
                                py-2"
                            id="date_to"
                            name="date_to"
                            type="date"
                            value="{{ $dateTo->format('Y-m-d') }}"
                        >
                    </div>
                    <div class="lg:col-span-2">
                        <label
                            for="keyword"
                            class="block text-sm font-semibold lms-text-neutral-secondary"
                        >キーワード</label>
                        <input
                            id="keyword"
                            name="keyword"
                            value="{{ $keyword }}"
                            placeholder="生徒番号・氏名・担当教員・面談メモ・次回対応"
                            class="mt-2 block w-full rounded-lg border px-3 py-2 lms-form-control"
                        >
                    </div>
                </div>

                <div class="grid gap-4 lg:grid-cols-4">
                    <div>
                        <label
                            for="student_id"
                            class="block text-sm font-semibold lms-text-neutral-secondary"
                        >生徒</label>
                        <select
                            id="student_id"
                            name="student_id"
                            class="mt-2 block w-full rounded-lg border px-3 py-2 lms-form-control"
                        >
                            <option value="">すべて</option>
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}" @selected($selectedStudentId === $student->id)>
                                    {{ $student->student_no ?? '番号未設定' }} / {{ $student->student_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label
                            for="teacher_id"
                            class="block text-sm font-semibold lms-text-neutral-secondary"
                        >担当教員</label>
                        <select
                            id="teacher_id"
                            name="teacher_id"
                            class="mt-2 block w-full rounded-lg border px-3 py-2 lms-form-control"
                        >
                            <option value="">すべて</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}" @selected($selectedTeacherId === $teacher->id)>
                                    {{ $teacher->user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label
                            for="interview_type"
                            class="block text-sm font-semibold lms-text-neutral-secondary"
                        >面談種別</label>
                        <input
                            id="interview_type"
                            name="interview_type"
                            list="interview-type-options"
                            value="{{ $selectedInterviewType }}"
                            maxlength="30"
                            class="mt-2 block w-full rounded-lg border px-3 py-2 lms-form-control"
                        >
                        <datalist id="interview-type-options">
                            @foreach ($interviewTypes as $interviewType)
                                <option value="{{ $interviewType }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                    <div class="flex items-end gap-3">
                        <button
                            type="submit"
                            class="rounded-lg px-5 py-2.5 font-semibold lms-button-primary"
                        >検索</button>
                        <a
                            href="{{ route('admin.interviews.index') }}"
                            class="rounded-lg border lms-border-neutral-default px-5 py-2.5 font-semibold
                                lms-hover-bg-neutral-subtle"
                        >クリア</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="overflow-hidden lms-panel">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y lms-divide-neutral-subtle lms-table lms-table--balanced">
                    <thead class="lms-bg-neutral-subtle">
                        <tr>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted
                                    lms-table-col--date"
                            >面談日・種別</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted">生徒</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted">担当教員</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted">次回対応</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted">関連リンク</th>
                            <th
                                class="px-5 py-3 text-right text-xs font-semibold lms-text-neutral-muted
                                    lms-table-col--action"
                            >操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y lms-divide-neutral-subtle">
                        @forelse ($interviewRecords as $interviewRecord)
                            <tr class="align-top lms-hover-bg-neutral-subtle">
                                <td class="whitespace-nowrap px-5 py-4 text-sm">
                                    <p
                                        class="font-semibold lms-text-neutral-strong"
                                    >{{ $interviewRecord->interview_date->format('Y/m/d') }}</p>
                                    <p
                                        class="mt-1 text-xs lms-text-neutral-muted"
                                    >{{ $interviewRecord->interview_type ?? '種別未設定' }}</p>
                                </td>
                                <td class="px-5 py-4 text-sm">
                                    <p
                                        class="font-semibold lms-text-neutral-strong"
                                    >{{ $interviewRecord->student->student_name }}</p>
                                    <p class="mt-1 text-xs lms-text-neutral-muted">
                                        {{ $interviewRecord->student->student_no ?? '番号未設定' }} / {{
                                        $interviewRecord->student->grade->label() }} / {{
                                        $interviewRecord->student->classGroup->class_name }}
                                    </p>
                                </td>
                                <td class="px-5 py-4 text-sm lms-text-neutral-secondary">
                                    {{ $interviewRecord->teacher?->user?->name ?? '未設定' }}
                                </td>
                                <td class="max-w-sm px-5 py-4 text-sm lms-text-neutral-secondary">
                                    {{ $interviewRecord->next_action !== null ?
                                    \Illuminate\Support\Str::limit($interviewRecord->next_action, 100) : '-' }}
                                </td>
                                <td class="px-5 py-4 text-sm">
                                    <div class="flex flex-col items-start gap-2">
                                        @if ($interviewRecord->drive_url !== null)
                                            <a
                                                href="{{ $interviewRecord->drive_url }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="font-semibold lms-link-primary"
                                            >Driveを開く</a>
                                        @endif
                                        @if ($interviewRecord->meet_url !== null)
                                            <a
                                                href="{{ $interviewRecord->meet_url }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="font-semibold lms-link-primary"
                                            >Meetを開く</a>
                                        @endif
                                        @if ($interviewRecord->drive_url === null && $interviewRecord->meet_url ===
                                        null)
                                            <span class="lms-text-neutral-disabled">未設定</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm">
                                    <a
                                        href="{{ route('admin.interviews.edit', $interviewRecord) }}"
                                        class="font-semibold lms-link-primary"
                                    >詳細・編集</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="6"
                                    class="px-6 py-12 text-center text-sm lms-text-neutral-muted"
                                >条件に一致する面談記録はありません。</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($interviewRecords->hasPages())
                <div class="border-t lms-border-neutral-subtle px-6 py-4">{{ $interviewRecords->links() }}</div>
            @endif
        </div>
    </section>
@endsection
