@extends('layouts.app')

@section('page-class', 'page-pattern-list page-admin-courses-index')

@section('title', '授業管理')
@section('header-title', '授業管理')

@section('content')
    <section class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">

            <div class="flex flex-wrap gap-3">
                <a
                    href="{{ route('admin.course-teacher-assignments.index') }}"
                    class="inline-flex shrink-0 items-center justify-center rounded-lg border
                        lms-border-neutral-default lms-bg-surface px-5 py-3 font-semibold lms-text-neutral-secondary
                        lms-hover-bg-neutral-subtle"
                >
                    担当教員設定
                </a>

                <a
                    href="{{ route('admin.courses.create') }}"
                    class="inline-flex shrink-0 items-center justify-center rounded-lg px-5 py-3 font-semibold
                        lms-button-primary"
                >
                    授業を登録
                </a>
            </div>
        </div>

        <div class="p-6 lms-panel">
            <form
                method="GET"
                action="{{ route('admin.courses.index') }}"
                class="space-y-4"
            >
                <div class="grid gap-4 lg:grid-cols-5">
                    <div class="lg:col-span-2">
                        <label
                            for="keyword"
                            class="block text-sm font-semibold lms-text-neutral-secondary"
                        >
                            キーワード
                        </label>

                        <input
                            id="keyword"
                            name="keyword"
                            type="search"
                            value="{{ $keyword }}"
                            placeholder="授業名・科目・担当教員・Classroom ID"
                            class="mt-2 block w-full rounded-lg border px-3 py-2 lms-text-neutral-strong shadow-sm
                                lms-focus-border focus:outline-none focus:ring-2 lms-focus-ring lms-form-control"
                        >
                    </div>

                    <div>
                        <label
                            for="academic_year"
                            class="block text-sm font-semibold lms-text-neutral-secondary"
                        >
                            年度
                        </label>

                        <select
                            id="academic_year"
                            name="academic_year"
                            class="mt-2 block w-full rounded-lg border px-3 py-2 lms-text-neutral-strong shadow-sm
                                lms-focus-border focus:outline-none focus:ring-2 lms-focus-ring lms-form-control"
                        >
                            <option value="">
                                すべて
                            </option>

                            @foreach ($academicYears as $academicYear)
                                <option
                                    value="{{ $academicYear }}"
                                    @selected(
                                        (string) $selectedAcademicYear
                                            === (string) $academicYear
                                    )
                                >
                                    {{ $academicYear }}年度
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label
                            for="grade"
                            class="block text-sm font-semibold lms-text-neutral-secondary"
                        >
                            学年
                        </label>

                        <select
                            id="grade"
                            name="grade"
                            class="mt-2 block w-full rounded-lg border px-3 py-2 lms-text-neutral-strong shadow-sm
                                lms-focus-border focus:outline-none focus:ring-2 lms-focus-ring lms-form-control"
                        >
                            <option value="">
                                すべて
                            </option>

                            @foreach ($grades as $grade)
                                <option
                                    value="{{ $grade->value }}"
                                    @selected(
                                        (string) $selectedGrade
                                            === $grade->value
                                    )
                                >
                                    {{ $grade->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label
                            for="status"
                            class="block text-sm font-semibold lms-text-neutral-secondary"
                        >
                            状態
                        </label>

                        <select
                            id="status"
                            name="status"
                            class="mt-2 block w-full rounded-lg border px-3 py-2 lms-text-neutral-strong shadow-sm
                                lms-focus-border focus:outline-none focus:ring-2 lms-focus-ring lms-form-control"
                        >
                            <option value="">
                                すべて
                            </option>

                            @foreach ($statuses as $status)
                                <option
                                    value="{{ $status->value }}"
                                    @selected(
                                        $selectedStatus
                                            === $status->value
                                    )
                                >
                                    {{ $status->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_auto_auto]">
                    <div>
                        <label
                            for="class_group_id"
                            class="block text-sm font-semibold lms-text-neutral-secondary"
                        >
                            クラス
                        </label>

                        <select
                            id="class_group_id"
                            name="class_group_id"
                            class="mt-2 block w-full rounded-lg border px-3 py-2 lms-text-neutral-strong shadow-sm
                                lms-focus-border focus:outline-none focus:ring-2 lms-focus-ring lms-form-control"
                        >
                            <option value="">
                                すべて
                            </option>

                            @foreach ($classGroups as $classGroup)
                                <option
                                    value="{{ $classGroup->id }}"
                                    @selected(
                                        (string) $selectedClassGroupId
                                            === (string) $classGroup->id
                                    )
                                >
                                    {{ $classGroup->class_code }}
                                    /
                                    {{ $classGroup->class_name }}
                                </option>
                            @endforeach
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
                            href="{{ route('admin.courses.index') }}"
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
                <table class="min-w-full divide-y lms-divide-neutral-subtle lms-table lms-table--balanced">
                    <thead class="lms-bg-neutral-subtle">
                        <tr>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider
                                    lms-text-neutral-muted lms-table-col--compact"
                            >
                                年度
                            </th>

                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider
                                    lms-text-neutral-muted"
                            >
                                学年・クラス
                            </th>

                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider
                                    lms-text-neutral-muted"
                            >
                                科目・授業名
                            </th>

                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider
                                    lms-text-neutral-muted"
                            >
                                担当教員
                            </th>

                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider
                                    lms-text-neutral-muted"
                            >
                                Classroom
                            </th>

                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider
                                    lms-text-neutral-muted lms-table-col--status"
                            >
                                状態
                            </th>

                            <th
                                class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider
                                    lms-text-neutral-muted lms-table-col--action"
                            >
                                操作
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y lms-divide-neutral-subtle lms-bg-surface">
                        @forelse ($courses as $course)
                            <tr class="lms-hover-bg-neutral-subtle">
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold lms-text-neutral-strong">
                                    {{ $course->academic_year }}年度
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm lms-text-neutral-secondary">
                                    <div class="font-semibold lms-text-neutral-strong">
                                        {{ $course->grade->label() }}
                                    </div>

                                    <div class="mt-1 text-xs lms-text-neutral-muted">
                                        {{ $course->classGroup->class_code }}
                                        /
                                        {{ $course->classGroup->class_name }}
                                    </div>
                                </td>

                                <td class="px-5 py-4 text-sm lms-text-neutral-secondary">
                                    <div class="font-semibold lms-text-neutral-strong">
                                        {{ $course->course_name }}
                                    </div>

                                    <div class="mt-1 text-xs lms-text-neutral-muted">
                                        {{ $course->subject->subject_code }}
                                        /
                                        {{ $course->subject->subject_name }}
                                    </div>
                                </td>

                                <td class="px-5 py-4 text-sm lms-text-neutral-secondary">
                                    @if ($course->teacher !== null)
                                        <div class="font-medium lms-text-neutral-strong">
                                            {{ $course->teacher->user->name }}
                                        </div>

                                        <div class="mt-1 text-xs lms-text-neutral-muted">
                                            {{ $course->teacher->user->email }}
                                        </div>
                                    @else
                                        <span class="lms-text-neutral-disabled">
                                            未設定
                                        </span>
                                    @endif
                                </td>

                                <td class="px-5 py-4 text-sm lms-text-neutral-secondary">
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
                                        <span class="lms-text-neutral-disabled">
                                            未設定
                                        </span>
                                    @endif

                                    @if ($course->google_classroom_id !== null)
                                        <div class="mt-1 text-xs lms-text-neutral-muted">
                                            {{ $course->google_classroom_id }}
                                        </div>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm">
                                    <span
                                        @class([
                                            'inline-flex whitespace-nowrap rounded-full',
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

                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm">
                                    <a
                                        href="{{ route(
                                            'admin.courses.edit',
                                            $course,
                                        ) }}"
                                        class="font-semibold lms-link-primary"
                                    >
                                        編集
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="7"
                                    class="px-6 py-12 text-center text-sm lms-text-neutral-muted"
                                >
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
