@extends('layouts.app')

@section('page-class', 'page-pattern-list page-teacher-attendance-index')

@section('title', '担当授業別出欠一覧')
@section('header-title', '担当授業別出欠一覧')

@section('content')
    <section class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">

            <a
                href="{{ route('teacher.attendance.edit') }}"
                class="rounded-lg px-5 py-3 text-center font-semibold lms-button-primary"
            >
                出欠を登録
            </a>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="p-5 lms-panel">
                <p class="text-sm font-semibold lms-text-neutral-muted">
                    授業実施日
                </p>

                <p class="mt-2 text-3xl font-bold lms-text-neutral-strong">
                    {{ $lessonSessionCount }}件
                </p>
            </div>

            <div class="p-5 lms-panel">
                <p class="text-sm font-semibold lms-text-neutral-muted">
                    出欠未登録
                </p>

                <p class="mt-2 text-3xl font-bold {{ $missingCount > 0 ? 'lms-text-warning' : 'lms-text-success' }}">
                    {{ $missingCount }}件
                </p>
            </div>
        </div>

        <div class="p-6 lms-panel">
            <form method="GET" action="{{ route('teacher.attendance.index') }}" class="space-y-4">
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label
                            for="date_from"
                            class="block text-sm font-semibold lms-text-neutral-secondary"
                        >開始日</label>
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
                        <label for="date_to" class="block text-sm font-semibold lms-text-neutral-secondary">終了日</label>
                        <input
                            class="lms-form-control mt-2 block w-full rounded-lg border lms-border-neutral-default px-3
                                py-2"
                            id="date_to"
                            name="date_to"
                            type="date"
                            value="{{ $dateTo->format('Y-m-d') }}"
                        >
                    </div>

                    <div>
                        <label
                            for="course_id"
                            class="block text-sm font-semibold lms-text-neutral-secondary"
                        >担当授業</label>
                        <select
                            id="course_id"
                            name="course_id"
                            class="mt-2 block w-full rounded-lg border px-3 py-2 lms-form-control"
                        >
                            <option value="">すべて</option>
                            @foreach ($courses as $course)
                                <option
                                    value="{{ $course->id }}"
                                    @selected((string) $selectedCourseId === (string) $course->id)
                                >
                                    {{ $course->academic_year }} / {{ $course->course_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label
                            for="attendance_status"
                            class="block text-sm font-semibold lms-text-neutral-secondary"
                        >出欠区分</label>
                        <select
                            id="attendance_status"
                            name="attendance_status"
                            class="mt-2 block w-full rounded-lg border px-3 py-2 lms-form-control"
                        >
                            <option value="">すべて</option>
                            @foreach ($attendanceStatuses as $attendanceStatus)
                                <option
                                    value="{{ $attendanceStatus->value }}"
                                    @selected($selectedAttendanceStatus === $attendanceStatus)
                                >
                                    {{ $attendanceStatus->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <label class="inline-flex items-center gap-2 text-sm font-semibold lms-text-neutral-secondary">
                        <input type="checkbox" name="missing_only" value="1" @checked($missingOnly) class="rounded">
                        未登録がある授業日の記録のみ
                    </label>

                    <div class="flex gap-3">
                        <a
                            href="{{ route('teacher.attendance.index') }}"
                            class="rounded-lg border lms-border-neutral-default px-5 py-2.5 font-semibold
                                lms-text-neutral-secondary lms-hover-bg-neutral-subtle"
                        >
                            クリア
                        </a>

                        <button type="submit" class="rounded-lg px-5 py-2.5 font-semibold lms-button-primary">
                            検索
                        </button>
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
                            >授業日</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted">授業</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted">生徒</th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted
                                    lms-table-col--status"
                            >出欠区分</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted">備考</th>
                            <th
                                class="px-5 py-3 text-right text-xs font-semibold lms-text-neutral-muted
                                    lms-table-col--action"
                            >操作</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y lms-divide-neutral-subtle">
                        @forelse ($attendanceRecords as $record)
                            <tr>
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold lms-text-neutral-strong">
                                    {{ $record->lessonSession->lesson_date->format('Y/m/d') }}
                                </td>

                                <td class="px-5 py-4 text-sm lms-text-neutral-secondary">
                                    <div class="font-semibold lms-text-neutral-strong">
                                        {{ $record->lessonSession->timetableSlot->course->course_name }}
                                    </div>

                                    <div class="mt-1 text-xs lms-text-neutral-muted">
                                        {{ $record->lessonSession->timetableSlot->day_of_week->shortLabel() }}曜
                                        {{ $record->lessonSession->timetableSlot->period_no }}時限
                                    </div>
                                </td>

                                <td class="px-5 py-4 text-sm lms-text-neutral-secondary">
                                    <div class="font-semibold lms-text-neutral-strong">
                                        {{ $record->student->student_name }}
                                    </div>

                                    <div class="mt-1 text-xs lms-text-neutral-muted">
                                        {{ $record->student->student_no ?? '生徒番号未設定' }}
                                    </div>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold lms-text-neutral-strong">
                                    {{ $record->attendance_status->label() }}
                                </td>

                                <td class="px-5 py-4 text-sm lms-text-neutral-secondary">
                                    {{ $record->note ?? '-' }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm">
                                    <a
                                        href="{{ route('teacher.attendance.edit', [
                                            'timetable_slot_id' => $record->lessonSession->timetable_slot_id,
                                            'lesson_date' => $record->lessonSession->lesson_date->format('Y-m-d'),
                                        ]) }}"
                                        class="font-semibold lms-link-primary"
                                    >
                                        登録・編集
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-sm lms-text-neutral-muted">
                                    条件に一致する出欠履歴はありません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($attendanceRecords->hasPages())
                <div class="border-t lms-border-neutral-subtle px-6 py-4">
                    {{ $attendanceRecords->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
