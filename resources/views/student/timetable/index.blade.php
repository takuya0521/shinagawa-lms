@extends('layouts.app')

@section('page-class', 'page-pattern-timetable page-student-timetable-index')

@section('title', '週間時間割')
@section('header-title', '週間時間割')

@section('content')
    @php
        $days = [
            \App\Enums\DayOfWeek::Monday,
            \App\Enums\DayOfWeek::Tuesday,
            \App\Enums\DayOfWeek::Wednesday,
            \App\Enums\DayOfWeek::Thursday,
            \App\Enums\DayOfWeek::Friday,
        ];
        $periods = range(1, 3);
        $slotMap = $timetableSlots->keyBy(
            static fn (\App\Models\TimetableSlot $slot): string => $slot->day_of_week->value.'-'.$slot->period_no,
        );
    @endphp

    <section class="space-y-6">
        <div class="p-6 lms-panel">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>

                    <p class="text-sm lms-text-neutral-subtle">
                        {{ $student->grade->label() }} /
                        {{ $student->classGroup->class_name }} の時間割を表示しています。
                    </p>
                </div>

                <form method="GET" action="{{ route('student.timetable.index') }}" class="flex items-end gap-3">
                    <div>
                        <label for="academic_year" class="block text-sm font-semibold lms-text-neutral-secondary">
                            年度
                        </label>

                        <select
                            id="academic_year"
                            name="academic_year"
                            class="mt-2 rounded-lg border px-3 py-2 lms-form-control"
                        >
                            @forelse ($academicYears as $academicYear)
                                <option
                                    value="{{ $academicYear }}"
                                    @selected((string) $selectedAcademicYear === (string) $academicYear)
                                >
                                    {{ $academicYear }}年度
                                </option>
                            @empty
                                <option value="{{ $selectedAcademicYear }}">
                                    {{ $selectedAcademicYear }}年度
                                </option>
                            @endforelse
                        </select>
                    </div>

                    <button
                        type="submit"
                        class="rounded-lg px-4 py-2 font-semibold lms-button-primary"
                    >
                        表示
                    </button>
                </form>
            </div>
        </div>

        <div class="overflow-hidden lms-panel">
            <div class="overflow-x-auto">
                <table class="w-full table-fixed divide-y lms-divide-neutral-subtle lms-table lms-table--no-row-hover">
                    <thead class="lms-bg-neutral-subtle">
                        <tr>
                            <th class="w-20 px-3 py-4 text-center text-xs font-semibold lms-text-neutral-muted">
                                時限
                            </th>

                            @foreach ($days as $day)
                                <th class="px-3 py-4 text-center text-sm font-bold lms-text-neutral-secondary">
                                    {{ $day->shortLabel() }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody class="divide-y lms-divide-neutral-subtle">
                        @foreach ($periods as $period)
                            <tr>
                                <th
                                    class="lms-bg-neutral-subtle px-3 py-5 text-center text-sm font-bold
                                        lms-text-neutral-secondary"
                                >
                                    {{ $period }}時限
                                </th>

                                @foreach ($days as $day)
                                    @php
                                        $slot = $slotMap->get($day->value.'-'.$period);
                                    @endphp

                                    <td class="align-top px-3 py-4">
                                        @if ($slot instanceof \App\Models\TimetableSlot)
                                            <div
                                                class="student-timetable-card min-h-32 rounded-xl border
                                                    lms-border-neutral-subtle p-4"
                                            >
                                                <p class="font-bold lms-text-neutral-strong">
                                                    {{ $slot->course->course_name }}
                                                </p>

                                                <p class="mt-1 text-sm lms-text-neutral-subtle">
                                                    {{ $slot->course->subject->subject_name }}
                                                </p>

                                                <p class="mt-3 text-xs lms-text-neutral-muted">
                                                    担当：{{ $slot->course->teacher?->user?->name ?? '未設定' }}
                                                </p>

                                                @if ($slot->start_time !== null && $slot->end_time !== null)
                                                    <p class="mt-1 text-xs lms-text-neutral-muted">
                                                        {{ substr((string) $slot->start_time, 0, 5) }}～
                                                        {{ substr((string) $slot->end_time, 0, 5) }}
                                                    </p>
                                                @endif

                                                @if ($slot->course->google_classroom_url !== null)
                                                    <a
                                                        href="{{ $slot->course->google_classroom_url }}"
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        class="mt-3 inline-flex text-xs font-semibold lms-link-primary"
                                                    >
                                                        Classroom
                                                    </a>
                                                @endif
                                            </div>
                                        @else
                                            <div
                                                class="min-h-32 rounded-xl border border-dashed
                                                    lms-border-neutral-subtle lms-bg-neutral-subtle"
                                            ></div>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
