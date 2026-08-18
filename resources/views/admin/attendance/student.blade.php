@extends('layouts.app')

@section('page-class', 'page-pattern-detail page-admin-attendance-student')

@section('title', '生徒別出欠詳細')
@section('header-title', '生徒別出欠詳細')

@section('content')
    <section class="space-y-6">
        <div class="p-6 lms-panel">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-sm font-semibold lms-text-neutral-muted">
                        {{ $student->student_no }}
                    </p>
                    <h1 class="mt-1 text-2xl font-bold">
                        {{ $student->student_name }}
                    </h1>
                    <p class="mt-1 text-sm lms-text-neutral-subtle">
                        {{ $student->grade->label() }} / {{ $student->classGroup->class_name }}
                    </p>
                </div>

                <a
                    href="{{ route('admin.students.show', $student) }}"
                    class="rounded-lg border px-4 py-2 text-sm font-semibold lms-border-neutral-default"
                >
                    生徒詳細へ戻る
                </a>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="p-5 lms-panel">
                <p class="text-sm font-semibold lms-text-neutral-muted">講義日数</p>
                <p class="mt-2 text-3xl font-bold">{{ $statistics->lessonCount }}</p>
            </div>
            <div class="p-5 lms-panel">
                <p class="text-sm font-semibold lms-text-neutral-muted">登録済み</p>
                <p class="mt-2 text-3xl font-bold">{{ $statistics->recordedCount }}</p>
            </div>
            <div class="p-5 lms-panel">
                <p class="text-sm font-semibold lms-text-neutral-muted">未登録</p>
                <p
                    @class([
                        'mt-2 text-3xl font-bold',
                        'lms-text-warning' => $statistics->missingCount > 0,
                        'lms-text-success' => $statistics->missingCount === 0,
                    ])
                >
                    {{ $statistics->missingCount }}
                </p>
            </div>
            <div class="p-5 lms-panel">
                <p class="text-sm font-semibold lms-text-neutral-muted">出席率</p>
                <p class="mt-2 text-3xl font-bold">{{ $statistics->attendanceRateLabel() }}</p>
            </div>
        </div>

        <div class="p-6 lms-panel">
            <form
                method="GET"
                action="{{ route('admin.students.attendance.show', $student) }}"
                class="grid gap-4 md:grid-cols-4"
            >
                <div>
                    <label for="date_from" class="block text-sm font-semibold">開始日</label>
                    <input
                        id="date_from"
                        name="date_from"
                        type="date"
                        value="{{ $dateFrom->format('Y-m-d') }}"
                        class="mt-2 w-full rounded-lg border px-3 py-2 lms-form-control lms-border-neutral-default"
                    >
                </div>

                <div>
                    <label for="date_to" class="block text-sm font-semibold">終了日</label>
                    <input
                        id="date_to"
                        name="date_to"
                        type="date"
                        value="{{ $dateTo->format('Y-m-d') }}"
                        class="mt-2 w-full rounded-lg border px-3 py-2 lms-form-control lms-border-neutral-default"
                    >
                </div>

                <div>
                    <label for="course_id" class="block text-sm font-semibold">授業</label>
                    <select
                        id="course_id"
                        name="course_id"
                        class="mt-2 w-full rounded-lg border px-3 py-2 lms-form-control"
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
                    <label for="attendance_status" class="block text-sm font-semibold">区分</label>
                    <select
                        id="attendance_status"
                        name="attendance_status"
                        class="mt-2 w-full rounded-lg border px-3 py-2 lms-form-control"
                    >
                        <option value="">すべて</option>
                        @foreach ($attendanceStatuses as $status)
                            <option
                                value="{{ $status->value }}"
                                @selected($selectedAttendanceStatus === $status)
                            >
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex justify-end gap-3 md:col-span-4">
                    <a
                        href="{{ route('admin.students.attendance.show', $student) }}"
                        class="rounded-lg border px-5 py-2.5 font-semibold lms-border-neutral-default"
                    >
                        クリア
                    </a>
                    <button
                        type="submit"
                        class="rounded-lg px-5 py-2.5 font-semibold lms-button-primary"
                    >
                        検索
                    </button>
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
                            >日付</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted">授業</th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted
                                    lms-table-col--status"
                            >区分</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted">備考</th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted
                                    lms-table-col--medium"
                            >
                                登録・修正者
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y lms-divide-neutral-subtle">
                        @forelse ($attendanceRecords as $record)
                            <tr>
                                <td class="px-5 py-4 text-sm font-semibold">
                                    {{ $record->lessonSession->lesson_date->format('Y/m/d') }}
                                </td>
                                <td class="px-5 py-4 text-sm">
                                    {{ $record->lessonSession->timetableSlot->course->course_name }}
                                    <div class="mt-1 text-xs lms-text-neutral-muted">
                                        {{ $record->lessonSession->timetableSlot->period_no }}時限
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-sm font-semibold">
                                    {{ $record->attendance_status->label() }}
                                </td>
                                <td class="px-5 py-4 text-sm">
                                    {{ $record->note ?? '-' }}
                                </td>
                                <td class="px-5 py-4 text-sm">
                                    {{ $record->recorder->name }}
                                    @if ($record->corrector !== null)
                                        <div class="mt-1 text-xs lms-text-warning">
                                            修正: {{ $record->corrector->name }}
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-sm lms-text-neutral-muted">
                                    出欠履歴はありません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($attendanceRecords->hasPages())
                <div class="border-t px-6 py-4 lms-border-neutral-subtle">
                    {{ $attendanceRecords->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
