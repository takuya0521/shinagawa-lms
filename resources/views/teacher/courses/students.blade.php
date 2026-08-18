@extends('layouts.app')

@section('page-class', 'page-pattern-list page-teacher-courses-students')

@section('title', '授業別生徒一覧')
@section('header-title', '授業別生徒一覧')

@section('content')
    <section class="space-y-6">
        <div class="p-6 lms-panel">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-sm font-semibold lms-text-neutral-muted">
                        {{ $course->academic_year }}年度 / {{ $course->grade->label() }} / {{
                        $course->classGroup->class_name }}
                    </p>
                    <h1 class="mt-1 text-2xl font-bold lms-text-neutral-strong">
                        {{ $course->course_name }}
                    </h1>
                    <p class="mt-1 text-sm lms-text-neutral-subtle">
                        {{ $course->subject->subject_name }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a
                        href="{{
                            route('teacher.evaluations.entry', ['course_id' => $course->id, 'academic_year' =>
                            $course->academic_year, 'term_name' => 'annual'])
                        }}"
                        class="rounded-lg px-4 py-2 text-sm font-semibold lms-button-primary"
                    >
                        最終評価を入力
                    </a>
                    <a
                        href="{{ route('teacher.courses.index') }}"
                        class="rounded-lg border lms-border-neutral-default px-4 py-2 text-sm font-semibold
                            lms-hover-bg-neutral-subtle"
                    >
                        担当授業一覧へ
                    </a>
                </div>
            </div>
        </div>

        <div class="p-6 lms-panel">
            <form
                method="GET"
                action="{{ route('teacher.courses.students.index', $course) }}"
                class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_220px_auto_auto]"
            >
                <div>
                    <label for="keyword" class="block text-sm font-semibold lms-text-neutral-secondary">キーワード</label>
                    <input
                        id="keyword"
                        name="keyword"
                        value="{{ $keyword }}"
                        placeholder="生徒番号・氏名"
                        class="mt-2 block w-full rounded-lg border px-3 py-2 lms-form-control"
                    >
                </div>
                <div>
                    <label for="status" class="block text-sm font-semibold lms-text-neutral-secondary">状態</label>
                    <select
                        id="status"
                        name="status"
                        class="mt-2 block w-full rounded-lg border px-3 py-2 lms-form-control"
                    >
                        <option value="">在籍のみ</option>
                        @foreach ($statuses as $status)
                            <option
                                value="{{ $status->value }}"
                                @selected($selectedStatus === $status)
                            >{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button
                        type="submit"
                        class="w-full rounded-lg px-5 py-2.5 font-semibold lms-button-primary"
                    >検索</button>
                </div>
                <div class="flex items-end">
                    <a
                        href="{{ route('teacher.courses.students.index', $course) }}"
                        class="w-full rounded-lg border lms-border-neutral-default px-5 py-2.5 text-center
                            font-semibold"
                    >クリア</a>
                </div>
            </form>
        </div>

        <div class="overflow-hidden lms-panel">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y lms-divide-neutral-subtle lms-table lms-table--balanced">
                    <thead class="lms-bg-neutral-subtle">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-semibold lms-text-neutral-muted
                                    lms-table-col--medium"
                            >生徒番号</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold lms-text-neutral-muted">氏名</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-semibold lms-text-neutral-muted
                                    lms-table-col--status"
                            >状態</th>
                            <th
                                class="px-6 py-3 text-right text-xs font-semibold lms-text-neutral-muted
                                    lms-table-col--action"
                            >操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y lms-divide-neutral-subtle">
                        @forelse ($students as $student)
                            <tr>
                                <td
                                    class="px-6 py-4 text-sm font-semibold lms-text-neutral-strong"
                                >{{ $student->student_no ?? '-' }}</td>
                                <td
                                    class="px-6 py-4 text-sm lms-text-neutral-secondary"
                                >{{ $student->student_name }}</td>
                                <td
                                    class="px-6 py-4 text-sm lms-text-neutral-secondary"
                                >{{ $student->status->label() }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <div class="flex justify-end gap-3">
                                        <a
                                            href="{{
                                                route('teacher.interviews.create', ['student_id' => $student->id])
                                            }}"
                                            class="font-semibold lms-link-primary"
                                        >
                                            面談記録
                                        </a>

                                        <a
                                            href="{{
                                                route('teacher.interviews.index', ['student_id' => $student->id])
                                            }}"
                                            class="font-semibold lms-text-neutral-subtle lms-hover-text-neutral-strong"
                                        >
                                            面談履歴
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-sm lms-text-neutral-muted">
                                    対象生徒はいません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($students->hasPages())
                <div class="border-t lms-border-neutral-subtle px-6 py-4">{{ $students->links() }}</div>
            @endif
        </div>
    </section>
@endsection
