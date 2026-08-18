@extends('layouts.app')

@section('page-class', 'page-pattern-attendance-entry page-teacher-attendance-edit')

@section('title', '出欠登録')
@section('header-title', '出欠登録')

@section('content')
    <section class="space-y-6">

        <div class="p-6 lms-panel">
            <form
                method="GET"
                action="{{ route('teacher.attendance.edit') }}"
                class="grid gap-4 md:grid-cols-[minmax(0,1fr)_220px_auto]"
            >
                <div>
                    <label
                        for="timetable_slot_id"
                        class="block text-sm font-semibold lms-text-neutral-secondary"
                    >担当授業・時間割枠</label>
                    <select
                        id="timetable_slot_id"
                        name="timetable_slot_id"
                        required
                        class="mt-2 block w-full rounded-lg border px-3 py-2 lms-form-control"
                    >
                        <option value="">選択してください</option>
                        @foreach ($timetableSlots as $slot)
                            <option
                                value="{{ $slot->id }}"
                                @selected((string) old('timetable_slot_id', $selectedSlot?->id) === (string) $slot->id)
                            >
                                {{ $slot->day_of_week->shortLabel() }}曜 {{ $slot->period_no }}時限 / {{
                                $slot->course->course_name }} / {{ $slot->course->classGroup->class_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('timetable_slot_id')<p class="mt-2 text-sm lms-text-danger">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="lesson_date" class="block text-sm font-semibold lms-text-neutral-secondary">授業日</label>
                    <input
                        class="lms-form-control mt-2 block w-full rounded-lg border lms-border-neutral-default px-3
                            py-2"
                        id="lesson_date"
                        name="lesson_date"
                        type="date"
                        required
                        value="{{ old('lesson_date', $lessonDate ?? now()->format('Y-m-d')) }}"
                    >
                    @error('lesson_date')<p class="mt-2 text-sm lms-text-danger">{{ $message }}</p>@enderror
                </div>
                <div class="flex items-end">
                    <button
                        type="submit"
                        class="w-full rounded-lg px-5 py-2.5 font-semibold lms-button-primary"
                    >対象生徒を表示</button>
                </div>
            </form>
        </div>

        @if ($lessonSession !== null && $selectedSlot !== null)
            @if ($lessonSession->status === \App\Enums\LessonStatus::Cancelled)
                <div
                    class="rounded-lg border lms-border-warning lms-bg-warning-soft px-4 py-3 text-sm
                        lms-text-warning-strong"
                >
                    休講の授業です。出欠を登録する場合は授業状態を確認してください。
                </div>
            @endif

            <div class="p-6 lms-panel">
                <div class="border-b lms-border-neutral-subtle pb-5">
                    <p
                        class="text-sm font-semibold lms-text-neutral-muted"
                    >{{ $lessonSession->lesson_date->format('Y年m月d日') }} / {{ $selectedSlot->day_of_week->label() }} /
                    {{ $selectedSlot->period_no }}時限</p>
                    <h2
                        class="mt-1 text-xl font-bold lms-text-neutral-strong"
                    >{{ $selectedSlot->course->course_name }}</h2>
                    <p
                        class="mt-1 text-sm lms-text-neutral-subtle"
                    >{{ $selectedSlot->course->subject->subject_name }} / {{ $selectedSlot->course->grade->label() }} /
                    {{ $selectedSlot->course->classGroup->class_name }}</p>
                </div>

                @if ($students->isEmpty())
                    <p class="py-10 text-center text-sm lms-text-neutral-muted">対象となる在籍生徒はいません。</p>
                @else
                    <form method="POST" action="{{ route('teacher.attendance.update', $lessonSession) }}" class="">
                        @csrf
                        @method('PUT')

                        @error('records')
                            <p
                                class="mb-4 rounded-lg lms-bg-danger-soft px-4 py-3 text-sm lms-text-danger-strong"
                            >{{ $message }}</p>
                        @enderror

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y lms-divide-neutral-subtle lms-table lms-table--balanced">
                                <thead class="lms-bg-neutral-subtle">
                                    <tr>
                                        <th
                                            class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted
                                                lms-table-col--medium"
                                        >生徒番号</th>
                                        <th
                                            class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted"
                                        >氏名</th>
                                        <th
                                            class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted
                                                lms-table-col--medium"
                                        >出欠区分</th>
                                        <th
                                            class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted"
                                        >備考</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y lms-divide-neutral-subtle">
                                    @foreach ($students as $student)
                                        @php
                                            $studentIndex = $loop->index;
                                            $attendanceRecord = $attendanceRecords->get($student->id);
                                        @endphp
                                        <tr>
                                            <td
                                                class="whitespace-nowrap px-5 py-4 text-sm font-semibold
                                                    lms-text-neutral-strong"
                                            >
                                                {{ $student->student_no ?? '-' }}
                                                <input
                                                    class="lms-form-control"
                                                    type="hidden"
                                                    name="records[{{ $studentIndex }}][student_id]"
                                                    value="{{ $student->id }}"
                                                >
                                            </td>
                                            <td
                                                class="px-5 py-4 text-sm lms-text-neutral-secondary"
                                            >{{ $student->student_name }}</td>
                                            <td class="px-5 py-4">
                                                <select
                                                    name="records[{{ $studentIndex }}][attendance_status]"
                                                    aria-label="{{ $student->student_name }}さんの出欠区分"
                                                    class="rounded-lg border px-3 py-2 text-sm lms-form-control"
                                                    required
                                                >
                                                    @foreach ($attendanceStatuses as $attendanceStatus)
                                                        <option
                                                            value="{{ $attendanceStatus->value }}"
                                                            @selected(old("records.{$studentIndex}.attendance_status",
                                                            $attendanceRecord?->attendance_status->value ??
                                                            \App\Enums\AttendanceStatus::Present->value) ===
                                                            $attendanceStatus->value)
                                                        >
                                                            {{ $attendanceStatus->label() }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="px-5 py-4">
                                                <input
                                                    class="block min-w-64 rounded-lg border px-3 py-2 text-sm
                                                        lms-form-control lms-border-neutral-default"
                                                    name="records[{{ $studentIndex }}][note]"
                                                    aria-label="{{ $student->student_name }}さんの備考"
                                                    maxlength="255"
                                                    value="{{
                                                        old("records.{$studentIndex}.note", $attendanceRecord?->note)
                                                    }}"
                                                    placeholder="任意"
                                                >
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-6 flex justify-center gap-3 border-t lms-border-neutral-subtle pt-6">
                            <a
                                href="{{ route('teacher.attendance.index') }}"
                                class="rounded-lg border lms-border-neutral-default px-5 py-3 font-semibold
                                    lms-text-neutral-secondary lms-hover-bg-neutral-subtle"
                            >戻る</a>
                            <button
                                type="submit"
                                class="rounded-lg px-5 py-3 font-semibold lms-button-primary"
                            >{{ $students->count() }}件を保存</button>
                        </div>
                    </form>
                @endif
            </div>
        @endif
    </section>
@endsection
