@extends('layouts.app')

@section('page-style', 'resources/css/pages/admin/students/show.css')
@section('page-class', 'page-pattern-detail page-admin-students-show')

@section('title', '生徒詳細')
@section('header-title', '生徒詳細')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-slate-500">
                    STUDENT DETAIL
                </p>

                <h1 class="mt-1 text-2xl font-bold text-slate-900">
                    生徒詳細
                </h1>

                <p class="mt-2 text-sm text-slate-600">
                    生徒の基本情報とログインアカウントを確認します。
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a
                    href="{{ route('admin.students.attendance.show', $student) }}"
                    class="rounded-lg border border-blue-300 px-5 py-3 font-semibold text-blue-700 hover:bg-blue-50"
                >
                    出欠詳細
                </a>

                <a
                    href="{{ route('admin.evaluations.index', [
                        'student_id' => $student->id,
                    ]) }}"
                    class="rounded-lg border border-violet-300 px-5 py-3 font-semibold text-violet-700 hover:bg-violet-50"
                >
                    成績詳細
                </a>


                <a
                    href="{{ route('admin.interviews.index', [
                        'student_id' => $student->id,
                    ]) }}"
                    class="rounded-lg border border-amber-300 px-5 py-3 font-semibold text-amber-700 hover:bg-amber-50"
                >
                    面談履歴
                </a>

                <a
                    href="{{ route(
                        'admin.students.edit',
                        $student,
                    ) }}"
                    class="rounded-lg bg-slate-900 px-5 py-3 font-semibold text-white hover:bg-slate-700"
                >
                    編集する
                </a>

                <a
                    href="{{ route('admin.students.index') }}"
                    class="rounded-lg border border-slate-300 px-5 py-3 font-semibold hover:bg-slate-50"
                >
                    一覧へ戻る
                </a>
            </div>
        </header>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm text-slate-500">
                            {{ $student->student_no ?? '生徒番号未設定' }}
                        </p>

                        <h2 class="mt-1 text-xl font-bold text-slate-900">
                            {{ $student->student_name }}
                        </h2>
                    </div>

                    <span
                        @class([
                            'inline-flex rounded-full px-4 py-2 text-sm font-semibold',
                            'bg-emerald-100 text-emerald-700' => $student->status === \App\Enums\StudentStatus::Active,
                            'bg-amber-100 text-amber-700' => $student->status === \App\Enums\StudentStatus::Suspended,
                            'bg-blue-100 text-blue-700' => $student->status === \App\Enums\StudentStatus::Graduated,
                            'bg-rose-100 text-rose-700' => $student->status === \App\Enums\StudentStatus::Withdrawn,
                        ])
                    >
                        {{ $student->status->label() }}
                    </span>
                </div>
            </div>

            <dl class="grid md:grid-cols-2">
                <div class="border-b border-slate-100 px-6 py-5 md:border-r">
                    <dt class="text-sm font-medium text-slate-500">
                        生徒番号
                    </dt>

                    <dd class="mt-2 text-slate-900">
                        {{ $student->student_no ?? '未設定' }}
                    </dd>
                </div>

                <div class="border-b border-slate-100 px-6 py-5">
                    <dt class="text-sm font-medium text-slate-500">
                        生徒氏名
                    </dt>

                    <dd class="mt-2 text-slate-900">
                        {{ $student->student_name }}
                    </dd>
                </div>

                <div class="border-b border-slate-100 px-6 py-5 md:border-r">
                    <dt class="text-sm font-medium text-slate-500">
                        学年
                    </dt>

                    <dd class="mt-2 text-slate-900">
                        {{ $student->grade->label() }}
                    </dd>
                </div>

                <div class="border-b border-slate-100 px-6 py-5">
                    <dt class="text-sm font-medium text-slate-500">
                        クラス
                    </dt>

                    <dd class="mt-2 text-slate-900">
                        {{ $student->classGroup->class_name }}
                    </dd>
                </div>

                <div class="border-b border-slate-100 px-6 py-5 md:border-r">
                    <dt class="text-sm font-medium text-slate-500">
                        所属
                    </dt>

                    <dd class="mt-2 text-slate-900">
                        {{ $student->affiliation ?? '未設定' }}
                    </dd>
                </div>

                <div class="border-b border-slate-100 px-6 py-5">
                    <dt class="text-sm font-medium text-slate-500">
                        提携校
                    </dt>

                    <dd class="mt-2 text-slate-900">
                        {{ $student->partner_school ?? '未設定' }}
                    </dd>
                </div>

                <div class="px-6 py-5 md:col-span-2">
                    <dt class="text-sm font-medium text-slate-500">
                        在籍状態
                    </dt>

                    <dd class="mt-2 text-slate-900">
                        {{ $student->status->label() }}
                    </dd>
                </div>
            </dl>
        </section>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">
                        成績概要
                    </h2>

                    <p class="mt-1 text-sm text-slate-600">
                        確定済みを優先して、直近10件の最終評価を表示します。
                    </p>
                </div>

                <a
                    href="{{ route('admin.evaluations.index', [
                        'student_id' => $student->id,
                    ]) }}"
                    class="text-sm font-semibold text-blue-700 hover:text-blue-900"
                >
                    成績一覧を開く
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">年度・期間</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">授業・科目</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-slate-500">総合点</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold text-slate-500">5段階</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">状態</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-slate-500">操作</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200">
                        @forelse ($recentEvaluations as $evaluation)
                            <tr>
                                <td class="whitespace-nowrap px-5 py-4 text-sm">
                                    {{ $evaluation->academic_year }}年度 / {{ $evaluation->term_name->label() }}
                                </td>

                                <td class="px-5 py-4 text-sm">
                                    <p class="font-semibold text-slate-900">
                                        {{ $evaluation->course->course_name }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
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
                                            'inline-flex rounded-full px-3 py-1 text-xs font-semibold',
                                            'bg-emerald-100 text-emerald-800' => $evaluation->status === \App\Enums\EvaluationStatus::Confirmed,
                                            'bg-amber-100 text-amber-800' => $evaluation->status === \App\Enums\EvaluationStatus::Draft,
                                        ])
                                    >
                                        {{ $evaluation->status->label() }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm">
                                    <a
                                        href="{{ route('admin.evaluations.edit', $evaluation) }}"
                                        class="font-semibold text-blue-700 hover:text-blue-900"
                                    >
                                        詳細・修正
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="6"
                                    class="px-6 py-10 text-center text-sm text-slate-500"
                                >
                                    登録済みの最終評価はありません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">
                        面談履歴
                    </h2>

                    <p class="mt-1 text-sm text-slate-600">
                        直近10件の面談記録を表示します。
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a
                        href="{{ route('admin.interviews.create', ['student_id' => $student->id]) }}"
                        class="text-sm font-semibold text-blue-700 hover:text-blue-900"
                    >
                        面談記録を登録
                    </a>

                    <a
                        href="{{ route('admin.interviews.index', ['student_id' => $student->id]) }}"
                        class="text-sm font-semibold text-blue-700 hover:text-blue-900"
                    >
                        面談履歴を開く
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">面談日・種別</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">担当教員</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">次回対応</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-slate-500">操作</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200">
                        @forelse ($recentInterviews as $interviewRecord)
                            <tr>
                                <td class="whitespace-nowrap px-5 py-4 text-sm">
                                    <p class="font-semibold text-slate-900">
                                        {{ $interviewRecord->interview_date->format('Y/m/d') }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $interviewRecord->interview_type ?? '種別未設定' }}
                                    </p>
                                </td>

                                <td class="px-5 py-4 text-sm text-slate-700">
                                    {{ $interviewRecord->teacher?->user?->name ?? '未設定' }}
                                </td>

                                <td class="max-w-md px-5 py-4 text-sm text-slate-700">
                                    {{ $interviewRecord->next_action !== null
                                        ? \Illuminate\Support\Str::limit($interviewRecord->next_action, 100)
                                        : '-' }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm">
                                    <a
                                        href="{{ route('admin.interviews.edit', $interviewRecord) }}"
                                        class="font-semibold text-blue-700 hover:text-blue-900"
                                    >
                                        詳細・編集
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-sm text-slate-500">
                                    登録済みの面談記録はありません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-5">
                <h2 class="text-lg font-bold text-slate-900">
                    ログインアカウント
                </h2>

                <p class="mt-1 text-sm text-slate-600">
                    LMSへログインするためのアカウント情報です。
                </p>
            </div>

            <dl class="grid md:grid-cols-2">
                <div class="border-b border-slate-100 px-6 py-5 md:border-r">
                    <dt class="text-sm font-medium text-slate-500">
                        アカウント氏名
                    </dt>

                    <dd class="mt-2 text-slate-900">
                        {{ $student->user->name }}
                    </dd>
                </div>

                <div class="border-b border-slate-100 px-6 py-5">
                    <dt class="text-sm font-medium text-slate-500">
                        メールアドレス
                    </dt>

                    <dd class="mt-2 break-all text-slate-900">
                        {{ $student->user->email }}
                    </dd>
                </div>

                <div class="px-6 py-5 md:border-r">
                    <dt class="text-sm font-medium text-slate-500">
                        ロール
                    </dt>

                    <dd class="mt-2 text-slate-900">
                        {{ $student->user->role->label() }}
                    </dd>
                </div>

                <div class="px-6 py-5">
                    <dt class="text-sm font-medium text-slate-500">
                        利用状態
                    </dt>

                    <dd class="mt-2">
                        <span
                            @class([
                                'inline-flex rounded-full px-3 py-1 text-sm font-semibold',
                                'bg-emerald-100 text-emerald-700' => $student->user->status === \App\Enums\UserStatus::Active,
                                'bg-slate-200 text-slate-700' => $student->user->status === \App\Enums\UserStatus::Suspended,
                            ])
                        >
                            {{ $student->user->status->label() }}
                        </span>
                    </dd>
                </div>
            </dl>
        </section>

        <section class="rounded-2xl bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900">
                システム情報
            </h2>

            <dl class="mt-4 grid gap-5 md:grid-cols-3">
                <div>
                    <dt class="text-sm font-medium text-slate-500">
                        生徒ID
                    </dt>

                    <dd class="mt-1 text-slate-900">
                        {{ $student->id }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-slate-500">
                        登録日時
                    </dt>

                    <dd class="mt-1 text-slate-900">
                        {{ $student->created_at?->format('Y/m/d H:i') }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-slate-500">
                        更新日時
                    </dt>

                    <dd class="mt-1 text-slate-900">
                        {{ $student->updated_at?->format('Y/m/d H:i') }}
                    </dd>
                </div>
            </dl>
        </section>
    </div>
@endsection
