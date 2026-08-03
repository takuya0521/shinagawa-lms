@extends('layouts.app')

@section('page-style', 'resources/css/pages/admin/course-teacher-assignments/index.css')
@section('page-class', 'page-pattern-list page-admin-course-teacher-assignments-index')

@section('title', '担当教員設定')
@section('header-title', '担当教員設定')

@section('content')
    <section class="space-y-6">
        <div class="flex flex-col gap-4 rounded-2xl bg-white p-6 shadow-sm sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-slate-500">A-022</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-900">担当教員設定</h1>
                <p class="mt-2 text-sm text-slate-600">
                    登録済みの授業へ担当講師を1名設定します。未設定を選ぶと担当を解除できます。
                </p>
            </div>

            <a
                href="{{ route('admin.courses.index') }}"
                class="inline-flex shrink-0 items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-3 font-semibold text-slate-700 hover:bg-slate-50"
            >
                授業管理を開く
            </a>
        </div>


        @if (session('warning'))
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900">
                {{ session('warning') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <p class="font-semibold">担当教員を更新できませんでした。</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <form
                method="GET"
                action="{{ route('admin.course-teacher-assignments.index') }}"
                class="space-y-4"
            >
                <div class="grid gap-4 lg:grid-cols-4">
                    <div class="lg:col-span-2">
                        <label for="keyword" class="block text-sm font-semibold text-slate-700">
                            キーワード
                        </label>
                        <input
                            id="keyword"
                            name="keyword"
                            type="search"
                            value="{{ $keyword }}"
                            placeholder="授業名・科目・担当教員"
                            class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                        >
                    </div>

                    <div>
                        <label for="academic_year" class="block text-sm font-semibold text-slate-700">
                            年度
                        </label>
                        <select
                            id="academic_year"
                            name="academic_year"
                            class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2 shadow-sm"
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
                        <label for="grade" class="block text-sm font-semibold text-slate-700">
                            学年
                        </label>
                        <select
                            id="grade"
                            name="grade"
                            class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2 shadow-sm"
                        >
                            <option value="">すべて</option>
                            @foreach ($grades as $grade)
                                <option
                                    value="{{ $grade->value }}"
                                    @selected($selectedGrade === $grade->value)
                                >
                                    {{ $grade->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid gap-4 lg:grid-cols-5">
                    <div>
                        <label for="class_group_id" class="block text-sm font-semibold text-slate-700">
                            クラス
                        </label>
                        <select
                            id="class_group_id"
                            name="class_group_id"
                            class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2 shadow-sm"
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
                        <label for="subject_id" class="block text-sm font-semibold text-slate-700">
                            科目
                        </label>
                        <select
                            id="subject_id"
                            name="subject_id"
                            class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2 shadow-sm"
                        >
                            <option value="">すべて</option>
                            @foreach ($subjects as $subject)
                                <option
                                    value="{{ $subject->id }}"
                                    @selected((string) $selectedSubjectId === (string) $subject->id)
                                >
                                    {{ $subject->subject_code }} / {{ $subject->subject_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="assignment_status" class="block text-sm font-semibold text-slate-700">
                            担当設定
                        </label>
                        <select
                            id="assignment_status"
                            name="assignment_status"
                            class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2 shadow-sm"
                        >
                            <option value="">すべて</option>
                            <option value="assigned" @selected($selectedAssignmentStatus === 'assigned')>
                                設定済み
                            </option>
                            <option value="unassigned" @selected($selectedAssignmentStatus === 'unassigned')>
                                未設定
                            </option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center rounded-lg bg-slate-900 px-5 py-2.5 font-semibold text-white hover:bg-slate-700"
                        >
                            検索
                        </button>
                    </div>

                    <div class="flex items-end">
                        <a
                            href="{{ route('admin.course-teacher-assignments.index') }}"
                            class="inline-flex w-full items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            クリア
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">年度・対象</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">授業・科目</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">現在の担当</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">未完了データ</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">Classroom</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">担当設定</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($courses as $course)
                            @php
                                $currentTeacherIsSelectable = $course->teacher_id === null
                                    || $teachers->contains('id', $course->teacher_id);
                                $pendingLessonSessions = (int) $course->getAttribute('pending_lesson_sessions_count');
                                $draftEvaluations = (int) $course->getAttribute('draft_evaluations_count');
                            @endphp

                            <tr class="align-top hover:bg-slate-50">
                                <td class="whitespace-nowrap px-5 py-4 text-sm">
                                    <p class="font-semibold text-slate-900">{{ $course->academic_year }}年度</p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $course->grade->label() }} / {{ $course->classGroup->class_code }}・{{ $course->classGroup->class_name }}
                                    </p>
                                    <span
                                        @class([
                                            'mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                            'bg-emerald-100 text-emerald-800' => $course->status === \App\Enums\MasterStatus::Active,
                                            'bg-slate-100 text-slate-700' => $course->status === \App\Enums\MasterStatus::Inactive,
                                        ])
                                    >
                                        {{ $course->status->label() }}
                                    </span>
                                </td>

                                <td class="px-5 py-4 text-sm">
                                    <p class="font-semibold text-slate-900">{{ $course->course_name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $course->subject->subject_code }} / {{ $course->subject->subject_name }}
                                    </p>
                                </td>

                                <td class="px-5 py-4 text-sm">
                                    @if ($course->teacher !== null)
                                        <p class="font-semibold text-slate-900">{{ $course->teacher->user->name }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $course->teacher->user->email }}</p>

                                        @if (! $currentTeacherIsSelectable)
                                            <span class="mt-2 inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">
                                                現在は選択不可
                                            </span>
                                        @endif
                                    @else
                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                            未設定
                                        </span>
                                    @endif
                                </td>

                                <td class="px-5 py-4 text-sm">
                                    <div class="space-y-1">
                                        <p class="text-slate-700">
                                            未完了授業：<span class="font-semibold">{{ $pendingLessonSessions }}件</span>
                                        </p>
                                        <p class="text-slate-700">
                                            下書き評価：<span class="font-semibold">{{ $draftEvaluations }}件</span>
                                        </p>
                                    </div>
                                </td>

                                <td class="px-5 py-4 text-sm">
                                    @if ($course->google_classroom_url !== null)
                                        <a
                                            href="{{ $course->google_classroom_url }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="font-semibold text-blue-700 hover:text-blue-900"
                                        >
                                            Classroomを開く
                                        </a>
                                    @else
                                        <span class="text-slate-400">未設定</span>
                                    @endif
                                </td>

                                <td class="min-w-80 px-5 py-4 text-sm">
                                    <form
                                        method="POST"
                                        action="{{ route('admin.course-teacher-assignments.update', $course) }}"
                                        class="flex min-w-72 items-start gap-2"
                                        @if ($course->teacher_id !== null && ($pendingLessonSessions > 0 || $draftEvaluations > 0))
                                            onsubmit="const select = this.querySelector('select[name=teacher_id]'); if (select.value === '') { return confirm('未完了データがある授業の担当を解除します。引継ぎ状況を確認しましたか？'); }"
                                        @endif
                                    >
                                        @csrf
                                        @method('PATCH')

                                        <input type="hidden" name="filter_keyword" value="{{ $keyword }}">
                                        <input type="hidden" name="filter_academic_year" value="{{ $selectedAcademicYear }}">
                                        <input type="hidden" name="filter_grade" value="{{ $selectedGrade }}">
                                        <input type="hidden" name="filter_class_group_id" value="{{ $selectedClassGroupId }}">
                                        <input type="hidden" name="filter_subject_id" value="{{ $selectedSubjectId }}">
                                        <input type="hidden" name="filter_assignment_status" value="{{ $selectedAssignmentStatus }}">
                                        <input type="hidden" name="page" value="{{ $courses->currentPage() }}">

                                        <select
                                            name="teacher_id"
                                            aria-label="{{ $course->course_name }}の担当教員"
                                            class="block min-w-52 flex-1 rounded-lg border border-slate-300 px-3 py-2 shadow-sm"
                                        >
                                            <option value="" @selected($course->teacher_id === null)>
                                                未設定（担当解除）
                                            </option>

                                            @if ($course->status === \App\Enums\MasterStatus::Inactive && $course->teacher !== null)
                                                <option value="{{ $course->teacher_id }}" selected>
                                                    {{ $course->teacher->user->name }}（現在設定）
                                                </option>
                                            @elseif ($course->teacher !== null && ! $currentTeacherIsSelectable)
                                                <option value="{{ $course->teacher_id }}" selected>
                                                    {{ $course->teacher->user->name }}（現在設定・利用不可）
                                                </option>
                                            @endif

                                            @if ($course->status === \App\Enums\MasterStatus::Active)
                                                @foreach ($teachers as $teacher)
                                                    <option
                                                        value="{{ $teacher->id }}"
                                                        @selected($course->teacher_id === $teacher->id)
                                                    >
                                                        {{ $teacher->user->name }} / {{ $teacher->user->email }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>

                                        <button
                                            type="submit"
                                            @disabled($course->status === \App\Enums\MasterStatus::Inactive && $course->teacher_id === null)
                                            class="inline-flex shrink-0 items-center justify-center rounded-lg bg-slate-900 px-4 py-2 font-semibold text-white hover:bg-slate-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                                        >
                                            保存
                                        </button>
                                    </form>

                                    @if ($course->status === \App\Enums\MasterStatus::Inactive)
                                        <p class="mt-2 text-xs text-amber-700">
                                            無効な授業は現在の担当保持または担当解除のみ可能です。
                                        </p>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-500">
                                    条件に一致する授業はありません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($courses->hasPages())
                <div class="border-t border-slate-200 px-6 py-4">
                    {{ $courses->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
