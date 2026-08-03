@extends('layouts.app')

@section('page-style', 'resources/css/pages/teacher/attendance/index.css')
@section('page-class', 'page-pattern-list page-teacher-attendance-index')

@section('title', '担当授業別出欠一覧')
@section('header-title', '担当授業別出欠一覧')

@section('content')
    <section class="space-y-6">
        <div class="flex flex-col gap-4 rounded-2xl bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    担当授業別出欠一覧
                </h1>

                <p class="mt-2 text-sm text-slate-600">
                    担当授業の生徒別出欠履歴と未登録件数を確認します。
                </p>
            </div>

            <a
                href="{{ route('teacher.attendance.edit') }}"
                class="rounded-lg bg-slate-900 px-5 py-3 text-center font-semibold text-white hover:bg-slate-700"
            >
                出欠を登録
            </a>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">
                    授業実施日
                </p>

                <p class="mt-2 text-3xl font-bold text-slate-900">
                    {{ $lessonSessionCount }}件
                </p>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">
                    出欠未登録
                </p>

                <p class="mt-2 text-3xl font-bold {{ $missingCount > 0 ? 'text-amber-700' : 'text-emerald-700' }}">
                    {{ $missingCount }}件
                </p>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('teacher.attendance.index') }}" class="space-y-4">
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label for="date_from" class="block text-sm font-semibold text-slate-700">開始日</label>
                        <input id="date_from" name="date_from" type="date" value="{{ $dateFrom->format('Y-m-d') }}" class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>

                    <div>
                        <label for="date_to" class="block text-sm font-semibold text-slate-700">終了日</label>
                        <input id="date_to" name="date_to" type="date" value="{{ $dateTo->format('Y-m-d') }}" class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>

                    <div>
                        <label for="course_id" class="block text-sm font-semibold text-slate-700">担当授業</label>
                        <select id="course_id" name="course_id" class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">すべて</option>
                            @foreach ($courses as $course)
                                <option value="{{ $course->id }}" @selected((string) $selectedCourseId === (string) $course->id)>
                                    {{ $course->academic_year }} / {{ $course->course_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="attendance_status" class="block text-sm font-semibold text-slate-700">出欠区分</label>
                        <select id="attendance_status" name="attendance_status" class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">すべて</option>
                            @foreach ($attendanceStatuses as $attendanceStatus)
                                <option value="{{ $attendanceStatus->value }}" @selected($selectedAttendanceStatus === $attendanceStatus)>
                                    {{ $attendanceStatus->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <input type="checkbox" name="missing_only" value="1" @checked($missingOnly) class="rounded border-slate-300">
                        未登録がある授業日の記録のみ
                    </label>

                    <div class="flex gap-3">
                        <a href="{{ route('teacher.attendance.index') }}" class="rounded-lg border border-slate-300 px-5 py-2.5 font-semibold text-slate-700 hover:bg-slate-50">
                            クリア
                        </a>

                        <button type="submit" class="rounded-lg bg-slate-900 px-5 py-2.5 font-semibold text-white hover:bg-slate-700">
                            検索
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">授業日</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">授業</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">生徒</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">出欠区分</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">備考</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-slate-500">操作</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200">
                        @forelse ($attendanceRecords as $record)
                            <tr>
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-900">
                                    {{ $record->lessonSession->lesson_date->format('Y/m/d') }}
                                </td>

                                <td class="px-5 py-4 text-sm text-slate-700">
                                    <div class="font-semibold text-slate-900">
                                        {{ $record->lessonSession->timetableSlot->course->course_name }}
                                    </div>

                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ $record->lessonSession->timetableSlot->day_of_week->shortLabel() }}曜
                                        {{ $record->lessonSession->timetableSlot->period_no }}時限
                                    </div>
                                </td>

                                <td class="px-5 py-4 text-sm text-slate-700">
                                    <div class="font-semibold text-slate-900">
                                        {{ $record->student->student_name }}
                                    </div>

                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ $record->student->student_no ?? '生徒番号未設定' }}
                                    </div>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-900">
                                    {{ $record->attendance_status->label() }}
                                </td>

                                <td class="px-5 py-4 text-sm text-slate-700">
                                    {{ $record->note ?? '-' }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm">
                                    <a
                                        href="{{ route('teacher.attendance.edit', [
                                            'timetable_slot_id' => $record->lessonSession->timetable_slot_id,
                                            'lesson_date' => $record->lessonSession->lesson_date->format('Y-m-d'),
                                        ]) }}"
                                        class="font-semibold text-blue-700 hover:text-blue-900"
                                    >
                                        登録・編集
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-500">
                                    条件に一致する出欠履歴はありません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($attendanceRecords->hasPages())
                <div class="border-t border-slate-200 px-6 py-4">
                    {{ $attendanceRecords->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
