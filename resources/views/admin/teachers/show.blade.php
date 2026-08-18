@extends('layouts.app')

@section('page-class', 'page-pattern-detail page-admin-teachers-show')

@section('title', '教員詳細')
@section('header-title', '教員詳細')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-end">

            <div class="flex flex-wrap gap-3">
                <a
                    href="{{ route('admin.course-teacher-assignments.index', [
                        'keyword' => $teacher->user->name,
                        'academic_year' => '',
                    ]) }}"
                    class="rounded-lg border lms-border-neutral-default lms-bg-surface px-5 py-3 font-semibold
                        lms-text-neutral-secondary lms-hover-bg-neutral-subtle"
                >
                    担当授業を設定
                </a>

                <a
                    href="{{ route('admin.teachers.edit', $teacher) }}"
                    class="rounded-lg px-5 py-3 font-semibold lms-button-primary"
                >
                    編集する
                </a>

                <a
                    href="{{ route('admin.teachers.index') }}"
                    class="rounded-lg border lms-border-neutral-default px-5 py-3 font-semibold
                        lms-hover-bg-neutral-subtle"
                >
                    一覧へ戻る
                </a>
            </div>
        </header>

        <section class="overflow-hidden lms-panel">
            <div class="border-b lms-border-neutral-subtle px-6 py-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-xl font-bold lms-text-neutral-strong">
                        {{ $teacher->user->name }}
                    </h2>

                    <span
                        @class([
                            'inline-flex whitespace-nowrap rounded-full',
                            'px-4 py-2 text-sm font-semibold',
                            'lms-bg-success-muted lms-text-success' => $teacher->status ===
                            \App\Enums\MasterStatus::Active,
                            'lms-bg-neutral-disabled lms-text-neutral-secondary' => $teacher->status ===
                            \App\Enums\MasterStatus::Inactive,
                        ])
                    >
                        {{ $teacher->status->label() }}
                    </span>
                </div>
            </div>

            <dl>
                <div class="border-b lms-border-neutral-faint px-6 py-5">
                    <dt class="text-sm font-medium lms-text-neutral-muted">
                        担当科目メモ
                    </dt>

                    <dd
                        class="mt-2 whitespace-pre-wrap lms-text-neutral-strong"
                    >{{ $teacher->subject_notes ?? '未設定' }}</dd>
                </div>

                <div class="px-6 py-5">
                    <dt class="text-sm font-medium lms-text-neutral-muted">
                        教員状態
                    </dt>

                    <dd class="mt-2 lms-text-neutral-strong">
                        {{ $teacher->status->label() }}
                    </dd>
                </div>
            </dl>
        </section>

        <section class="overflow-hidden lms-panel">
            <div class="border-b lms-border-neutral-subtle px-6 py-5">
                <h2 class="text-lg font-bold lms-text-neutral-strong">
                    ログインアカウント
                </h2>
            </div>

            <dl class="grid md:grid-cols-2">
                <div class="border-b lms-border-neutral-faint lms-detail-grid-divider px-6 py-5">
                    <dt class="text-sm font-medium lms-text-neutral-muted">
                        氏名
                    </dt>

                    <dd class="mt-2 lms-text-neutral-strong">
                        {{ $teacher->user->name }}
                    </dd>
                </div>

                <div class="border-b lms-border-neutral-faint px-6 py-5">
                    <dt class="text-sm font-medium lms-text-neutral-muted">
                        メールアドレス
                    </dt>

                    <dd class="mt-2 break-all lms-text-neutral-strong">
                        {{ $teacher->user->email }}
                    </dd>
                </div>

                <div class="lms-detail-grid-divider px-6 py-5">
                    <dt class="text-sm font-medium lms-text-neutral-muted">
                        ロール
                    </dt>

                    <dd class="mt-2 lms-text-neutral-strong">
                        {{ $teacher->user->role->label() }}
                    </dd>
                </div>

                <div class="px-6 py-5">
                    <dt class="text-sm font-medium lms-text-neutral-muted">
                        アカウント利用状態
                    </dt>

                    <dd class="mt-2">
                        <span
                            @class([
                                'inline-flex whitespace-nowrap rounded-full',
                                'px-3 py-1 text-sm font-semibold',
                                'lms-bg-success-muted lms-text-success' => $teacher->user->status ===
                                \App\Enums\UserStatus::Active,
                                'lms-bg-neutral-disabled lms-text-neutral-secondary' => $teacher->user->status ===
                                \App\Enums\UserStatus::Suspended,
                            ])
                        >
                            {{ $teacher->user->status->label() }}
                        </span>
                    </dd>
                </div>
            </dl>
        </section>

        <section class="overflow-hidden lms-panel">
            <div
                class="flex flex-col gap-3 border-b lms-border-neutral-subtle px-6 py-5 sm:flex-row sm:items-center
                    sm:justify-between"
            >
                <div>
                    <h2 class="text-lg font-bold lms-text-neutral-strong">担当授業・対象生徒</h2>
                    <p class="mt-1 text-sm lms-text-neutral-muted">
                        現在この教員へ設定されている授業を最大20件表示します。
                    </p>
                </div>

                <a
                    href="{{ route('admin.course-teacher-assignments.index', [
                        'keyword' => $teacher->user->name,
                        'academic_year' => '',
                    ]) }}"
                    class="text-sm font-semibold lms-link-primary"
                >
                    担当教員設定を開く
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y lms-divide-neutral-subtle lms-table lms-table--balanced">
                    <thead class="lms-bg-neutral-subtle">
                        <tr>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted
                                    lms-table-col--medium"
                            >年度・対象</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted">授業・科目</th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted
                                    lms-table-col--medium"
                            >時間割</th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted
                                    lms-table-col--medium"
                            >対象生徒</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted">外部リンク</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y lms-divide-neutral-subtle lms-bg-surface">
                        @forelse ($assignedCourses as $course)
                            @php
                                $targetStudents = $targetStudentsByCourse[$course->id] ?? collect();
                            @endphp

                            <tr class="align-top lms-hover-bg-neutral-subtle">
                                <td class="whitespace-nowrap px-5 py-4 text-sm">
                                    <p class="font-semibold lms-text-neutral-strong">{{ $course->academic_year }}年度</p>
                                    <p class="mt-1 text-xs lms-text-neutral-muted">
                                        {{ $course->grade->label() }} / {{ $course->classGroup->class_code }}・{{
                                        $course->classGroup->class_name }}
                                    </p>
                                </td>

                                <td class="px-5 py-4 text-sm">
                                    <a
                                        href="{{ route('admin.courses.edit', $course) }}"
                                        class="font-semibold lms-link-primary"
                                    >
                                        {{ $course->course_name }}
                                    </a>
                                    <p class="mt-1 text-xs lms-text-neutral-muted">
                                        {{ $course->subject->subject_code }} / {{ $course->subject->subject_name }}
                                    </p>
                                </td>

                                <td class="px-5 py-4 text-sm lms-text-neutral-secondary">
                                    @forelse ($course->timetableSlots as $slot)
                                        <span
                                            class="mr-1 inline-flex whitespace-nowrap rounded-full lms-bg-neutral-muted
                                                px-2.5 py-1
                                                text-xs font-semibold lms-text-neutral-secondary"
                                        >
                                            {{ $slot->day_of_week->shortLabel() }}曜 {{ $slot->period_no }}時限
                                        </span>
                                    @empty
                                        <span class="lms-text-neutral-disabled">未設定</span>
                                    @endforelse
                                </td>

                                <td class="min-w-64 px-5 py-4 text-sm">
                                    <p class="font-semibold lms-text-neutral-strong">{{ $targetStudents->count() }}名</p>

                                    @if ($targetStudents->isNotEmpty())
                                        <p class="mt-1 text-xs leading-5 lms-text-neutral-muted">
                                            {{ $targetStudents->take(5)->pluck('student_name')->join('、') }}
                                            @if ($targetStudents->count() > 5)
                                                ほか{{ $targetStudents->count() - 5 }}名
                                            @endif
                                        </p>
                                    @else
                                        <p class="mt-1 text-xs lms-text-neutral-disabled">対象となる在籍生徒はいません。</p>
                                    @endif
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
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-sm lms-text-neutral-muted">
                                    担当授業は設定されていません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="p-6 lms-panel">
            <h2 class="text-lg font-bold lms-text-neutral-strong">
                システム情報
            </h2>

            <dl class="mt-4 grid gap-5 md:grid-cols-3">
                <div>
                    <dt class="text-sm font-medium lms-text-neutral-muted">
                        教員ID
                    </dt>

                    <dd class="mt-1 lms-text-neutral-strong">
                        {{ $teacher->id }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm font-medium lms-text-neutral-muted">
                        登録日時
                    </dt>

                    <dd class="mt-1 lms-text-neutral-strong">
                        {{ $teacher->created_at?->format('Y/m/d H:i') }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm font-medium lms-text-neutral-muted">
                        更新日時
                    </dt>

                    <dd class="mt-1 lms-text-neutral-strong">
                        {{ $teacher->updated_at?->format('Y/m/d H:i') }}
                    </dd>
                </div>
            </dl>
        </section>
    </div>
@endsection
