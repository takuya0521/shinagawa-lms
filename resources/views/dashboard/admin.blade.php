@extends('layouts.app')

@section('page-class', 'page-pattern-dashboard page-dashboard-admin')

@section('title', '管理者ダッシュボード')
@section('header-title', '管理者ダッシュボード')

@section('content')
    <div class="space-y-6">

        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <a href="{{ route('admin.timetable-slots.index') }}" class="p-6 lms-panel lms-panel--interactive">
                <p class="text-sm font-semibold lms-text-neutral-muted">本日の授業</p>
                <p
                    class="mt-2 text-3xl font-bold lms-text-neutral-strong"
                >{{ $todayLessonCount }}<span class="ml-1 text-base font-semibold">件</span></p>
                <p class="mt-2 text-xs lms-text-neutral-muted">{{ $today->format('Y年m月d日') }}</p>
            </a>
            <a href="{{ route('admin.attendance.index') }}" class="p-6 lms-panel lms-panel--interactive">
                <p class="text-sm font-semibold lms-text-neutral-muted">出欠未登録</p>
                <p
                    class="mt-2 text-3xl font-bold lms-text-warning"
                >{{ $unregisteredAttendanceCount }}<span class="ml-1 text-base font-semibold">授業</span></p>
                <p class="mt-2 text-xs lms-text-neutral-muted">対象生徒の記録が不足している授業</p>
            </a>
            <a
                href="{{ route('admin.evaluations.index', ['academic_year' => $academicYear, 'missing_only' => 1]) }}"
                class="p-6 lms-panel lms-panel--interactive"
            >
                <p class="text-sm font-semibold lms-text-neutral-muted">未確定評価</p>
                <p
                    class="mt-2 text-3xl font-bold lms-text-primary"
                >{{ $unconfirmedEvaluationCount }}<span class="ml-1 text-base font-semibold">件</span></p>
                <p class="mt-2 text-xs lms-text-neutral-muted">{{ $academicYear }}年度・通年</p>
            </a>
            <a
                href="{{ route('admin.users.index', ['status' => \App\Enums\UserStatus::Suspended->value]) }}"
                class="p-6 lms-panel lms-panel--interactive"
            >
                <p class="text-sm font-semibold lms-text-neutral-muted">利用停止ユーザー</p>
                <p
                    class="mt-2 text-3xl font-bold lms-text-neutral-strong"
                >{{ $suspendedUserCount }}<span class="ml-1 text-base font-semibold">人</span></p>
            </a>
        </section>

        <section class="p-6 lms-panel">
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
                    <a
                        href="{{ route($menu['route']) }}"
                        class="rounded-xl border lms-border-neutral-subtle px-4 py-4 font-semibold
                            lms-hover-bg-neutral-subtle"
                    >{{ $menu['label'] }}</a>
                @endforeach
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="p-6 lms-panel">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-bold">本日の授業</h2>
                        <p class="mt-1 text-sm lms-text-neutral-muted">時限順に表示します。</p>
                    </div>
                    <a
                        href="{{ route('admin.attendance.index') }}"
                        class="text-sm font-semibold lms-link-primary"
                    >出欠管理</a>
                </div>
                <div class="mt-5 space-y-3">
                    @forelse ($todaySlots as $slot)
                        <article class="rounded-xl border lms-border-neutral-subtle p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p
                                        class="text-sm font-semibold lms-text-neutral-muted"
                                    >{{ $slot->period_no }}時限 / {{ $slot->course->classGroup->class_name }}</p>
                                    <h3 class="mt-1 font-bold">{{ $slot->course->course_name }}</h3>
                                    <p
                                        class="mt-1 text-sm lms-text-neutral-subtle"
                                    >{{ $slot->course->subject->subject_name }} / 担当 {{
                                    $slot->course->teacher?->user?->name ?? '未設定' }}</p>
                                </div>
                                @if ((bool) $slot->getAttribute('is_cancelled'))
                                    <span
                                        class="rounded-full lms-bg-neutral-muted px-3 py-1 text-xs font-semibold"
                                    >休講</span>
                                @elseif ((int) $slot->getAttribute('missing_count') > 0)
                                    <span
                                        class="rounded-full lms-bg-warning-muted px-3 py-1 text-xs font-semibold
                                            lms-text-warning-strong"
                                    >未登録 {{ $slot->getAttribute('missing_count') }}名</span>
                                @else
                                    <span
                                        class="rounded-full lms-bg-success-muted px-3 py-1 text-xs font-semibold
                                            lms-text-success-strong"
                                    >登録済み</span>
                                @endif
                            </div>
                        </article>
                    @empty
                        <p
                            class="rounded-xl lms-bg-neutral-subtle px-5 py-8 text-center text-sm
                                lms-text-neutral-muted"
                        >本日の授業はありません。</p>
                    @endforelse
                </div>
            </section>

            <section class="p-6 lms-panel">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-bold">重要なお知らせ</h2>
                        <p class="mt-1 text-sm lms-text-neutral-muted">掲載中の重要なお知らせを最大10件表示します。</p>
                    </div>
                    <a
                        href="{{ route('admin.announcements.index') }}"
                        class="text-sm font-semibold lms-link-primary"
                    >掲示板管理</a>
                </div>
                <div class="mt-5 space-y-3">
                    @forelse ($importantAnnouncements as $announcement)
                        <a
                            href="{{ route('admin.announcements.edit', $announcement) }}"
                            class="block rounded-xl border lms-border-danger-faint lms-bg-danger-soft p-4
                                lms-hover-bg-danger-muted"
                        >
                            <p
                                class="text-xs font-semibold lms-text-danger-strong"
                            >{{ $announcement->notice_type->label() }}</p>
                            <h3 class="mt-1 font-bold lms-text-neutral-strong">{{ $announcement->title }}</h3>
                            <p
                                class="mt-1 text-xs lms-text-neutral-muted"
                            >{{ $announcement->publish_start_at?->format('Y/m/d H:i') ?? '即時' }}</p>
                        </a>
                    @empty
                        <p
                            class="rounded-xl lms-bg-neutral-subtle px-5 py-8 text-center text-sm
                                lms-text-neutral-muted"
                        >掲載中の重要なお知らせはありません。</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
