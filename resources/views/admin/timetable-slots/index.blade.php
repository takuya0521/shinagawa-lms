@extends('layouts.app')

@section('page-style', 'resources/css/pages/admin/timetable-slots/index.css')
@section('page-class', 'page-pattern-timetable page-admin-timetable-slots-index')

@section('title', '時間割管理')
@section('header-title', '時間割管理')

@section('content')
    <section class="space-y-6">
        <div class="flex flex-col gap-4 rounded-2xl bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    時間割管理
                </h1>

                <p class="mt-2 text-sm text-slate-600">
                    年度・学年・クラスごとの授業を曜日と時限へ割り当てます。
                </p>
            </div>

            <a
                href="{{ route('admin.timetable-slots.create') }}"
                class="inline-flex shrink-0 items-center justify-center rounded-lg bg-slate-900 px-5 py-3 font-semibold text-white hover:bg-slate-700"
            >
                時間割を登録
            </a>
        </div>


        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <form
                method="GET"
                action="{{ route('admin.timetable-slots.index') }}"
                class="space-y-4"
            >
                <div class="grid gap-4 lg:grid-cols-6">
                    <div class="lg:col-span-2">
                        <label
                            for="keyword"
                            class="block text-sm font-semibold text-slate-700"
                        >
                            キーワード
                        </label>

                        <input
                            id="keyword"
                            name="keyword"
                            type="search"
                            value="{{ $keyword }}"
                            placeholder="授業名・科目・担当教員"
                            class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                        >
                    </div>

                    <div>
                        <label
                            for="academic_year"
                            class="block text-sm font-semibold text-slate-700"
                        >
                            年度
                        </label>

                        <select
                            id="academic_year"
                            name="academic_year"
                            class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
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
                            class="block text-sm font-semibold text-slate-700"
                        >
                            学年
                        </label>

                        <select
                            id="grade"
                            name="grade"
                            class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
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
                            class="block text-sm font-semibold text-slate-700"
                        >
                            曜日
                        </label>

                        <select
                            id="day_of_week"
                            name="day_of_week"
                            class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
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
                            class="block text-sm font-semibold text-slate-700"
                        >
                            状態
                        </label>

                        <select
                            id="status"
                            name="status"
                            class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
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
                            class="block text-sm font-semibold text-slate-700"
                        >
                            クラス
                        </label>

                        <select
                            id="class_group_id"
                            name="class_group_id"
                            class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
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
                            class="inline-flex w-full items-center justify-center rounded-lg bg-slate-900 px-5 py-2.5 font-semibold text-white hover:bg-slate-700"
                        >
                            検索
                        </button>
                    </div>

                    <div class="flex items-end">
                        <a
                            href="{{ route('admin.timetable-slots.index') }}"
                            class="inline-flex w-full items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            クリア
                        </a>
                    </div>
                </div>
            </form>

            <p class="mt-4 text-sm text-slate-500">
                年度・学年・クラスをすべて選択すると、週間時間割表も表示されます。
            </p>
        </div>

        @if ($showWeeklyGrid)
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-5">
                    <h2 class="text-lg font-bold text-slate-900">
                        週間時間割
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        {{ $selectedAcademicYear }}年度
                        /
                        {{ \App\Enums\Grade::from((string) $selectedGrade)->label() }}
                        /
                        {{ $classGroups->firstWhere('id', $selectedClassGroupId)?->class_name }}
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-[1100px] w-full table-fixed border-collapse">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="w-20 border-b border-r border-slate-200 px-3 py-3 text-center text-xs font-semibold text-slate-500">
                                    時限
                                </th>

                                @foreach ($daysOfWeek as $dayOfWeek)
                                    <th class="border-b border-r border-slate-200 px-3 py-3 text-center text-xs font-semibold text-slate-500 last:border-r-0">
                                        {{ $dayOfWeek->shortLabel() }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($periods as $periodNo)
                                <tr>
                                    <th class="border-b border-r border-slate-200 bg-slate-50 px-3 py-4 text-center text-sm font-bold text-slate-700">
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

                                        <td class="h-32 border-b border-r border-slate-200 p-2 align-top last:border-r-0">
                                            @forelse ($cellSlots as $slot)
                                                <a
                                                    href="{{ route(
                                                        'admin.timetable-slots.edit',
                                                        $slot,
                                                    ) }}"
                                                    class="mb-2 block rounded-lg border border-slate-200 bg-slate-50 p-3 hover:border-slate-400 hover:bg-white"
                                                >
                                                    <div class="text-sm font-bold text-slate-900">
                                                        {{ $slot->course->course_name }}
                                                    </div>

                                                    <div class="mt-1 text-xs text-slate-500">
                                                        {{ $slot->course->subject->subject_name }}
                                                    </div>

                                                    @if ($slot->course->teacher !== null)
                                                        <div class="mt-1 text-xs text-slate-500">
                                                            {{ $slot->course->teacher->user->name }}
                                                        </div>
                                                    @endif

                                                    @if (
                                                        $slot->start_time !== null
                                                        && $slot->end_time !== null
                                                    )
                                                        <div class="mt-2 text-xs font-medium text-slate-600">
                                                            {{ substr($slot->start_time, 0, 5) }}
                                                            ～
                                                            {{ substr($slot->end_time, 0, 5) }}
                                                        </div>
                                                    @endif

                                                    @if ($slot->status === \App\Enums\MasterStatus::Inactive)
                                                        <span class="mt-2 inline-flex rounded-full bg-slate-200 px-2 py-0.5 text-xs font-semibold text-slate-700">
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
                                                    class="flex h-full min-h-24 items-center justify-center rounded-lg border border-dashed border-slate-300 text-sm font-semibold text-slate-400 hover:border-slate-500 hover:text-slate-600"
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

        <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-5">
                <h2 class="text-lg font-bold text-slate-900">
                    時間割一覧
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                対象
                            </th>

                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                授業
                            </th>

                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                曜日・時限
                            </th>

                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                時刻
                            </th>

                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                担当教員
                            </th>

                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                状態
                            </th>

                            <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                操作
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($timetableSlots as $timetableSlot)
                            <tr class="hover:bg-slate-50">
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-700">
                                    <div class="font-semibold text-slate-900">
                                        {{ $timetableSlot->course->academic_year }}年度
                                        /
                                        {{ $timetableSlot->course->grade->label() }}
                                    </div>

                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ $timetableSlot->course->classGroup->class_code }}
                                        /
                                        {{ $timetableSlot->course->classGroup->class_name }}
                                    </div>
                                </td>

                                <td class="px-5 py-4 text-sm text-slate-700">
                                    <div class="font-semibold text-slate-900">
                                        {{ $timetableSlot->course->course_name }}
                                    </div>

                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ $timetableSlot->course->subject->subject_code }}
                                        /
                                        {{ $timetableSlot->course->subject->subject_name }}
                                    </div>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-700">
                                    <div class="font-semibold text-slate-900">
                                        {{ $timetableSlot->day_of_week->label() }}
                                    </div>

                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ $timetableSlot->period_no }}時限
                                    </div>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-700">
                                    @if (
                                        $timetableSlot->start_time !== null
                                        && $timetableSlot->end_time !== null
                                    )
                                        {{ substr($timetableSlot->start_time, 0, 5) }}
                                        ～
                                        {{ substr($timetableSlot->end_time, 0, 5) }}
                                    @else
                                        <span class="text-slate-400">
                                            未設定
                                        </span>
                                    @endif
                                </td>

                                <td class="px-5 py-4 text-sm text-slate-700">
                                    @if ($timetableSlot->course->teacher !== null)
                                        <div class="font-medium text-slate-900">
                                            {{ $timetableSlot->course->teacher->user->name }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ $timetableSlot->course->teacher->user->email }}
                                        </div>
                                    @else
                                        <span class="text-slate-400">
                                            未設定
                                        </span>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm">
                                    <span
                                        @class([
                                            'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                            'bg-emerald-100 text-emerald-800' => $timetableSlot->status === \App\Enums\MasterStatus::Active,
                                            'bg-slate-100 text-slate-700' => $timetableSlot->status === \App\Enums\MasterStatus::Inactive,
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
                                        class="font-semibold text-blue-700 hover:text-blue-900"
                                    >
                                        編集
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="7"
                                    class="px-6 py-12 text-center text-sm text-slate-500"
                                >
                                    条件に一致する時間割はありません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($timetableSlots->hasPages())
                <div class="border-t border-slate-200 px-6 py-4">
                    {{ $timetableSlots->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
