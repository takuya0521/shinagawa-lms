@extends('layouts.app')

@section('page-class', 'page-pattern-detail page-admin-students-show')

@section('title', '生徒詳細')
@section('header-title', '生徒詳細')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-end">

            <nav class="admin-student-actions" aria-label="生徒詳細の操作">
                <a
                    href="{{ route('admin.students.attendance.show', $student) }}"
                    class="admin-student-actions__link"
                >
                    出欠詳細
                </a>

                <a
                    href="{{ route('admin.evaluations.index', [
                        'student_id' => $student->id,
                    ]) }}"
                    class="admin-student-actions__link"
                >
                    成績詳細
                </a>

                <a
                    href="{{ route('admin.interviews.index', [
                        'student_id' => $student->id,
                    ]) }}"
                    class="admin-student-actions__link"
                >
                    面談履歴
                </a>

                <a
                    href="{{ route(
                        'admin.students.edit',
                        $student,
                    ) }}"
                    class="admin-student-actions__link admin-student-actions__link--primary"
                >
                    編集する
                </a>

                <a
                    href="{{ route('admin.students.index') }}"
                    class="admin-student-actions__link admin-student-actions__link--back"
                >
                    <span aria-hidden="true">←</span>
                    一覧へ戻る
                </a>
            </nav>
        </header>

        <section class="overflow-hidden lms-panel">
            <div class="border-b lms-border-neutral-subtle px-6 py-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm lms-text-neutral-muted">
                            {{ $student->student_no ?? '生徒番号未設定' }}
                        </p>

                        <h2 class="mt-1 text-xl font-bold lms-text-neutral-strong">
                            {{ $student->student_name }}
                        </h2>
                    </div>

                    <span
                        @class([
                            'inline-flex whitespace-nowrap rounded-full',
                            'px-4 py-2 text-sm font-semibold',
                            'lms-bg-success-muted lms-text-success' => $student->status ===
                            \App\Enums\StudentStatus::Active,
                            'lms-bg-warning-muted lms-text-warning' => $student->status ===
                            \App\Enums\StudentStatus::Suspended,
                            'lms-bg-info-muted lms-text-info' => $student->status ===
                            \App\Enums\StudentStatus::Graduated,
                            'lms-bg-withdrawn lms-text-withdrawn' => $student->status ===
                            \App\Enums\StudentStatus::Withdrawn,
                        ])
                    >
                        {{ $student->status->label() }}
                    </span>
                </div>
            </div>

            <dl class="grid md:grid-cols-2">
                <div class="border-b lms-border-neutral-faint lms-detail-grid-divider px-6 py-5">
                    <dt class="text-sm font-medium lms-text-neutral-muted">
                        生徒番号
                    </dt>

                    <dd class="mt-2 lms-text-neutral-strong">
                        {{ $student->student_no ?? '未設定' }}
                    </dd>
                </div>

                <div class="border-b lms-border-neutral-faint px-6 py-5">
                    <dt class="text-sm font-medium lms-text-neutral-muted">
                        生徒氏名
                    </dt>

                    <dd class="mt-2 lms-text-neutral-strong">
                        {{ $student->student_name }}
                    </dd>
                </div>

                <div class="border-b lms-border-neutral-faint lms-detail-grid-divider px-6 py-5">
                    <dt class="text-sm font-medium lms-text-neutral-muted">
                        学年
                    </dt>

                    <dd class="mt-2 lms-text-neutral-strong">
                        {{ $student->grade->label() }}
                    </dd>
                </div>

                <div class="border-b lms-border-neutral-faint px-6 py-5">
                    <dt class="text-sm font-medium lms-text-neutral-muted">
                        クラス
                    </dt>

                    <dd class="mt-2 lms-text-neutral-strong">
                        {{ $student->classGroup->class_name }}
                    </dd>
                </div>

                <div class="border-b lms-border-neutral-faint lms-detail-grid-divider px-6 py-5">
                    <dt class="text-sm font-medium lms-text-neutral-muted">
                        所属
                    </dt>

                    <dd class="mt-2 lms-text-neutral-strong">
                        {{ $student->affiliation ?? '未設定' }}
                    </dd>
                </div>

                <div class="border-b lms-border-neutral-faint px-6 py-5">
                    <dt class="text-sm font-medium lms-text-neutral-muted">
                        提携校
                    </dt>

                    <dd class="mt-2 lms-text-neutral-strong">
                        {{ $student->partner_school ?? '未設定' }}
                    </dd>
                </div>

                <div class="px-6 py-5 md:col-span-2">
                    <dt class="text-sm font-medium lms-text-neutral-muted">
                        在籍状態
                    </dt>

                    <dd class="mt-2 lms-text-neutral-strong">
                        {{ $student->status->label() }}
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
                    <h2 class="text-lg font-bold lms-text-neutral-strong">
                        成績概要
                    </h2>

                    <p class="mt-1 text-sm lms-text-neutral-subtle">
                        確定済みを優先して、直近10件の最終評価を表示します。
                    </p>
                </div>

                <a
                    href="{{ route('admin.evaluations.index', [
                        'student_id' => $student->id,
                    ]) }}"
                    class="text-sm font-semibold lms-link-primary"
                >
                    成績一覧を開く
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y lms-divide-neutral-subtle lms-table lms-table--balanced">
                    <thead class="lms-bg-neutral-subtle">
                        <tr>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted
                                    lms-table-col--date"
                            >年度・期間</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted">授業・科目</th>
                            <th
                                class="px-5 py-3 text-right text-xs font-semibold lms-text-neutral-muted
                                    lms-table-col--compact"
                            >総合点</th>
                            <th
                                class="px-5 py-3 text-center text-xs font-semibold lms-text-neutral-muted
                                    lms-table-col--compact"
                            >5段階</th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted
                                    lms-table-col--status"
                            >状態</th>
                            <th
                                class="px-5 py-3 text-right text-xs font-semibold lms-text-neutral-muted
                                    lms-table-col--action"
                            >操作</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y lms-divide-neutral-subtle">
                        @forelse ($recentEvaluations as $evaluation)
                            <tr>
                                <td class="whitespace-nowrap px-5 py-4 text-sm">
                                    {{ $evaluation->academic_year }}年度 / {{ $evaluation->term_name->label() }}
                                </td>

                                <td class="px-5 py-4 text-sm">
                                    <p class="font-semibold lms-text-neutral-strong">
                                        {{ $evaluation->course->course_name }}
                                    </p>

                                    <p class="mt-1 text-xs lms-text-neutral-muted">
                                        {{ $evaluation->course->subject->subject_name }}
                                    </p>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-semibold">
                                    {{ number_format((float) $evaluation->total_score, 2) }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-center text-sm font-semibold">
                                    {{ $evaluation->grade_level ?? '-' }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm">
                                    <span
                                        @class([
                                            'inline-flex whitespace-nowrap rounded-full',
                                            'px-3 py-1 text-xs font-semibold',
                                            'lms-bg-success-muted lms-text-success-strong' => $evaluation->status ===
                                            \App\Enums\EvaluationStatus::Confirmed,
                                            'lms-bg-warning-muted lms-text-warning-strong' => $evaluation->status ===
                                            \App\Enums\EvaluationStatus::Draft,
                                        ])
                                    >
                                        {{ $evaluation->status->label() }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm">
                                    <a
                                        href="{{ route('admin.evaluations.edit', $evaluation) }}"
                                        class="font-semibold lms-link-primary"
                                    >
                                        詳細・修正
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="6"
                                    class="px-6 py-10 text-center text-sm lms-text-neutral-muted"
                                >
                                    登録済みの最終評価はありません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="overflow-hidden lms-panel">
            <div
                class="flex flex-col gap-3 border-b lms-border-neutral-subtle px-6 py-5 sm:flex-row sm:items-center
                    sm:justify-between"
            >
                <div>
                    <h2 class="text-lg font-bold lms-text-neutral-strong">
                        面談履歴
                    </h2>

                    <p class="mt-1 text-sm lms-text-neutral-subtle">
                        直近10件の面談記録を表示します。
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a
                        href="{{ route('admin.interviews.create', ['student_id' => $student->id]) }}"
                        class="text-sm font-semibold lms-link-primary"
                    >
                        面談記録を登録
                    </a>

                    <a
                        href="{{ route('admin.interviews.index', ['student_id' => $student->id]) }}"
                        class="text-sm font-semibold lms-link-primary"
                    >
                        面談履歴を開く
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y lms-divide-neutral-subtle lms-table lms-table--balanced">
                    <thead class="lms-bg-neutral-subtle">
                        <tr>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted
                                    lms-table-col--date"
                            >面談日・種別</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted">担当教員</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted">次回対応</th>
                            <th
                                class="px-5 py-3 text-right text-xs font-semibold lms-text-neutral-muted
                                    lms-table-col--action"
                            >操作</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y lms-divide-neutral-subtle">
                        @forelse ($recentInterviews as $interviewRecord)
                            <tr>
                                <td class="whitespace-nowrap px-5 py-4 text-sm">
                                    <p class="font-semibold lms-text-neutral-strong">
                                        {{ $interviewRecord->interview_date->format('Y/m/d') }}
                                    </p>

                                    <p class="mt-1 text-xs lms-text-neutral-muted">
                                        {{ $interviewRecord->interview_type ?? '種別未設定' }}
                                    </p>
                                </td>

                                <td class="px-5 py-4 text-sm lms-text-neutral-secondary">
                                    {{ $interviewRecord->teacher?->user?->name ?? '未設定' }}
                                </td>

                                <td class="max-w-md px-5 py-4 text-sm lms-text-neutral-secondary">
                                    {{ $interviewRecord->next_action !== null
                                        ? \Illuminate\Support\Str::limit($interviewRecord->next_action, 100)
                                        : '-' }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm">
                                    <a
                                        href="{{ route('admin.interviews.edit', $interviewRecord) }}"
                                        class="font-semibold lms-link-primary"
                                    >
                                        詳細・編集
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-sm lms-text-neutral-muted">
                                    登録済みの面談記録はありません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="overflow-hidden lms-panel">
            <div class="border-b lms-border-neutral-subtle px-6 py-5">
                <h2 class="text-lg font-bold lms-text-neutral-strong">
                    ログインアカウント
                </h2>

                <p class="mt-1 text-sm lms-text-neutral-subtle">
                    LMSへログインするためのアカウント情報です。
                </p>
            </div>

            <dl class="grid md:grid-cols-2">
                <div class="border-b lms-border-neutral-faint lms-detail-grid-divider px-6 py-5">
                    <dt class="text-sm font-medium lms-text-neutral-muted">
                        アカウント氏名
                    </dt>

                    <dd class="mt-2 lms-text-neutral-strong">
                        {{ $student->user->name }}
                    </dd>
                </div>

                <div class="border-b lms-border-neutral-faint px-6 py-5">
                    <dt class="text-sm font-medium lms-text-neutral-muted">
                        メールアドレス
                    </dt>

                    <dd class="mt-2 break-all lms-text-neutral-strong">
                        {{ $student->user->email }}
                    </dd>
                </div>

                <div class="lms-detail-grid-divider px-6 py-5">
                    <dt class="text-sm font-medium lms-text-neutral-muted">
                        ロール
                    </dt>

                    <dd class="mt-2 lms-text-neutral-strong">
                        {{ $student->user->role->label() }}
                    </dd>
                </div>

                <div class="px-6 py-5">
                    <dt class="text-sm font-medium lms-text-neutral-muted">
                        利用状態
                    </dt>

                    <dd class="mt-2">
                        <span
                            @class([
                                'inline-flex whitespace-nowrap rounded-full',
                                'px-3 py-1 text-sm font-semibold',
                                'lms-bg-success-muted lms-text-success' => $student->user->status ===
                                \App\Enums\UserStatus::Active,
                                'lms-bg-neutral-disabled lms-text-neutral-secondary' => $student->user->status ===
                                \App\Enums\UserStatus::Suspended,
                            ])
                        >
                            {{ $student->user->status->label() }}
                        </span>
                    </dd>
                </div>
            </dl>
        </section>

        <section class="p-6 lms-panel">
            <h2 class="text-lg font-bold lms-text-neutral-strong">
                システム情報
            </h2>

            <dl class="mt-4 grid gap-5 md:grid-cols-3">
                <div>
                    <dt class="text-sm font-medium lms-text-neutral-muted">
                        生徒ID
                    </dt>

                    <dd class="mt-1 lms-text-neutral-strong">
                        {{ $student->id }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm font-medium lms-text-neutral-muted">
                        登録日時
                    </dt>

                    <dd class="mt-1 lms-text-neutral-strong">
                        {{ $student->created_at?->format('Y/m/d H:i') }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm font-medium lms-text-neutral-muted">
                        更新日時
                    </dt>

                    <dd class="mt-1 lms-text-neutral-strong">
                        {{ $student->updated_at?->format('Y/m/d H:i') }}
                    </dd>
                </div>
            </dl>
        </section>
    </div>
@endsection
