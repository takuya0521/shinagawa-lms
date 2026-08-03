@extends('layouts.app')

@section('page-style', 'resources/css/pages/admin/attendance/edit.css')
@section('page-class', 'page-pattern-attendance-entry page-admin-attendance-edit')

@section('title', '日別出欠確認・修正')
@section('header-title', '日別出欠確認・修正')

@section('content')
    <section class="space-y-6">
        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-bold text-slate-900">日別出欠確認・修正</h1>
            <p class="mt-2 text-sm text-slate-600">授業実施日を選択し、教員が登録した出欠を確認・修正します。</p>
        </div>


        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('admin.attendance.edit') }}" class="grid gap-4 md:grid-cols-[minmax(0,1fr)_220px_auto]">
                <div>
                    <label for="timetable_slot_id" class="block text-sm font-semibold text-slate-700">授業・時間割枠</label>
                    <select id="timetable_slot_id" name="timetable_slot_id" required class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="">選択してください</option>
                        @foreach ($timetableSlots as $slot)
                            <option value="{{ $slot->id }}" @selected((string) old('timetable_slot_id', $selectedSlot?->id) === (string) $slot->id)>
                                {{ $slot->day_of_week->shortLabel() }}曜 {{ $slot->period_no }}時限 / {{ $slot->course->course_name }} / {{ $slot->course->classGroup->class_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('timetable_slot_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="lesson_date" class="block text-sm font-semibold text-slate-700">授業日</label>
                    <input id="lesson_date" name="lesson_date" type="date" required value="{{ old('lesson_date', $lessonDate ?? now()->format('Y-m-d')) }}" class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2">
                    @error('lesson_date')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full rounded-lg bg-slate-900 px-5 py-2.5 font-semibold text-white">表示</button>
                </div>
            </form>
        </div>

        @if ($lessonSession !== null && $selectedSlot !== null)
            @if ($lessonSession->status === \App\Enums\LessonStatus::Cancelled)
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">休講の授業です。出欠を登録する場合は授業状態を確認してください。</div>
            @endif

            <div class="rounded-2xl bg-white p-6 shadow-sm">
                <div class="border-b border-slate-200 pb-5">
                    <p class="text-sm font-semibold text-slate-500">{{ $lessonSession->lesson_date->format('Y年m月d日') }} / {{ $selectedSlot->day_of_week->label() }} / {{ $selectedSlot->period_no }}時限</p>
                    <h2 class="mt-1 text-xl font-bold">{{ $selectedSlot->course->course_name }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ $selectedSlot->course->subject->subject_name }} / {{ $selectedSlot->course->grade->label() }} / {{ $selectedSlot->course->classGroup->class_name }}</p>
                </div>

                @if ($students->isEmpty())
                    <p class="py-10 text-center text-sm text-slate-500">対象となる在籍生徒はいません。</p>
                @else
                    <form method="POST" action="{{ route('admin.attendance.update', $lessonSession) }}" class="mt-6">
                        @csrf
                        @method('PUT')
                        @error('records')<p class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</p>@enderror
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50"><tr><th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">生徒番号</th><th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">氏名</th><th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">出欠区分</th><th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">備考・修正理由</th></tr></thead>
                                <tbody class="divide-y divide-slate-200">
                                    @foreach ($students as $student)
                                        @php
                                            $studentIndex = $loop->index;
                                            $attendanceRecord = $attendanceRecords->get($student->id);
                                        @endphp
                                        <tr>
                                            <td class="px-5 py-4 text-sm font-semibold">{{ $student->student_no ?? '-' }}<input type="hidden" name="records[{{ $studentIndex }}][student_id]" value="{{ $student->id }}"></td>
                                            <td class="px-5 py-4 text-sm">{{ $student->student_name }}</td>
                                            <td class="px-5 py-4">
                                                <select name="records[{{ $studentIndex }}][attendance_status]" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                                    @foreach ($attendanceStatuses as $attendanceStatus)
                                                        <option value="{{ $attendanceStatus->value }}" @selected(old("records.{$studentIndex}.attendance_status", $attendanceRecord?->attendance_status->value ?? \App\Enums\AttendanceStatus::Present->value) === $attendanceStatus->value)>{{ $attendanceStatus->label() }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="px-5 py-4"><input name="records[{{ $studentIndex }}][note]" maxlength="255" value="{{ old("records.{$studentIndex}.note", $attendanceRecord?->note) }}" class="block min-w-64 rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="任意"></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-6 flex justify-end gap-3 border-t border-slate-200 pt-6">
                            <a href="{{ route('admin.attendance.index') }}" class="rounded-lg border border-slate-300 px-5 py-3 font-semibold">戻る</a>
                            <button type="submit" class="rounded-lg bg-slate-900 px-5 py-3 font-semibold text-white">{{ $students->count() }}件を保存</button>
                        </div>
                    </form>
                @endif
            </div>
        @endif
    </section>
@endsection
