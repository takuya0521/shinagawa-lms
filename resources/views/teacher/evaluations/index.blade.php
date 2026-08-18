@extends('layouts.app')

@section('page-class', 'page-pattern-list page-teacher-evaluations-index')

@section('title', '科目別評価一覧')
@section('header-title', '科目別評価一覧')

@section('content')
    <div class="space-y-6">
        <section class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
            <a
                href="{{
                    route('teacher.evaluations.entry', ['academic_year' => $academicYear, 'term_name' => $term->value])
                }}"
                class="inline-flex items-center justify-center rounded-lg px-5 py-3 font-semibold lms-button-primary"
            >最終評価を入力</a>
        </section>

        <section class="p-6 lms-panel">
            <form method="GET" action="{{ route('teacher.evaluations.index') }}" class="grid gap-4 lg:grid-cols-5">
                <div>
                    <label for="academic_year" class="block text-sm font-semibold lms-text-neutral-secondary">年度</label>
                    <select
                        id="academic_year"
                        name="academic_year"
                        class="mt-2 w-full rounded-lg border px-3 py-2 lms-form-control"
                    >
                        @foreach ($academicYears as $year)
                            <option
                                value="{{ $year }}"
                                @selected((int) $academicYear === (int) $year)
                            >{{ $year }}年度</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="term_name" class="block text-sm font-semibold lms-text-neutral-secondary">期間</label>
                    <select
                        id="term_name"
                        name="term_name"
                        class="mt-2 w-full rounded-lg border px-3 py-2 lms-form-control"
                    >
                        @foreach ($terms as $termOption)
                            <option
                                value="{{ $termOption->value }}"
                                @selected($term === $termOption)
                            >{{ $termOption->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="course_id" class="block text-sm font-semibold lms-text-neutral-secondary">担当授業</label>
                    <select
                        id="course_id"
                        name="course_id"
                        class="mt-2 w-full rounded-lg border px-3 py-2 lms-form-control"
                    >
                        <option value="">すべて</option>
                        @foreach ($courses as $course)
                            <option
                                value="{{ $course->id }}"
                                @selected($selectedCourseId === $course->id)
                            >{{ $course->academic_year }} / {{ $course->course_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="status" class="block text-sm font-semibold lms-text-neutral-secondary">状態</label>
                    <select id="status" name="status" class="mt-2 w-full rounded-lg border px-3 py-2 lms-form-control">
                        <option value="">すべて</option>
                        @foreach ($statuses as $status)
                            <option
                                value="{{ $status->value }}"
                                @selected($selectedStatus === $status)
                            >{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-3">
                    <label
                        class="flex items-center gap-2 rounded-lg border lms-border-neutral-default px-3 py-2.5 text-sm
                            font-semibold lms-text-neutral-secondary"
                    >
                        <input type="checkbox" name="missing_only" value="1" @checked($missingOnly)>
                        未入力のみ
                    </label>
                    <button type="submit" class="rounded-lg px-5 py-2.5 font-semibold lms-button-primary">検索</button>
                </div>
            </form>
        </section>

        <section class="overflow-hidden lms-panel">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y lms-divide-neutral-subtle lms-table lms-table--balanced">
                    <thead class="lms-bg-neutral-subtle">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted">授業</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted">生徒</th>
                            <th
                                class="px-5 py-3 text-right text-xs font-semibold lms-text-neutral-muted
                                    lms-table-col--compact"
                            >提出物</th>
                            <th
                                class="px-5 py-3 text-right text-xs font-semibold lms-text-neutral-muted
                                    lms-table-col--compact"
                            >出欠</th>
                            <th
                                class="px-5 py-3 text-right text-xs font-semibold lms-text-neutral-muted
                                    lms-table-col--compact"
                            >授業態度</th>
                            <th
                                class="px-5 py-3 text-right text-xs font-semibold lms-text-neutral-muted
                                    lms-table-col--compact"
                            >総合</th>
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
                        @forelse ($evaluations as $evaluation)
                            <tr>
                                <td class="px-5 py-4 text-sm">
                                    <p class="font-semibold lms-text-neutral-strong">{{ $evaluation->course_name }}</p>
                                    <p
                                        class="mt-1 text-xs lms-text-neutral-muted"
                                    >{{ $evaluation->subject_name }} / {{ $evaluation->class_name }}</p>
                                </td>
                                <td class="px-5 py-4 text-sm">
                                    <p class="font-semibold lms-text-neutral-strong">{{ $evaluation->student_name }}</p>
                                    <p
                                        class="mt-1 text-xs lms-text-neutral-muted"
                                    >{{ $evaluation->student_no ?? '番号未設定' }}</p>
                                </td>
                                @if ($evaluation->evaluation_id !== null)
                                    <td
                                        class="whitespace-nowrap px-5 py-4 text-right text-sm"
                                    >{{ number_format((float) $evaluation->submission_score, 2) }}</td>
                                    <td
                                        class="whitespace-nowrap px-5 py-4 text-right text-sm"
                                    >{{ number_format((float) $evaluation->attendance_score, 2) }}</td>
                                    <td
                                        class="whitespace-nowrap px-5 py-4 text-right text-sm"
                                    >{{ number_format((float) $evaluation->attitude_score, 2) }}</td>
                                    <td
                                        class="whitespace-nowrap px-5 py-4 text-right text-sm font-semibold"
                                    >{{ number_format((float) $evaluation->total_score, 2) }}</td>
                                    <td
                                        class="whitespace-nowrap px-5 py-4 text-center text-sm font-semibold"
                                    >{{ $evaluation->grade_level ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm">
                                        <span
                                            class="inline-flex whitespace-nowrap rounded-full px-3 py-1
                                                text-xs font-semibold {{
                                                $evaluation->evaluation_status === 'confirmed' ? 'lms-bg-success-muted
                                                lms-text-success-strong' : 'lms-bg-warning-muted
                                                lms-text-warning-strong' }}"
                                        >
                                            {{
                                            \App\Enums\EvaluationStatus::from($evaluation->evaluation_status)->label()
                                            }}
                                        </span>
                                    </td>
                                @else
                                    <td
                                        colspan="5"
                                        class="px-5 py-4 text-center text-sm lms-text-neutral-disabled"
                                    >未入力</td>
                                    <td
                                        class="px-5 py-4 text-sm"
                                    >
                                    <span
                                        class="inline-flex whitespace-nowrap rounded-full lms-bg-neutral-muted px-3 py-1
                                            text-xs font-semibold
                                            lms-text-neutral-subtle"
                                    >未入力</span>
                                    </td>
                                @endif
                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm">
                                    <a
                                        href="{{
                                            route('teacher.evaluations.entry', ['course_id' => $evaluation->course_id,
                                            'academic_year' => $evaluation->academic_year, 'term_name' => $term->value])
                                        }}"
                                        class="font-semibold lms-link-primary"
                                    >入力・編集</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-sm lms-text-neutral-muted">
                                    条件に一致する評価対象はありません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($evaluations->hasPages())
                <div class="border-t lms-border-neutral-subtle px-6 py-4">{{ $evaluations->links() }}</div>
            @endif
        </section>
    </div>
@endsection
