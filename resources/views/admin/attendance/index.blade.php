@extends('layouts.app')

@section('page-style', 'resources/css/pages/admin/attendance/index.css')
@section('page-class', 'page-pattern-list page-admin-attendance-index')

@section('title', '出欠一覧・集計')
@section('header-title', '出欠一覧・集計')

@section('content')
    <section class="space-y-6">
        <div class="flex flex-col gap-4 rounded-2xl bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">出欠一覧・集計</h1>
                <p class="mt-2 text-sm text-slate-600">日別・生徒別・授業別に出欠記録を検索し、登録状況を確認します。</p>
            </div>
            <a href="{{ route('admin.attendance.edit') }}" class="rounded-lg bg-slate-900 px-5 py-3 text-center font-semibold text-white hover:bg-slate-700">
                日別出欠を確認・修正
            </a>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">講義日数</p>
                <p class="mt-2 text-3xl font-bold">{{ $statistics->lessonCount }}</p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">出欠登録</p>
                <p class="mt-2 text-3xl font-bold">{{ $statistics->recordedCount }}/{{ $statistics->expectedRecordCount }}</p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">未登録</p>
                <p class="mt-2 text-3xl font-bold {{ $statistics->missingCount > 0 ? 'text-amber-700' : 'text-emerald-700' }}">{{ $statistics->missingCount }}</p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">出席率</p>
                <p class="mt-2 text-3xl font-bold">{{ $statistics->attendanceRateLabel() }}</p>
                @unless ($statistics->canCalculateAttendanceRate())
                    <p class="mt-2 text-xs text-slate-500">未登録、遅刻、早退を含む場合は未決仕様のため算出しません。</p>
                @endunless
            </div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('admin.attendance.index') }}" class="space-y-4">
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
                        <label for="student_id" class="block text-sm font-semibold text-slate-700">生徒</label>
                        <select id="student_id" name="student_id" class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">すべて</option>
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}" @selected((string) $selectedStudentId === (string) $student->id)>{{ $student->student_no }} / {{ $student->student_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="course_id" class="block text-sm font-semibold text-slate-700">授業</label>
                        <select id="course_id" name="course_id" class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">すべて</option>
                            @foreach ($courses as $course)
                                <option value="{{ $course->id }}" @selected((string) $selectedCourseId === (string) $course->id)>{{ $course->academic_year }} / {{ $course->course_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="grade" class="block text-sm font-semibold text-slate-700">学年</label>
                        <select id="grade" name="grade" class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">すべて</option>
                            @foreach ($grades as $grade)
                                <option value="{{ $grade->value }}" @selected($selectedGrade === $grade)>{{ $grade->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="class_group_id" class="block text-sm font-semibold text-slate-700">クラス</label>
                        <select id="class_group_id" name="class_group_id" class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">すべて</option>
                            @foreach ($classGroups as $classGroup)
                                <option value="{{ $classGroup->id }}" @selected((string) $selectedClassGroupId === (string) $classGroup->id)>{{ $classGroup->class_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="attendance_status" class="block text-sm font-semibold text-slate-700">出欠区分</label>
                        <select id="attendance_status" name="attendance_status" class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">すべて</option>
                            @foreach ($attendanceStatuses as $attendanceStatus)
                                <option value="{{ $attendanceStatus->value }}" @selected($selectedAttendanceStatus === $attendanceStatus)>{{ $attendanceStatus->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-3">
                    <a href="{{ route('admin.attendance.index') }}" class="rounded-lg border border-slate-300 px-5 py-2.5 font-semibold text-slate-700 hover:bg-slate-50">クリア</a>
                    <button type="submit" class="rounded-lg bg-slate-900 px-5 py-2.5 font-semibold text-white hover:bg-slate-700">検索</button>
                </div>
            </form>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-sm">
            <div class="flex flex-wrap gap-4 text-sm">
                <span>出席 <strong>{{ $statistics->presentCount }}</strong></span>
                <span>欠席 <strong>{{ $statistics->absentCount }}</strong></span>
                <span>遅刻 <strong>{{ $statistics->lateCount }}</strong></span>
                <span>早退 <strong>{{ $statistics->earlyLeaveCount }}</strong></span>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">授業日</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">生徒</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">授業</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">区分</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">登録・修正者</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-slate-500">操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($attendanceRecords as $attendanceRecord)
                            <tr>
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-900">{{ $attendanceRecord->lessonSession->lesson_date->format('Y/m/d') }}</td>
                                <td class="px-5 py-4 text-sm text-slate-700">
                                    <a href="{{ route('admin.students.attendance.show', $attendanceRecord->student) }}" class="font-semibold text-blue-700 hover:text-blue-900">{{ $attendanceRecord->student->student_name }}</a>
                                    <div class="mt-1 text-xs text-slate-500">{{ $attendanceRecord->student->student_no }}</div>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-700">
                                    <div class="font-semibold text-slate-900">{{ $attendanceRecord->lessonSession->timetableSlot->course->course_name }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ $attendanceRecord->lessonSession->timetableSlot->period_no }}時限 / {{ $attendanceRecord->lessonSession->timetableSlot->course->classGroup->class_name }}</div>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold">{{ $attendanceRecord->attendance_status->label() }}</td>
                                <td class="px-5 py-4 text-sm text-slate-700">
                                    {{ $attendanceRecord->recorder->name }}
                                    @if ($attendanceRecord->corrector !== null)
                                        <div class="mt-1 text-xs text-amber-700">修正: {{ $attendanceRecord->corrector->name }}</div>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm">
                                    <a href="{{ route('admin.attendance.edit', ['lesson_session_id' => $attendanceRecord->lesson_session_id]) }}" class="font-semibold text-blue-700 hover:text-blue-900">日別確認・修正</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-12 text-center text-sm text-slate-500">条件に一致する出欠記録はありません。</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($attendanceRecords->hasPages())
                <div class="border-t border-slate-200 px-6 py-4">{{ $attendanceRecords->links() }}</div>
            @endif
        </div>
    </section>
@endsection
