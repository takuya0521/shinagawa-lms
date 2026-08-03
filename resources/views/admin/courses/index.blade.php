@extends('layouts.app')

@section('page-style', 'resources/css/pages/admin/courses/index.css')
@section('page-class', 'page-pattern-list page-admin-courses-index')

@section('title', '授業管理')
@section('header-title', '授業管理')

@section('content')
    <section class="space-y-6">
        <div class="flex flex-col gap-4 rounded-2xl bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    授業管理
                </h1>

                <p class="mt-2 text-sm text-slate-600">
                    年度・学年・クラスごとの授業と担当教員を管理します。
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a
                    href="{{ route('admin.course-teacher-assignments.index') }}"
                    class="inline-flex shrink-0 items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-3 font-semibold text-slate-700 hover:bg-slate-50"
                >
                    担当教員設定
                </a>

                <a
                    href="{{ route('admin.courses.create') }}"
                    class="inline-flex shrink-0 items-center justify-center rounded-lg bg-slate-900 px-5 py-3 font-semibold text-white hover:bg-slate-700"
                >
                    授業を登録
                </a>
            </div>
        </div>


        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <form
                method="GET"
                action="{{ route('admin.courses.index') }}"
                class="space-y-4"
            >
                <div class="grid gap-4 lg:grid-cols-5">
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
                            placeholder="授業名・科目・担当教員・Classroom ID"
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
                            href="{{ route('admin.courses.index') }}"
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
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                年度
                            </th>

                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                学年・クラス
                            </th>

                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                科目・授業名
                            </th>

                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                担当教員
                            </th>

                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Classroom
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
                        @forelse ($courses as $course)
                            <tr class="hover:bg-slate-50">
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-900">
                                    {{ $course->academic_year }}年度
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-700">
                                    <div class="font-semibold text-slate-900">
                                        {{ $course->grade->label() }}
                                    </div>

                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ $course->classGroup->class_code }}
                                        /
                                        {{ $course->classGroup->class_name }}
                                    </div>
                                </td>

                                <td class="px-5 py-4 text-sm text-slate-700">
                                    <div class="font-semibold text-slate-900">
                                        {{ $course->course_name }}
                                    </div>

                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ $course->subject->subject_code }}
                                        /
                                        {{ $course->subject->subject_name }}
                                    </div>
                                </td>

                                <td class="px-5 py-4 text-sm text-slate-700">
                                    @if ($course->teacher !== null)
                                        <div class="font-medium text-slate-900">
                                            {{ $course->teacher->user->name }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ $course->teacher->user->email }}
                                        </div>
                                    @else
                                        <span class="text-slate-400">
                                            未設定
                                        </span>
                                    @endif
                                </td>

                                <td class="px-5 py-4 text-sm text-slate-700">
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
                                        <span class="text-slate-400">
                                            未設定
                                        </span>
                                    @endif

                                    @if ($course->google_classroom_id !== null)
                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ $course->google_classroom_id }}
                                        </div>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm">
                                    <span
                                        @class([
                                            'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                            'bg-emerald-100 text-emerald-800' => $course->status === \App\Enums\MasterStatus::Active,
                                            'bg-slate-100 text-slate-700' => $course->status === \App\Enums\MasterStatus::Inactive,
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
