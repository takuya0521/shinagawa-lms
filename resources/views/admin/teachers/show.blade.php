@extends('layouts.app')

@section('page-style', 'resources/css/pages/admin/teachers/show.css')
@section('page-class', 'page-pattern-detail page-admin-teachers-show')

@section('title', '教員詳細')
@section('header-title', '教員詳細')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-slate-500">
                    TEACHER DETAIL
                </p>

                <h1 class="mt-1 text-2xl font-bold text-slate-900">
                    教員詳細
                </h1>

                <p class="mt-2 text-sm text-slate-600">
                    教員情報とログインアカウントを確認します。
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a
                    href="{{ route('admin.course-teacher-assignments.index', [
                        'keyword' => $teacher->user->name,
                        'academic_year' => '',
                    ]) }}"
                    class="rounded-lg border border-slate-300 bg-white px-5 py-3 font-semibold text-slate-700 hover:bg-slate-50"
                >
                    担当授業を設定
                </a>

                <a
                    href="{{ route('admin.teachers.edit', $teacher) }}"
                    class="rounded-lg bg-slate-900 px-5 py-3 font-semibold text-white hover:bg-slate-700"
                >
                    編集する
                </a>

                <a
                    href="{{ route('admin.teachers.index') }}"
                    class="rounded-lg border border-slate-300 px-5 py-3 font-semibold hover:bg-slate-50"
                >
                    一覧へ戻る
                </a>
            </div>
        </header>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-xl font-bold text-slate-900">
                        {{ $teacher->user->name }}
                    </h2>

                    <span
                        @class([
                            'inline-flex rounded-full px-4 py-2 text-sm font-semibold',
                            'bg-emerald-100 text-emerald-700' => $teacher->status === \App\Enums\MasterStatus::Active,
                            'bg-slate-200 text-slate-700' => $teacher->status === \App\Enums\MasterStatus::Inactive,
                        ])
                    >
                        {{ $teacher->status->label() }}
                    </span>
                </div>
            </div>

            <dl>
                <div class="border-b border-slate-100 px-6 py-5">
                    <dt class="text-sm font-medium text-slate-500">
                        担当科目メモ
                    </dt>

                    <dd class="mt-2 whitespace-pre-wrap text-slate-900">{{ $teacher->subject_notes ?? '未設定' }}</dd>
                </div>

                <div class="px-6 py-5">
                    <dt class="text-sm font-medium text-slate-500">
                        教員状態
                    </dt>

                    <dd class="mt-2 text-slate-900">
                        {{ $teacher->status->label() }}
                    </dd>
                </div>
            </dl>
        </section>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-5">
                <h2 class="text-lg font-bold text-slate-900">
                    ログインアカウント
                </h2>
            </div>

            <dl class="grid md:grid-cols-2">
                <div class="border-b border-slate-100 px-6 py-5 md:border-r">
                    <dt class="text-sm font-medium text-slate-500">
                        氏名
                    </dt>

                    <dd class="mt-2 text-slate-900">
                        {{ $teacher->user->name }}
                    </dd>
                </div>

                <div class="border-b border-slate-100 px-6 py-5">
                    <dt class="text-sm font-medium text-slate-500">
                        メールアドレス
                    </dt>

                    <dd class="mt-2 break-all text-slate-900">
                        {{ $teacher->user->email }}
                    </dd>
                </div>

                <div class="px-6 py-5 md:border-r">
                    <dt class="text-sm font-medium text-slate-500">
                        ロール
                    </dt>

                    <dd class="mt-2 text-slate-900">
                        {{ $teacher->user->role->label() }}
                    </dd>
                </div>

                <div class="px-6 py-5">
                    <dt class="text-sm font-medium text-slate-500">
                        アカウント利用状態
                    </dt>

                    <dd class="mt-2">
                        <span
                            @class([
                                'inline-flex rounded-full px-3 py-1 text-sm font-semibold',
                                'bg-emerald-100 text-emerald-700' => $teacher->user->status === \App\Enums\UserStatus::Active,
                                'bg-slate-200 text-slate-700' => $teacher->user->status === \App\Enums\UserStatus::Suspended,
                            ])
                        >
                            {{ $teacher->user->status->label() }}
                        </span>
                    </dd>
                </div>
            </dl>
        </section>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">担当授業・対象生徒</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        現在この教員へ設定されている授業を最大20件表示します。
                    </p>
                </div>

                <a
                    href="{{ route('admin.course-teacher-assignments.index', [
                        'keyword' => $teacher->user->name,
                        'academic_year' => '',
                    ]) }}"
                    class="text-sm font-semibold text-blue-700 hover:text-blue-900"
                >
                    担当教員設定を開く
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">年度・対象</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">授業・科目</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">時間割</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">対象生徒</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">外部リンク</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($assignedCourses as $course)
                            @php
                                $targetStudents = $targetStudentsByCourse[$course->id] ?? collect();
                            @endphp

                            <tr class="align-top hover:bg-slate-50">
                                <td class="whitespace-nowrap px-5 py-4 text-sm">
                                    <p class="font-semibold text-slate-900">{{ $course->academic_year }}年度</p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $course->grade->label() }} / {{ $course->classGroup->class_code }}・{{ $course->classGroup->class_name }}
                                    </p>
                                </td>

                                <td class="px-5 py-4 text-sm">
                                    <a
                                        href="{{ route('admin.courses.edit', $course) }}"
                                        class="font-semibold text-blue-700 hover:text-blue-900"
                                    >
                                        {{ $course->course_name }}
                                    </a>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $course->subject->subject_code }} / {{ $course->subject->subject_name }}
                                    </p>
                                </td>

                                <td class="px-5 py-4 text-sm text-slate-700">
                                    @forelse ($course->timetableSlots as $slot)
                                        <span class="mr-1 inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                            {{ $slot->day_of_week->shortLabel() }}曜 {{ $slot->period_no }}時限
                                        </span>
                                    @empty
                                        <span class="text-slate-400">未設定</span>
                                    @endforelse
                                </td>

                                <td class="min-w-64 px-5 py-4 text-sm">
                                    <p class="font-semibold text-slate-900">{{ $targetStudents->count() }}名</p>

                                    @if ($targetStudents->isNotEmpty())
                                        <p class="mt-1 text-xs leading-5 text-slate-500">
                                            {{ $targetStudents->take(5)->pluck('student_name')->join('、') }}
                                            @if ($targetStudents->count() > 5)
                                                ほか{{ $targetStudents->count() - 5 }}名
                                            @endif
                                        </p>
                                    @else
                                        <p class="mt-1 text-xs text-slate-400">対象となる在籍生徒はいません。</p>
                                    @endif
                                </td>

                                <td class="px-5 py-4 text-sm">
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
                                        <span class="text-slate-400">未設定</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500">
                                    担当授業は設定されていません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-2xl bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900">
                システム情報
            </h2>

            <dl class="mt-4 grid gap-5 md:grid-cols-3">
                <div>
                    <dt class="text-sm font-medium text-slate-500">
                        教員ID
                    </dt>

                    <dd class="mt-1 text-slate-900">
                        {{ $teacher->id }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-slate-500">
                        登録日時
                    </dt>

                    <dd class="mt-1 text-slate-900">
                        {{ $teacher->created_at?->format('Y/m/d H:i') }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-slate-500">
                        更新日時
                    </dt>

                    <dd class="mt-1 text-slate-900">
                        {{ $teacher->updated_at?->format('Y/m/d H:i') }}
                    </dd>
                </div>
            </dl>
        </section>
    </div>
@endsection
