@extends('layouts.app')

@section('page-class', 'page-pattern-list page-admin-course-teacher-assignments-index')

@section('title', '担当教員設定')
@section('header-title', '担当教員設定')

@section('content')
    <section class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">

            <a
                href="{{ route('admin.courses.index') }}"
                class="inline-flex shrink-0 items-center justify-center rounded-lg border lms-border-neutral-default
                    lms-bg-surface px-5 py-3 font-semibold lms-text-neutral-secondary lms-hover-bg-neutral-subtle"
            >
                授業管理を開く
            </a>
        </div>

        @if (session('warning'))
            <div
                class="rounded-lg border lms-border-warning lms-bg-warning-soft px-4 py-3 text-sm font-semibold
                    lms-text-warning-deep"
            >
                {{ session('warning') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg border lms-border-danger lms-bg-danger-soft px-4 py-3 text-sm lms-text-danger-deep">
                <p class="font-semibold">担当教員を更新できませんでした。</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="p-6 lms-panel">
            <form
                method="GET"
                action="{{ route('admin.course-teacher-assignments.index') }}"
                class="space-y-4"
            >
                <div class="grid gap-4 lg:grid-cols-4">
                    <div class="lg:col-span-2">
                        <label for="keyword" class="block text-sm font-semibold lms-text-neutral-secondary">
                            キーワード
                        </label>
                        <input
                            id="keyword"
                            name="keyword"
                            type="search"
                            value="{{ $keyword }}"
                            placeholder="授業名・科目・担当教員"
                            class="mt-2 block w-full rounded-lg border px-3 py-2 shadow-sm lms-focus-border
                                focus:outline-none focus:ring-2 lms-focus-ring lms-form-control"
                        >
                    </div>

                    <div>
                        <label for="academic_year" class="block text-sm font-semibold lms-text-neutral-secondary">
                            年度
                        </label>
                        <select
                            id="academic_year"
                            name="academic_year"
                            class="mt-2 block w-full rounded-lg border px-3 py-2 shadow-sm lms-form-control"
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
                        <label for="grade" class="block text-sm font-semibold lms-text-neutral-secondary">
                            学年
                        </label>
                        <select
                            id="grade"
                            name="grade"
                            class="mt-2 block w-full rounded-lg border px-3 py-2 shadow-sm lms-form-control"
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
                        <label for="class_group_id" class="block text-sm font-semibold lms-text-neutral-secondary">
                            クラス
                        </label>
                        <select
                            id="class_group_id"
                            name="class_group_id"
                            class="mt-2 block w-full rounded-lg border px-3 py-2 shadow-sm lms-form-control"
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
                        <label for="subject_id" class="block text-sm font-semibold lms-text-neutral-secondary">
                            科目
                        </label>
                        <select
                            id="subject_id"
                            name="subject_id"
                            class="mt-2 block w-full rounded-lg border px-3 py-2 shadow-sm lms-form-control"
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
                        <label for="assignment_status" class="block text-sm font-semibold lms-text-neutral-secondary">
                            担当設定
                        </label>
                        <select
                            id="assignment_status"
                            name="assignment_status"
                            class="mt-2 block w-full rounded-lg border px-3 py-2 shadow-sm lms-form-control"
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
                            class="inline-flex w-full items-center justify-center rounded-lg px-5 py-2.5 font-semibold
                                lms-button-primary"
                        >
                            検索
                        </button>
                    </div>

                    <div class="flex items-end">
                        <a
                            href="{{ route('admin.course-teacher-assignments.index') }}"
                            class="inline-flex w-full items-center justify-center rounded-lg border
                                lms-border-neutral-default lms-bg-surface px-5 py-2.5 font-semibold
                                lms-text-neutral-secondary lms-hover-bg-neutral-subtle"
                        >
                            クリア
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="overflow-hidden lms-panel">
            <div class="overflow-x-auto">
                <table
                    class="min-w-full divide-y lms-divide-neutral-subtle lms-table lms-table--fluid
                        lms-table--assignment"
                >
                    <thead class="lms-bg-neutral-subtle">
                        <tr>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted
                                    lms-table-col--medium"
                            >年度・対象</th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted
                                    lms-table-col--medium"
                            >授業・科目</th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted
                                    lms-table-col--wide"
                            >現在の担当</th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted
                                    lms-table-col--medium"
                            >未完了データ</th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted
                                    lms-table-col--medium"
                            >Classroom</th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted
                                    lms-table-col--action-wide"
                            >担当設定</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y lms-divide-neutral-subtle lms-bg-surface">
                        @forelse ($courses as $course)
                            @php
                                $currentTeacherIsSelectable = $course->teacher_id === null
                                    || $teachers->contains('id', $course->teacher_id);
                                $pendingLessonSessions = (int) $course->getAttribute('pending_lesson_sessions_count');
                                $draftEvaluations = (int) $course->getAttribute('draft_evaluations_count');
                            @endphp

                            <tr class="align-top lms-hover-bg-neutral-subtle">
                                <td class="whitespace-nowrap px-5 py-4 text-sm">
                                    <p class="font-semibold lms-text-neutral-strong">{{ $course->academic_year }}年度</p>
                                    <p class="mt-1 text-xs lms-text-neutral-muted">
                                        {{ $course->grade->label() }} / {{ $course->classGroup->class_code }}・{{
                                        $course->classGroup->class_name }}
                                    </p>
                                    <span
                                        @class([
                                            'mt-2 inline-flex whitespace-nowrap rounded-full',
                                            'px-2.5 py-1 text-xs font-semibold',
                                            'lms-bg-success-muted lms-text-success-strong' => $course->status ===
                                            \App\Enums\MasterStatus::Active,
                                            'lms-bg-neutral-muted lms-text-neutral-secondary' => $course->status ===
                                            \App\Enums\MasterStatus::Inactive,
                                        ])
                                    >
                                        {{ $course->status->label() }}
                                    </span>
                                </td>

                                <td class="px-5 py-4 text-sm">
                                    <p class="font-semibold lms-text-neutral-strong">{{ $course->course_name }}</p>
                                    <p class="mt-1 text-xs lms-text-neutral-muted">
                                        {{ $course->subject->subject_code }} / {{ $course->subject->subject_name }}
                                    </p>
                                </td>

                                <td class="px-5 py-4 text-sm">
                                    @if ($course->teacher !== null)
                                        <p
                                            class="font-semibold lms-text-neutral-strong"
                                        >{{ $course->teacher->user->name }}</p>
                                        <p
                                            class="lms-table-cell--nowrap mt-1 text-xs lms-text-neutral-muted"
                                        >{{ $course->teacher->user->email }}</p>

                                        @if (! $currentTeacherIsSelectable)
                                            <span
                                                class="mt-2 inline-flex whitespace-nowrap rounded-full
                                                    lms-bg-warning-muted px-2.5 py-1 text-xs font-semibold
                                                    lms-text-warning-strong"
                                            >
                                                現在は選択不可
                                            </span>
                                        @endif
                                    @else
                                        <span
                                            class="inline-flex whitespace-nowrap rounded-full lms-bg-neutral-muted
                                                px-2.5 py-1 text-xs font-semibold lms-text-neutral-subtle"
                                        >
                                            未設定
                                        </span>
                                    @endif
                                </td>

                                <td class="lms-table-cell--nowrap px-5 py-4 text-sm">
                                    <div class="space-y-1">
                                        <p class="lms-text-neutral-secondary">
                                            未完了授業：<span class="font-semibold">{{ $pendingLessonSessions }}件</span>
                                        </p>
                                        <p class="lms-text-neutral-secondary">
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
                                            class="font-semibold lms-link-primary"
                                        >
                                            Classroomを開く
                                        </a>
                                    @else
                                        <span class="lms-text-neutral-disabled">未設定</span>
                                    @endif
                                </td>

                                <td class="px-5 py-4 text-sm">
                                    <form
                                        method="POST"
                                        action="{{ route('admin.course-teacher-assignments.update', $course) }}"
                                        class="lms-assignment-form"
                                        @if ($course->teacher_id !== null && ($pendingLessonSessions > 0 ||
                                        $draftEvaluations > 0))
                                            data-confirm-empty-select="teacher_id"
                                            data-confirm-message="未完了データがある授業の担当を解除します。引継ぎ状況を確認しましたか？"
                                        @endif
                                    >
                                        @csrf
                                        @method('PATCH')

                                        <input
                                            class="lms-form-control"
                                            type="hidden"
                                            name="filter_keyword"
                                            value="{{ $keyword }}"
                                        >
                                        <input
                                            class="lms-form-control"
                                            type="hidden"
                                            name="filter_academic_year"
                                            value="{{ $selectedAcademicYear }}"
                                        >
                                        <input
                                            class="lms-form-control"
                                            type="hidden"
                                            name="filter_grade"
                                            value="{{ $selectedGrade }}"
                                        >
                                        <input
                                            class="lms-form-control"
                                            type="hidden"
                                            name="filter_class_group_id"
                                            value="{{ $selectedClassGroupId }}"
                                        >
                                        <input
                                            class="lms-form-control"
                                            type="hidden"
                                            name="filter_subject_id"
                                            value="{{ $selectedSubjectId }}"
                                        >
                                        <input
                                            class="lms-form-control"
                                            type="hidden"
                                            name="filter_assignment_status"
                                            value="{{ $selectedAssignmentStatus }}"
                                        >
                                        <input
                                            class="lms-form-control"
                                            type="hidden"
                                            name="page"
                                            value="{{ $courses->currentPage() }}"
                                        >

                                        <select
                                        class="lms-assignment-select lms-form-control block rounded-lg border
                                            lms-border-neutral-default px-3 py-2 shadow-sm"
                                            name="teacher_id"
                                            aria-label="{{ $course->course_name }}の担当教員"
                                        >
                                            <option value="" @selected($course->teacher_id === null)>
                                                未設定（担当解除）
                                            </option>

                                            @if ($course->status === \App\Enums\MasterStatus::Inactive &&
                                            $course->teacher !== null)
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
                                            @disabled($course->status === \App\Enums\MasterStatus::Inactive &&
                                            $course->teacher_id === null)
                                            class="inline-flex shrink-0 items-center justify-center rounded-lg px-4
                                                py-2 font-semibold disabled:cursor-not-allowed lms-button-primary"
                                        >
                                            保存
                                        </button>
                                    </form>

                                    @if ($course->status === \App\Enums\MasterStatus::Inactive)
                                        <p class="mt-2 text-xs lms-text-warning">
                                            無効な授業は現在の担当保持または担当解除のみ可能です。
                                        </p>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-sm lms-text-neutral-muted">
                                    条件に一致する授業はありません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($courses->hasPages())
                <div class="border-t lms-border-neutral-subtle px-6 py-4">
                    {{ $courses->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
