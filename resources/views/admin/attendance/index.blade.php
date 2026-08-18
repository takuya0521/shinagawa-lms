@extends('layouts.app')

@section('page-class', 'page-pattern-list page-admin-attendance-index')

@section('title', '出欠一覧・集計')
@section('header-title', '出欠一覧・集計')

@section('content')
    <section class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
            <a
                href="{{ route('admin.attendance.edit') }}"
                class="rounded-lg px-5 py-3 text-center font-semibold lms-button-primary"
            >
                日別出欠を確認・修正
            </a>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="p-5 lms-panel">
                <p class="text-sm font-semibold lms-text-neutral-muted">講義日数</p>
                <p class="mt-2 text-3xl font-bold">{{ $statistics->lessonCount }}</p>
            </div>
            <div class="p-5 lms-panel">
                <p class="text-sm font-semibold lms-text-neutral-muted">出欠登録</p>
                <p
                    class="mt-2 text-3xl font-bold"
                >{{ $statistics->recordedCount }}/{{ $statistics->expectedRecordCount }}</p>
            </div>
            <div class="p-5 lms-panel">
                <p class="text-sm font-semibold lms-text-neutral-muted">未登録</p>
                <p
                    class="mt-2 text-3xl font-bold {{ $statistics->missingCount > 0 ? 'lms-text-warning' :
                        'lms-text-success' }}"
                >{{ $statistics->missingCount }}</p>
            </div>
            <div class="p-5 lms-panel">
                <p class="text-sm font-semibold lms-text-neutral-muted">出席率</p>
                <p class="mt-2 text-3xl font-bold">{{ $statistics->attendanceRateLabel() }}</p>
                @unless ($statistics->canCalculateAttendanceRate())
                    <p class="mt-2 text-xs lms-text-neutral-muted">出席率を算出できないデータが含まれています。</p>
                @endunless
            </div>
        </div>

        <div class="p-6 lms-panel">
            <form method="GET" action="{{ route('admin.attendance.index') }}" class="space-y-4">
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
                                <option
                                    value="{{ $student->id }}"
                                    @selected((string) $selectedStudentId === (string) $student->id)
                                >{{ $student->student_no }} / {{ $student->student_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="course_id" class="block text-sm font-semibold lms-text-neutral-secondary">授業</label>
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
                                >{{ $course->academic_year }} / {{ $course->course_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="grade" class="block text-sm font-semibold lms-text-neutral-secondary">学年</label>
                        <select
                            id="grade"
                            name="grade"
                            class="mt-2 block w-full rounded-lg border px-3 py-2 lms-form-control"
                        >
                            <option value="">すべて</option>
                            @foreach ($grades as $grade)
                                <option
                                    value="{{ $grade->value }}"
                                    @selected($selectedGrade === $grade)
                                >{{ $grade->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label
                            for="class_group_id"
                            class="block text-sm font-semibold lms-text-neutral-secondary"
                        >クラス</label>
                        <select
                            id="class_group_id"
                            name="class_group_id"
                            class="mt-2 block w-full rounded-lg border px-3 py-2 lms-form-control"
                        >
                            <option value="">すべて</option>
                            @foreach ($classGroups as $classGroup)
                                <option
                                    value="{{ $classGroup->id }}"
                                    @selected((string) $selectedClassGroupId === (string) $classGroup->id)
                                >{{ $classGroup->class_name }}</option>
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
                                >{{ $attendanceStatus->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-3">
                    <a
                        href="{{ route('admin.attendance.index') }}"
                        class="rounded-lg border lms-border-neutral-default px-5 py-2.5 font-semibold
                            lms-text-neutral-secondary lms-hover-bg-neutral-subtle"
                    >クリア</a>
                    <button type="submit" class="rounded-lg px-5 py-2.5 font-semibold lms-button-primary">検索</button>
                </div>
            </form>
        </div>

        <div class="p-5 lms-panel">
            <div class="flex flex-wrap gap-4 text-sm">
                <span>出席 <strong>{{ $statistics->presentCount }}</strong></span>
                <span>欠席 <strong>{{ $statistics->absentCount }}</strong></span>
                <span>遅刻 <strong>{{ $statistics->lateCount }}</strong></span>
                <span>早退 <strong>{{ $statistics->earlyLeaveCount }}</strong></span>
            </div>
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
                            <th class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted">生徒</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted">授業</th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted
                                    lms-table-col--status"
                            >区分</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted">登録・修正者</th>
                            <th
                                class="px-5 py-3 text-right text-xs font-semibold lms-text-neutral-muted
                                    lms-table-col--action-label"
                            >操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y lms-divide-neutral-subtle">
                        @forelse ($attendanceRecords as $attendanceRecord)
                            <tr>
                                <td
                                    class="whitespace-nowrap px-5 py-4 text-sm font-semibold lms-text-neutral-strong"
                                >{{ $attendanceRecord->lessonSession->lesson_date->format('Y/m/d') }}</td>
                                <td class="px-5 py-4 text-sm lms-text-neutral-secondary">
                                    <a
                                        href="{{ route('admin.students.attendance.show', $attendanceRecord->student) }}"
                                        class="font-semibold lms-link-primary"
                                    >{{ $attendanceRecord->student->student_name }}</a>
                                    <div
                                        class="mt-1 text-xs lms-text-neutral-muted"
                                    >{{ $attendanceRecord->student->student_no }}</div>
                                </td>
                                <td class="px-5 py-4 text-sm lms-text-neutral-secondary">
                                    <div
                                        class="font-semibold lms-text-neutral-strong"
                                    >{{ $attendanceRecord->lessonSession->timetableSlot->course->course_name }}</div>
                                    <div
                                        class="mt-1 text-xs lms-text-neutral-muted"
                                    >{{ $attendanceRecord->lessonSession->timetableSlot->period_no }}時限 / {{
                                    $attendanceRecord->lessonSession->timetableSlot->course->classGroup->class_name
                                    }}</div>
                                </td>
                                <td
                                    class="whitespace-nowrap px-5 py-4 text-sm font-semibold"
                                >{{ $attendanceRecord->attendance_status->label() }}</td>
                                <td class="px-5 py-4 text-sm lms-text-neutral-secondary">
                                    {{ $attendanceRecord->recorder->name }}
                                    @if ($attendanceRecord->corrector !== null)
                                        <div
                                            class="mt-1 text-xs lms-text-warning"
                                        >修正: {{ $attendanceRecord->corrector->name }}</div>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm">
                                    <a
                                        href="{{
                                            route('admin.attendance.edit', ['lesson_session_id' =>
                                            $attendanceRecord->lesson_session_id])
                                        }}"
                                        class="font-semibold lms-link-primary"
                                    >日別確認・修正</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-sm lms-text-neutral-muted">
                                    条件に一致する出欠記録はありません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($attendanceRecords->hasPages())
                <div class="border-t lms-border-neutral-subtle px-6 py-4">{{ $attendanceRecords->links() }}</div>
            @endif
        </div>
    </section>
@endsection
