@extends('layouts.app')

@section('page-style', 'resources/css/pages/dashboard/admin.css')
@section('page-class', 'page-pattern-dashboard page-dashboard-admin')

@section('title', '管理者ダッシュボード')
@section('header-title', '管理者ダッシュボード')

@section('content')
    <div class="space-y-6">
        <section class="rounded-2xl bg-white p-8 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">A-001</p>
            <h1 class="mt-1 text-2xl font-bold">管理者ダッシュボード</h1>
            <p class="mt-3 text-slate-600">本日の授業・出欠、未確定評価、重要なお知らせを確認します。</p>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <a href="{{ route('admin.timetable-slots.index') }}" class="rounded-2xl bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <p class="text-sm font-semibold text-slate-500">本日の授業</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">{{ $todayLessonCount }}<span class="ml-1 text-base font-semibold">件</span></p>
                <p class="mt-2 text-xs text-slate-500">{{ $today->format('Y年m月d日') }}</p>
            </a>
            <a href="{{ route('admin.attendance.index') }}" class="rounded-2xl bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <p class="text-sm font-semibold text-slate-500">出欠未登録</p>
                <p class="mt-2 text-3xl font-bold text-amber-700">{{ $unregisteredAttendanceCount }}<span class="ml-1 text-base font-semibold">授業</span></p>
                <p class="mt-2 text-xs text-slate-500">対象生徒の記録が不足している授業</p>
            </a>
            <a href="{{ route('admin.evaluations.index', ['academic_year' => $academicYear, 'missing_only' => 1]) }}" class="rounded-2xl bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <p class="text-sm font-semibold text-slate-500">未確定評価</p>
                <p class="mt-2 text-3xl font-bold text-blue-700">{{ $unconfirmedEvaluationCount }}<span class="ml-1 text-base font-semibold">件</span></p>
                <p class="mt-2 text-xs text-slate-500">{{ $academicYear }}年度・通年</p>
            </a>
            <a href="{{ route('admin.users.index', ['status' => \App\Enums\UserStatus::Suspended->value]) }}" class="rounded-2xl bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <p class="text-sm font-semibold text-slate-500">利用停止ユーザー</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">{{ $suspendedUserCount }}<span class="ml-1 text-base font-semibold">人</span></p>
                <p class="mt-2 text-xs text-slate-500">GAM対象外のためローカル状態を表示</p>
            </a>
        </section>

        <section class="rounded-2xl bg-white p-6 shadow-sm">
            <h2 class="text-xl font-bold">管理メニュー</h2>
            <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['route' => 'admin.users.index', 'label' => 'ユーザー管理'],
                    ['route' => 'admin.students.index', 'label' => '生徒管理'],
                    ['route' => 'admin.teachers.index', 'label' => '教員管理'],
                    ['route' => 'admin.class-groups.index', 'label' => 'クラス管理'],
                    ['route' => 'admin.subjects.index', 'label' => '科目管理'],
                    ['route' => 'admin.courses.index', 'label' => '授業管理'],
                    ['route' => 'admin.course-teacher-assignments.index', 'label' => '担当教員設定'],
                    ['route' => 'admin.timetable-slots.index', 'label' => '時間割管理'],
                    ['route' => 'admin.attendance.index', 'label' => '出欠管理'],
                    ['route' => 'admin.evaluations.index', 'label' => '成績管理'],
                    ['route' => 'admin.interviews.index', 'label' => '面談記録'],
                    ['route' => 'admin.announcements.index', 'label' => '掲示板管理'],
                    ['route' => 'admin.external-links.index', 'label' => '外部リンク管理'],
                    ['route' => 'admin.operation-logs.index', 'label' => '操作ログ確認'],
                ] as $menu)
                    <a href="{{ route($menu['route']) }}" class="rounded-xl border border-slate-200 px-4 py-4 font-semibold hover:bg-slate-50">{{ $menu['label'] }}</a>
                @endforeach
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-2xl bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-bold">本日の授業</h2>
                        <p class="mt-1 text-sm text-slate-500">時限順に表示します。</p>
                    </div>
                    <a href="{{ route('admin.attendance.index') }}" class="text-sm font-semibold text-blue-700">出欠管理</a>
                </div>
                <div class="mt-5 space-y-3">
                    @forelse ($todaySlots as $slot)
                        <article class="rounded-xl border border-slate-200 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-500">{{ $slot->period_no }}時限 / {{ $slot->course->classGroup->class_name }}</p>
                                    <h3 class="mt-1 font-bold">{{ $slot->course->course_name }}</h3>
                                    <p class="mt-1 text-sm text-slate-600">{{ $slot->course->subject->subject_name }} / 担当 {{ $slot->course->teacher?->user?->name ?? '未設定' }}</p>
                                </div>
                                @if ((bool) $slot->getAttribute('is_cancelled'))
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold">休講</span>
                                @elseif ((int) $slot->getAttribute('missing_count') > 0)
                                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">未登録 {{ $slot->getAttribute('missing_count') }}名</span>
                                @else
                                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">登録済み</span>
                                @endif
                            </div>
                        </article>
                    @empty
                        <p class="rounded-xl bg-slate-50 px-5 py-8 text-center text-sm text-slate-500">本日の授業はありません。</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-2xl bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-bold">重要なお知らせ</h2>
                        <p class="mt-1 text-sm text-slate-500">掲載中の重要なお知らせを最大10件表示します。</p>
                    </div>
                    <a href="{{ route('admin.announcements.index') }}" class="text-sm font-semibold text-blue-700">掲示板管理</a>
                </div>
                <div class="mt-5 space-y-3">
                    @forelse ($importantAnnouncements as $announcement)
                        <a href="{{ route('admin.announcements.edit', $announcement) }}" class="block rounded-xl border border-red-100 bg-red-50 p-4 hover:bg-red-100/60">
                            <p class="text-xs font-semibold text-red-700">{{ $announcement->notice_type->label() }}</p>
                            <h3 class="mt-1 font-bold text-slate-900">{{ $announcement->title }}</h3>
                            <p class="mt-1 text-xs text-slate-500">{{ $announcement->publish_start_at?->format('Y/m/d H:i') ?? '即時' }}</p>
                        </a>
                    @empty
                        <p class="rounded-xl bg-slate-50 px-5 py-8 text-center text-sm text-slate-500">掲載中の重要なお知らせはありません。</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
