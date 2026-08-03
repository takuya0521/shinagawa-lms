@extends('layouts.app')

@section('page-style', 'resources/css/pages/dashboard/teacher.css')
@section('page-class', 'page-pattern-dashboard page-dashboard-teacher')

@section('title', '教員ダッシュボード')
@section('header-title', '教員ダッシュボード')

@section('content')
    <div class="space-y-6">
        <section class="rounded-2xl bg-white p-8 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">T-001</p>
            <h1 class="mt-1 text-2xl font-bold">{{ $teacher->user->name }}先生</h1>
            <p class="mt-3 text-slate-600">担当授業の出欠・評価・面談と、学校からのお知らせを確認します。</p>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <a href="{{ route('teacher.courses.index') }}" class="rounded-2xl bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <p class="text-sm font-semibold text-slate-500">担当授業</p>
                <p class="mt-2 text-3xl font-bold">{{ $assignedCourseCount }}<span class="ml-1 text-base">件</span></p>
            </a>
            <a href="{{ route('teacher.attendance.index') }}" class="rounded-2xl bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <p class="text-sm font-semibold text-slate-500">本日の出欠未登録</p>
                <p class="mt-2 text-3xl font-bold text-amber-700">{{ $unregisteredCount }}<span class="ml-1 text-base">授業</span></p>
            </a>
            <a href="{{ route('teacher.evaluations.index', ['academic_year' => $academicYear]) }}" class="rounded-2xl bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <p class="text-sm font-semibold text-slate-500">評価未確定</p>
                <p class="mt-2 text-3xl font-bold text-blue-700">{{ $evaluationPendingCount }}<span class="ml-1 text-base">件</span></p>
            </a>
            <a href="{{ route('teacher.interviews.index') }}" class="rounded-2xl bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <p class="text-sm font-semibold text-slate-500">面談記録</p>
                <p class="mt-2 text-lg font-bold">登録・履歴確認</p>
            </a>
        </section>

        <section class="rounded-2xl bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold">本日の担当授業</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $today->format('Y年m月d日') }}</p>
                </div>
                <a href="{{ route('teacher.courses.index') }}" class="text-sm font-semibold text-blue-700">担当授業一覧</a>
            </div>

            <div class="mt-5 space-y-3">
                @forelse ($todaySlots as $slot)
                    <article class="rounded-xl border border-slate-200 p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <p class="text-sm font-semibold text-slate-500">
                                    {{ $slot->period_no }}時限
                                    @if ($slot->start_time !== null && $slot->end_time !== null)
                                        ・{{ substr((string) $slot->start_time, 0, 5) }}～{{ substr((string) $slot->end_time, 0, 5) }}
                                    @endif
                                </p>
                                <h3 class="mt-1 text-lg font-bold">{{ $slot->course->course_name }}</h3>
                                <p class="mt-1 text-sm text-slate-600">{{ $slot->course->subject->subject_name }} / {{ $slot->course->grade->label() }} / {{ $slot->course->classGroup->class_name }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                @if ((bool) $slot->getAttribute('is_cancelled'))
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-700">休講</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold">登録 {{ $slot->getAttribute('recorded_count') }}/{{ $slot->getAttribute('target_count') }}名</span>
                                    @if ((int) $slot->getAttribute('missing_count') > 0)
                                        <span class="rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-800">未登録 {{ $slot->getAttribute('missing_count') }}名</span>
                                    @else
                                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-800">登録済み</span>
                                    @endif
                                @endif
                                @if ($slot->course->google_classroom_url !== null)
                                    <a href="{{ $slot->course->google_classroom_url }}" target="_blank" rel="noopener noreferrer" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold hover:bg-slate-50">Classroom</a>
                                @endif
                                @unless ((bool) $slot->getAttribute('is_cancelled'))
                                    <a href="{{ route('teacher.attendance.edit', ['timetable_slot_id' => $slot->id, 'lesson_date' => $today->format('Y-m-d')]) }}" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">出欠登録</a>
                                @endunless
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="rounded-xl bg-slate-50 px-5 py-8 text-center text-sm text-slate-500">本日の担当授業はありません。</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold">学校からのお知らせ</h2>
                    <p class="mt-1 text-sm text-slate-500">担当学年・担当クラスを含む掲載中のお知らせです。</p>
                </div>
                <a href="{{ route('teacher.announcements.index') }}" class="text-sm font-semibold text-blue-700">掲示板を開く</a>
            </div>
            <div class="mt-5 grid gap-3 md:grid-cols-2">
                @forelse ($announcements as $announcement)
                    <a href="{{ route('teacher.announcements.show', $announcement) }}" @class(['rounded-xl border p-4 hover:bg-slate-50', 'border-red-200 bg-red-50' => $announcement->is_important, 'border-slate-200' => ! $announcement->is_important])>
                        <div class="flex items-center gap-2">
                            @if ($announcement->is_important)<span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">重要</span>@endif
                            <span class="text-xs font-semibold text-slate-500">{{ $announcement->notice_type->label() }}</span>
                        </div>
                        <h3 class="mt-2 font-bold">{{ $announcement->title }}</h3>
                    </a>
                @empty
                    <p class="rounded-xl bg-slate-50 px-5 py-8 text-center text-sm text-slate-500 md:col-span-2">掲載中のお知らせはありません。</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
