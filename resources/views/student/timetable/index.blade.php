@extends('layouts.app')

@section('page-style', 'resources/css/pages/student/timetable/index.css')
@section('page-class', 'page-pattern-timetable page-student-timetable-index')

@section('title', '週間時間割')
@section('header-title', '週間時間割')

@section('content')
    @php
        $days = \App\Enums\DayOfWeek::cases();
        $periods = range(1, 6);
        $slotMap = $timetableSlots->keyBy(
            static fn (\App\Models\TimetableSlot $slot): string => $slot->day_of_week->value.'-'.$slot->period_no,
        );
    @endphp

    <section class="space-y-6">
        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">
                        週間時間割
                    </h1>

                    <p class="mt-2 text-sm text-slate-600">
                        {{ $student->grade->label() }} / {{ $student->classGroup->class_name }} の時間割を表示しています。
                    </p>
                </div>

                <form method="GET" action="{{ route('student.timetable.index') }}" class="flex items-end gap-3">
                    <div>
                        <label for="academic_year" class="block text-sm font-semibold text-slate-700">
                            年度
                        </label>

                        <select
                            id="academic_year"
                            name="academic_year"
                            class="mt-2 rounded-lg border border-slate-300 px-3 py-2"
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
                        class="rounded-lg bg-slate-900 px-4 py-2 font-semibold text-white hover:bg-slate-700"
                    >
                        表示
                    </button>
                </form>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-[1100px] w-full table-fixed divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="w-20 px-3 py-4 text-center text-xs font-semibold text-slate-500">
                                時限
                            </th>

                            @foreach ($days as $day)
                                <th class="px-3 py-4 text-center text-sm font-bold text-slate-700">
                                    {{ $day->shortLabel() }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200">
                        @foreach ($periods as $period)
                            <tr>
                                <th class="bg-slate-50 px-3 py-5 text-center text-sm font-bold text-slate-700">
                                    {{ $period }}時限
                                </th>

                                @foreach ($days as $day)
                                    @php
                                        $slot = $slotMap->get($day->value.'-'.$period);
                                    @endphp

                                    <td class="align-top px-3 py-4">
                                        @if ($slot instanceof \App\Models\TimetableSlot)
                                            <div class="min-h-32 rounded-xl border border-slate-200 p-4">
                                                <p class="font-bold text-slate-900">
                                                    {{ $slot->course->course_name }}
                                                </p>

                                                <p class="mt-1 text-sm text-slate-600">
                                                    {{ $slot->course->subject->subject_name }}
                                                </p>

                                                <p class="mt-3 text-xs text-slate-500">
                                                    担当：{{ $slot->course->teacher?->user?->name ?? '未設定' }}
                                                </p>

                                                @if ($slot->start_time !== null && $slot->end_time !== null)
                                                    <p class="mt-1 text-xs text-slate-500">
                                                        {{ substr((string) $slot->start_time, 0, 5) }}～{{ substr((string) $slot->end_time, 0, 5) }}
                                                    </p>
                                                @endif

                                                @if ($slot->course->google_classroom_url !== null)
                                                    <a
                                                        href="{{ $slot->course->google_classroom_url }}"
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        class="mt-3 inline-flex text-xs font-semibold text-blue-700 hover:text-blue-900"
                                                    >
                                                        Classroom
                                                    </a>
                                                @endif
                                            </div>
                                        @else
                                            <div class="min-h-32 rounded-xl border border-dashed border-slate-200 bg-slate-50"></div>
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
