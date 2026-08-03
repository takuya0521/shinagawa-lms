@extends('layouts.app')

@section('page-style', 'resources/css/pages/admin/attendance/student.css')
@section('page-class', 'page-pattern-detail page-admin-attendance-student')

@section('title', '生徒別出欠詳細')
@section('header-title', '生徒別出欠詳細')

@section('content')
    <section class="space-y-6">
        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-sm font-semibold text-slate-500">{{ $student->student_no }}</p>
                    <h1 class="mt-1 text-2xl font-bold">{{ $student->student_name }}</h1>
                    <p class="mt-1 text-sm text-slate-600">{{ $student->grade->label() }} / {{ $student->classGroup->class_name }}</p>
                </div>
                <a href="{{ route('admin.students.show', $student) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold">生徒詳細へ戻る</a>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl bg-white p-5 shadow-sm"><p class="text-sm font-semibold text-slate-500">講義日数</p><p class="mt-2 text-3xl font-bold">{{ $statistics->lessonCount }}</p></div>
            <div class="rounded-2xl bg-white p-5 shadow-sm"><p class="text-sm font-semibold text-slate-500">登録済み</p><p class="mt-2 text-3xl font-bold">{{ $statistics->recordedCount }}</p></div>
            <div class="rounded-2xl bg-white p-5 shadow-sm"><p class="text-sm font-semibold text-slate-500">未登録</p><p class="mt-2 text-3xl font-bold {{ $statistics->missingCount > 0 ? 'text-amber-700' : 'text-emerald-700' }}">{{ $statistics->missingCount }}</p></div>
            <div class="rounded-2xl bg-white p-5 shadow-sm"><p class="text-sm font-semibold text-slate-500">出席率</p><p class="mt-2 text-3xl font-bold">{{ $statistics->attendanceRateLabel() }}</p></div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('admin.students.attendance.show', $student) }}" class="grid gap-4 md:grid-cols-4">
                <div><label for="date_from" class="block text-sm font-semibold">開始日</label><input id="date_from" name="date_from" type="date" value="{{ $dateFrom->format('Y-m-d') }}" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"></div>
                <div><label for="date_to" class="block text-sm font-semibold">終了日</label><input id="date_to" name="date_to" type="date" value="{{ $dateTo->format('Y-m-d') }}" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"></div>
                <div><label for="course_id" class="block text-sm font-semibold">授業</label><select id="course_id" name="course_id" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"><option value="">すべて</option>@foreach ($courses as $course)<option value="{{ $course->id }}" @selected((string) $selectedCourseId === (string) $course->id)>{{ $course->academic_year }} / {{ $course->course_name }}</option>@endforeach</select></div>
                <div><label for="attendance_status" class="block text-sm font-semibold">区分</label><select id="attendance_status" name="attendance_status" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"><option value="">すべて</option>@foreach ($attendanceStatuses as $status)<option value="{{ $status->value }}" @selected($selectedAttendanceStatus === $status)>{{ $status->label() }}</option>@endforeach</select></div>
                <div class="flex justify-end gap-3 md:col-span-4"><a href="{{ route('admin.students.attendance.show', $student) }}" class="rounded-lg border border-slate-300 px-5 py-2.5 font-semibold">クリア</a><button class="rounded-lg bg-slate-900 px-5 py-2.5 font-semibold text-white">検索</button></div>
            </form>
        </div>

        <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50"><tr><th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">日付</th><th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">授業</th><th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">区分</th><th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">備考</th><th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">登録・修正者</th></tr></thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($attendanceRecords as $record)
                            <tr><td class="px-5 py-4 text-sm font-semibold">{{ $record->lessonSession->lesson_date->format('Y/m/d') }}</td><td class="px-5 py-4 text-sm">{{ $record->lessonSession->timetableSlot->course->course_name }}<div class="mt-1 text-xs text-slate-500">{{ $record->lessonSession->timetableSlot->period_no }}時限</div></td><td class="px-5 py-4 text-sm font-semibold">{{ $record->attendance_status->label() }}</td><td class="px-5 py-4 text-sm">{{ $record->note ?? '-' }}</td><td class="px-5 py-4 text-sm">{{ $record->recorder->name }}@if ($record->corrector !== null)<div class="mt-1 text-xs text-amber-700">修正: {{ $record->corrector->name }}</div>@endif</td></tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-12 text-center text-sm text-slate-500">出欠履歴はありません。</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($attendanceRecords->hasPages())<div class="border-t border-slate-200 px-6 py-4">{{ $attendanceRecords->links() }}</div>@endif
        </div>
    </section>
@endsection
