@extends('layouts.app')

@section('page-class', 'page-pattern-list page-teacher-courses-index')

@section('title', '担当授業一覧')
@section('header-title', '担当授業一覧')

@section('content')
    <section class="space-y-6">

        <div class="p-6 lms-panel">
            <form method="GET" action="{{ route('teacher.courses.index') }}" class="grid gap-4 md:grid-cols-4">
                <div>
                    <label for="academic_year" class="block text-sm font-semibold lms-text-neutral-secondary">年度</label>
                    <select
                        id="academic_year"
                        name="academic_year"
                        class="mt-2 block w-full rounded-lg border px-3 py-2 lms-form-control"
                    >
                        <option value="">すべて</option>
                        @foreach ($academicYears as $academicYear)
                            <option
                                value="{{ $academicYear }}"
                                @selected((string) $selectedAcademicYear === (string) $academicYear)
                            >
                                {{ $academicYear }}年度
                            </option>
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
                            <option value="{{ $grade->value }}" @selected($selectedGrade === $grade)>
                                {{ $grade->label() }}
                            </option>
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
                            >
                                {{ $classGroup->class_code }} / {{ $classGroup->class_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="subject_id" class="block text-sm font-semibold lms-text-neutral-secondary">科目</label>
                    <select
                        id="subject_id"
                        name="subject_id"
                        class="mt-2 block w-full rounded-lg border px-3 py-2 lms-form-control"
                    >
                        <option value="">すべて</option>
                        @foreach ($subjects as $subject)
                            <option
                                value="{{ $subject->id }}"
                                @selected((string) $selectedSubjectId === (string) $subject->id)
                            >
                                {{ $subject->subject_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-3 md:col-span-4 md:justify-end">
                    <a
                        href="{{ route('teacher.courses.index') }}"
                        class="rounded-lg border lms-border-neutral-default px-5 py-2.5 font-semibold
                            lms-text-neutral-secondary lms-hover-bg-neutral-subtle"
                    >
                        クリア
                    </a>
                    <button type="submit" class="rounded-lg px-5 py-2.5 font-semibold lms-button-primary">
                        検索
                    </button>
                </div>
            </form>
        </div>

        <div class="space-y-4">
            @forelse ($courses as $course)
                <article class="p-6 lms-panel">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <p class="text-sm font-semibold lms-text-neutral-muted">
                                {{ $course->academic_year }}年度 / {{ $course->grade->label() }} / {{
                                $course->classGroup->class_name }}
                            </p>
                            <h2 class="mt-1 text-xl font-bold lms-text-neutral-strong">
                                {{ $course->course_name }}
                            </h2>
                            <p class="mt-1 text-sm lms-text-neutral-subtle">
                                {{ $course->subject->subject_code }} / {{ $course->subject->subject_name }}
                            </p>

                            <div class="mt-4 flex flex-wrap gap-2">
                                @forelse ($course->timetableSlots as $slot)
                                    <span
                                        class="rounded-full lms-bg-neutral-muted px-3 py-1 text-sm font-semibold
                                            lms-text-neutral-secondary"
                                    >
                                        {{ $slot->day_of_week->shortLabel() }}曜 {{ $slot->period_no }}時限
                                    </span>
                                @empty
                                    <span class="text-sm lms-text-neutral-disabled">時間割未設定</span>
                                @endforelse
                            </div>
                        </div>

                        <div class="min-w-52 rounded-xl lms-bg-neutral-subtle p-4">
                            <p class="text-sm lms-text-neutral-muted">対象生徒</p>
                            <p class="mt-1 text-2xl font-bold lms-text-neutral-strong">
                                {{ $course->classGroup->students->where('grade', $course->grade)->count() }}名
                            </p>
                        </div>
                    </div>

                    @php
                        $firstSlot = $course->timetableSlots->first();
                        $nextLessonDate = null;

                        if ($firstSlot instanceof \App\Models\TimetableSlot) {
                            $daysUntilLesson = (
                                $firstSlot->day_of_week->value
                                - now()->dayOfWeekIso
                                + 7
                            ) % 7;
                            $nextLessonDate = now()
                                ->addDays($daysUntilLesson)
                                ->format('Y-m-d');
                        }
                    @endphp

                    <div class="mt-5 flex flex-wrap gap-3 border-t lms-border-neutral-subtle pt-5">
                        <a
                            href="{{ route('teacher.courses.students.index', $course) }}"
                            class="rounded-lg border lms-border-neutral-default px-4 py-2 text-sm font-semibold
                                lms-hover-bg-neutral-subtle"
                        >
                            生徒一覧
                        </a>

                        @if ($firstSlot instanceof \App\Models\TimetableSlot)
                            <a
                                href="{{
                                    route('teacher.attendance.edit', ['timetable_slot_id' => $firstSlot->id,
                                    'lesson_date' => $nextLessonDate])
                                }}"
                                class="rounded-lg px-4 py-2 text-sm font-semibold lms-button-primary"
                            >
                                出欠登録
                            </a>
                        @else
                            <span
                                class="cursor-not-allowed rounded-lg lms-bg-neutral-disabled px-4 py-2 text-sm
                                    font-semibold lms-text-neutral-muted"
                            >
                                時間割未設定
                            </span>
                        @endif
                        <a
                            href="{{ route('teacher.attendance.index', ['course_id' => $course->id]) }}"
                            class="rounded-lg border lms-border-neutral-default px-4 py-2 text-sm font-semibold
                                lms-hover-bg-neutral-subtle"
                        >
                            出欠履歴
                        </a>
                        @if ($course->google_classroom_url !== null)
                            <a
                                href="{{ $course->google_classroom_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="rounded-lg border px-4 py-2 text-sm font-semibold lms-button-secondary"
                            >
                                Classroom
                            </a>
                        @endif
                    </div>
                </article>
            @empty
                <div class="px-6 py-12 text-center text-sm lms-text-neutral-muted lms-panel">
                    条件に一致する担当授業はありません。
                </div>
            @endforelse
        </div>

        {{ $courses->links() }}
    </section>
@endsection
