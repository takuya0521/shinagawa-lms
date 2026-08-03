@extends('layouts.app')

@section('page-style', 'resources/css/pages/teacher/courses/index.css')
@section('page-class', 'page-pattern-list page-teacher-courses-index')

@section('title', '担当授業一覧')
@section('header-title', '担当授業一覧')

@section('content')
    <section class="space-y-6">
        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-bold text-slate-900">
                担当授業一覧
            </h1>

            <p class="mt-2 text-sm text-slate-600">
                自分が担当する有効な授業のみ表示します。
            </p>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('teacher.courses.index') }}" class="grid gap-4 md:grid-cols-4">
                <div>
                    <label for="academic_year" class="block text-sm font-semibold text-slate-700">年度</label>
                    <select id="academic_year" name="academic_year" class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="">すべて</option>
                        @foreach ($academicYears as $academicYear)
                            <option value="{{ $academicYear }}" @selected((string) $selectedAcademicYear === (string) $academicYear)>
                                {{ $academicYear }}年度
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="grade" class="block text-sm font-semibold text-slate-700">学年</label>
                    <select id="grade" name="grade" class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="">すべて</option>
                        @foreach ($grades as $grade)
                            <option value="{{ $grade->value }}" @selected($selectedGrade === $grade)>
                                {{ $grade->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="class_group_id" class="block text-sm font-semibold text-slate-700">クラス</label>
                    <select id="class_group_id" name="class_group_id" class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="">すべて</option>
                        @foreach ($classGroups as $classGroup)
                            <option value="{{ $classGroup->id }}" @selected((string) $selectedClassGroupId === (string) $classGroup->id)>
                                {{ $classGroup->class_code }} / {{ $classGroup->class_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="subject_id" class="block text-sm font-semibold text-slate-700">科目</label>
                    <select id="subject_id" name="subject_id" class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="">すべて</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}" @selected((string) $selectedSubjectId === (string) $subject->id)>
                                {{ $subject->subject_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-3 md:col-span-4 md:justify-end">
                    <a href="{{ route('teacher.courses.index') }}" class="rounded-lg border border-slate-300 px-5 py-2.5 font-semibold text-slate-700 hover:bg-slate-50">
                        クリア
                    </a>
                    <button type="submit" class="rounded-lg bg-slate-900 px-5 py-2.5 font-semibold text-white hover:bg-slate-700">
                        検索
                    </button>
                </div>
            </form>
        </div>

        <div class="space-y-4">
            @forelse ($courses as $course)
                <article class="rounded-2xl bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-slate-500">
                                {{ $course->academic_year }}年度 / {{ $course->grade->label() }} / {{ $course->classGroup->class_name }}
                            </p>
                            <h2 class="mt-1 text-xl font-bold text-slate-900">
                                {{ $course->course_name }}
                            </h2>
                            <p class="mt-1 text-sm text-slate-600">
                                {{ $course->subject->subject_code }} / {{ $course->subject->subject_name }}
                            </p>

                            <div class="mt-4 flex flex-wrap gap-2">
                                @forelse ($course->timetableSlots as $slot)
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-700">
                                        {{ $slot->day_of_week->shortLabel() }}曜 {{ $slot->period_no }}時限
                                    </span>
                                @empty
                                    <span class="text-sm text-slate-400">時間割未設定</span>
                                @endforelse
                            </div>
                        </div>

                        <div class="min-w-52 rounded-xl bg-slate-50 p-4">
                            <p class="text-sm text-slate-500">対象生徒</p>
                            <p class="mt-1 text-2xl font-bold text-slate-900">
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

                    <div class="mt-5 flex flex-wrap gap-3 border-t border-slate-200 pt-5">
                        <a href="{{ route('teacher.courses.students.index', $course) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold hover:bg-slate-50">
                            生徒一覧
                        </a>

                        @if ($firstSlot instanceof \App\Models\TimetableSlot)
                            <a href="{{ route('teacher.attendance.edit', ['timetable_slot_id' => $firstSlot->id, 'lesson_date' => $nextLessonDate]) }}" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                                出欠登録
                            </a>
                        @else
                            <span class="cursor-not-allowed rounded-lg bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-500">
                                時間割未設定
                            </span>
                        @endif
                        <a href="{{ route('teacher.attendance.index', ['course_id' => $course->id]) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold hover:bg-slate-50">
                            出欠履歴
                        </a>
                        @if ($course->google_classroom_url !== null)
                            <a href="{{ $course->google_classroom_url }}" target="_blank" rel="noopener noreferrer" class="rounded-lg border border-blue-300 px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-50">
                                Classroom
                            </a>
                        @endif
                    </div>
                </article>
            @empty
                <div class="rounded-2xl bg-white px-6 py-12 text-center text-sm text-slate-500 shadow-sm">
                    条件に一致する担当授業はありません。
                </div>
            @endforelse
        </div>

        {{ $courses->links() }}
    </section>
@endsection
