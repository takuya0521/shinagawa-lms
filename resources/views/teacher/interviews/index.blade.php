@extends('layouts.app')

@section('page-style', 'resources/css/pages/teacher/interviews/index.css')
@section('page-class', 'page-pattern-list page-teacher-interviews-index')

@section('title', '面談履歴一覧')
@section('header-title', '面談管理')

@section('content')
    <section class="space-y-6">
        <div class="flex flex-col gap-4 rounded-2xl bg-white p-6 shadow-sm sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-slate-500">T-008</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-900">面談履歴一覧</h1>
                <p class="mt-2 text-sm text-slate-600">
                    現在の担当生徒に登録した面談履歴を検索・確認します。
                </p>
            </div>

            <a href="{{ route('teacher.interviews.create') }}" class="inline-flex shrink-0 items-center justify-center rounded-lg bg-slate-900 px-5 py-3 font-semibold text-white hover:bg-slate-700">
                面談記録を登録
            </a>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('teacher.interviews.index') }}" class="space-y-4">
                <div class="grid gap-4 lg:grid-cols-4">
                    <div>
                        <label for="date_from" class="block text-sm font-semibold text-slate-700">面談日（開始）</label>
                        <input id="date_from" name="date_from" type="date" value="{{ $dateFrom->format('Y-m-d') }}" class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label for="date_to" class="block text-sm font-semibold text-slate-700">面談日（終了）</label>
                        <input id="date_to" name="date_to" type="date" value="{{ $dateTo->format('Y-m-d') }}" class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div class="lg:col-span-2">
                        <label for="keyword" class="block text-sm font-semibold text-slate-700">キーワード</label>
                        <input id="keyword" name="keyword" value="{{ $keyword }}" placeholder="生徒番号・氏名・面談メモ・次回対応" class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                </div>

                <div class="grid gap-4 lg:grid-cols-4">
                    <div>
                        <label for="student_id" class="block text-sm font-semibold text-slate-700">生徒</label>
                        <select id="student_id" name="student_id" class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">すべて</option>
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}" @selected($selectedStudentId === $student->id)>
                                    {{ $student->student_no ?? '番号未設定' }} / {{ $student->student_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="interview_type" class="block text-sm font-semibold text-slate-700">面談種別</label>
                        <input id="interview_type" name="interview_type" list="interview-type-options" value="{{ $selectedInterviewType }}" maxlength="30" class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2">
                        <datalist id="interview-type-options">
                            @foreach ($interviewTypes as $interviewType)
                                <option value="{{ $interviewType }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                    <div class="flex items-end gap-3 lg:col-span-2">
                        <button type="submit" class="rounded-lg bg-slate-900 px-5 py-2.5 font-semibold text-white">検索</button>
                        <a href="{{ route('teacher.interviews.index') }}" class="rounded-lg border border-slate-300 px-5 py-2.5 font-semibold hover:bg-slate-50">クリア</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">面談日・種別</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">生徒</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">次回対応</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">関連リンク</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-slate-500">操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($interviewRecords as $interviewRecord)
                            <tr class="align-top hover:bg-slate-50">
                                <td class="whitespace-nowrap px-5 py-4 text-sm">
                                    <p class="font-semibold text-slate-900">{{ $interviewRecord->interview_date->format('Y/m/d') }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $interviewRecord->interview_type ?? '種別未設定' }}</p>
                                </td>
                                <td class="px-5 py-4 text-sm">
                                    <p class="font-semibold text-slate-900">{{ $interviewRecord->student->student_name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $interviewRecord->student->student_no ?? '番号未設定' }} / {{ $interviewRecord->student->classGroup->class_name }}</p>
                                </td>
                                <td class="max-w-md px-5 py-4 text-sm text-slate-700">
                                    {{ $interviewRecord->next_action !== null ? \Illuminate\Support\Str::limit($interviewRecord->next_action, 120) : '-' }}
                                </td>
                                <td class="px-5 py-4 text-sm">
                                    <div class="flex flex-col items-start gap-2">
                                        @if ($interviewRecord->drive_url !== null)
                                            <a href="{{ $interviewRecord->drive_url }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-blue-700 hover:text-blue-900">Driveを開く</a>
                                        @endif
                                        @if ($interviewRecord->meet_url !== null)
                                            <a href="{{ $interviewRecord->meet_url }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-blue-700 hover:text-blue-900">Meetを開く</a>
                                        @endif
                                        @if ($interviewRecord->drive_url === null && $interviewRecord->meet_url === null)
                                            <span class="text-slate-400">未設定</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm">
                                    <a href="{{ route('teacher.interviews.edit', $interviewRecord) }}" class="font-semibold text-blue-700 hover:text-blue-900">詳細・編集</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-sm text-slate-500">条件に一致する面談記録はありません。</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($interviewRecords->hasPages())
                <div class="border-t border-slate-200 px-6 py-4">{{ $interviewRecords->links() }}</div>
            @endif
        </div>
    </section>
@endsection
