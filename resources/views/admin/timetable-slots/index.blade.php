@extends('layouts.app')

@section('page-class', 'page-pattern-timetable page-admin-timetable-slots-index')

@section('title', '時間割管理')
@section('header-title', '時間割管理')

@section('content')
    <section class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">

            <a
                href="{{ route('admin.timetable-slots.create') }}"
                class="inline-flex shrink-0 items-center justify-center rounded-lg px-5 py-3 font-semibold
                    lms-button-primary"
            >
                時間割を登録
            </a>
        </div>

        <div class="p-6 lms-panel">
            <form
                method="GET"
                action="{{ route('admin.timetable-slots.index') }}"
                class="space-y-4"
            >
                <div class="grid gap-4 lg:grid-cols-6">
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
                            placeholder="授業名・科目・担当教員"
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
                            for="day_of_week"
                            class="block text-sm font-semibold lms-text-neutral-secondary"
                        >
                            曜日
                        </label>

                        <select
                            id="day_of_week"
                            name="day_of_week"
                            class="mt-2 block w-full rounded-lg border px-3 py-2 lms-text-neutral-strong shadow-sm
                                lms-focus-border focus:outline-none focus:ring-2 lms-focus-ring lms-form-control"
                        >
                            <option value="">
                                すべて
                            </option>

                            @foreach ($daysOfWeek as $dayOfWeek)
                                <option
                                    value="{{ $dayOfWeek->value }}"
                                    @selected(
                                        (string) $selectedDayOfWeek
                                            === (string) $dayOfWeek->value
                                    )
                                >
                                    {{ $dayOfWeek->label() }}
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
                            href="{{ route('admin.timetable-slots.index') }}"
                            class="inline-flex w-full items-center justify-center rounded-lg border
                                lms-border-neutral-default lms-bg-surface px-5 py-2.5 font-semibold
                                lms-text-neutral-secondary lms-hover-bg-neutral-subtle"
                        >
                            クリア
                        </a>
                    </div>
                </div>
            </form>

            <p class="mt-4 text-sm lms-text-neutral-muted">
                年度・学年・クラスをすべて選択すると、週間時間割表も表示されます。
            </p>
        </div>

        @if ($showWeeklyGrid)
            <div class="overflow-hidden lms-panel">
                <div class="border-b lms-border-neutral-subtle px-6 py-5">
                    <h2 class="text-lg font-bold lms-text-neutral-strong">
                        週間時間割
                    </h2>

                    <p class="mt-1 text-sm lms-text-neutral-muted">
                        {{ $selectedAcademicYear }}年度
                        /
                        {{ \App\Enums\Grade::from((string) $selectedGrade)->label() }}
                        /
                        {{ $classGroups->firstWhere('id', $selectedClassGroupId)?->class_name }}
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-[1100px] w-full table-fixed border-collapse lms-table">
                        <thead class="lms-bg-neutral-subtle">
                            <tr>
                                <th
                                    class="w-20 border-b border-r lms-border-neutral-subtle px-3 py-3 text-center
                                        text-xs font-semibold lms-text-neutral-muted"
                                >
                                    時限
                                </th>

                                @foreach ($daysOfWeek as $dayOfWeek)
                                    <th
                                        class="border-b border-r lms-border-neutral-subtle px-3 py-3 text-center
                                            text-xs font-semibold lms-text-neutral-muted last:border-r-0"
                                    >
                                        {{ $dayOfWeek->shortLabel() }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($periods as $periodNo)
                                <tr>
                                    <th
                                        class="border-b border-r lms-border-neutral-subtle lms-bg-neutral-subtle px-3
                                            py-4 text-center text-sm font-bold lms-text-neutral-secondary"
                                    >
                                        {{ $periodNo }}限
                                    </th>

                                    @foreach ($daysOfWeek as $dayOfWeek)
                                        @php
                                            $cellKey = sprintf(
                                                '%d-%d',
                                                $periodNo,
                                                $dayOfWeek->value,
                                            );

                                            $cellSlots = $weeklySlots->get(
                                                $cellKey,
                                                collect(),
                                            );
                                        @endphp

                                        <td
                                            class="h-32 border-b border-r lms-border-neutral-subtle p-2 align-top
                                                last:border-r-0"
                                        >
                                            @forelse ($cellSlots as $slot)
                                                <a
                                                    href="{{ route(
                                                        'admin.timetable-slots.edit',
                                                        $slot,
                                                    ) }}"
                                                    class="mb-2 block rounded-lg border lms-border-neutral-subtle
                                                        lms-bg-neutral-subtle p-3 lms-hover-border-neutral-strong
                                                        lms-hover-bg-surface"
                                                >
                                                    <div class="text-sm font-bold lms-text-neutral-strong">
                                                        {{ $slot->course->course_name }}
                                                    </div>

                                                    <div class="mt-1 text-xs lms-text-neutral-muted">
                                                        {{ $slot->course->subject->subject_name }}
                                                    </div>

                                                    @if ($slot->course->teacher !== null)
                                                        <div class="mt-1 text-xs lms-text-neutral-muted">
                                                            {{ $slot->course->teacher->user->name }}
                                                        </div>
                                                    @endif

                                                    @if (
                                                        $slot->start_time !== null
                                                        && $slot->end_time !== null
                                                    )
                                                        <div class="mt-2 text-xs font-medium lms-text-neutral-subtle">
                                                            {{ substr($slot->start_time, 0, 5) }}
                                                            ～
                                                            {{ substr($slot->end_time, 0, 5) }}
                                                        </div>
                                                    @endif

                                                    @if ($slot->status === \App\Enums\MasterStatus::Inactive)
                                                        <span
                                                            class="mt-2 inline-flex whitespace-nowrap rounded-full
                                                                lms-bg-neutral-disabled px-2 py-0.5 text-xs
                                                                font-semibold lms-text-neutral-secondary"
                                                        >
                                                            無効
                                                        </span>
                                                    @endif
                                                </a>
                                            @empty
                                                <a
                                                    href="{{ route('admin.timetable-slots.create', [
                                                        'day_of_week' => $dayOfWeek->value,
                                                        'period_no' => $periodNo,
                                                    ]) }}"
                                                    class="flex h-full min-h-24 items-center justify-center rounded-lg
                                                        border border-dashed lms-border-neutral-default text-sm
                                                        font-semibold lms-text-neutral-disabled
                                                        lms-hover-border-neutral-emphasis lms-hover-text-neutral-subtle"
                                                >
                                                    未登録
                                                </a>
                                            @endforelse
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="overflow-hidden lms-panel">
            <div class="border-b lms-border-neutral-subtle px-6 py-5">
                <h2 class="text-lg font-bold lms-text-neutral-strong">
                    時間割一覧
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y lms-divide-neutral-subtle lms-table lms-table--balanced">
                    <thead class="lms-bg-neutral-subtle">
                        <tr>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider
                                    lms-text-neutral-muted lms-table-col--medium"
                            >
                                対象
                            </th>

                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider
                                    lms-text-neutral-muted"
                            >
                                授業
                            </th>

                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider
                                    lms-text-neutral-muted lms-table-col--medium"
                            >
                                曜日・時限
                            </th>

                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider
                                    lms-text-neutral-muted lms-table-col--medium"
                            >
                                時刻
                            </th>

                            <th
                                class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider
                                    lms-text-neutral-muted lms-table-col--wide"
                            >
                                担当教員
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
                        @forelse ($timetableSlots as $timetableSlot)
                            <tr class="lms-hover-bg-neutral-subtle">
                                <td class="whitespace-nowrap px-5 py-4 text-sm lms-text-neutral-secondary">
                                    <div class="font-semibold lms-text-neutral-strong">
                                        {{ $timetableSlot->course->academic_year }}年度
                                        /
                                        {{ $timetableSlot->course->grade->label() }}
                                    </div>

                                    <div class="mt-1 text-xs lms-text-neutral-muted">
                                        {{ $timetableSlot->course->classGroup->class_code }}
                                        /
                                        {{ $timetableSlot->course->classGroup->class_name }}
                                    </div>
                                </td>

                                <td class="px-5 py-4 text-sm lms-text-neutral-secondary">
                                    <div class="font-semibold lms-text-neutral-strong">
                                        {{ $timetableSlot->course->course_name }}
                                    </div>

                                    <div class="mt-1 text-xs lms-text-neutral-muted">
                                        {{ $timetableSlot->course->subject->subject_code }}
                                        /
                                        {{ $timetableSlot->course->subject->subject_name }}
                                    </div>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm lms-text-neutral-secondary">
                                    <div class="font-semibold lms-text-neutral-strong">
                                        {{ $timetableSlot->day_of_week->label() }}
                                    </div>

                                    <div class="mt-1 text-xs lms-text-neutral-muted">
                                        {{ $timetableSlot->period_no }}時限
                                    </div>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm lms-text-neutral-secondary">
                                    @if (
                                        $timetableSlot->start_time !== null
                                        && $timetableSlot->end_time !== null
                                    )
                                        {{ substr($timetableSlot->start_time, 0, 5) }}
                                        ～
                                        {{ substr($timetableSlot->end_time, 0, 5) }}
                                    @else
                                        <span class="lms-text-neutral-disabled">
                                            未設定
                                        </span>
                                    @endif
                                </td>

                                <td class="px-5 py-4 text-sm lms-text-neutral-secondary">
                                    @if ($timetableSlot->course->teacher !== null)
                                        <div class="font-medium lms-text-neutral-strong">
                                            {{ $timetableSlot->course->teacher->user->name }}
                                        </div>

                                        <div class="lms-table-cell--email mt-1 text-xs lms-text-neutral-muted">
                                            {{ $timetableSlot->course->teacher->user->email }}
                                        </div>
                                    @else
                                        <span class="lms-text-neutral-disabled">
                                            未設定
                                        </span>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm">
                                    <span
                                        @class([
                                            'inline-flex whitespace-nowrap rounded-full',
                                            'px-2.5 py-1 text-xs font-semibold',
                                            'lms-bg-success-muted lms-text-success-strong' => $timetableSlot->status
                                            === \App\Enums\MasterStatus::Active,
                                            'lms-bg-neutral-muted lms-text-neutral-secondary' => $timetableSlot->status
                                            === \App\Enums\MasterStatus::Inactive,
                                        ])
                                    >
                                        {{ $timetableSlot->status->label() }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm">
                                    <a
                                        href="{{ route(
                                            'admin.timetable-slots.edit',
                                            $timetableSlot,
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
                                    条件に一致する時間割はありません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($timetableSlots->hasPages())
                <div class="border-t lms-border-neutral-subtle px-6 py-4">
                    {{ $timetableSlots->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
