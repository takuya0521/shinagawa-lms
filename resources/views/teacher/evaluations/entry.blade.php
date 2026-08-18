@extends('layouts.app')

@section('page-class', 'page-pattern-form page-teacher-evaluations-entry')

@section('title', '最終評価入力')
@section('header-title', '最終評価入力')

@section('content')
    <div class="space-y-6">

        @if (! $gradingConfigured)
            <div
                class="rounded-xl border lms-border-warning lms-bg-warning-soft px-5 py-4 text-sm lms-text-warning-deep"
            >
                現在の評価設定では確定できません。下書き保存のみ利用できます。
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border lms-border-danger lms-bg-danger-soft px-5 py-4 text-sm lms-text-danger-deep">
                <p class="font-semibold">入力内容を確認してください。</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="p-6 lms-panel">
            <form
                method="GET"
                action="{{ route('teacher.evaluations.entry') }}"
                class="grid gap-4 lg:grid-cols-[180px_180px_minmax(0,1fr)_auto]"
            >
                <div>
                    <label for="academic_year" class="block text-sm font-semibold lms-text-neutral-secondary">年度</label>
                    <input
                        id="academic_year"
                        name="academic_year"
                        type="number"
                        min="2000"
                        max="2100"
                        value="{{ $academicYear }}"
                        class="mt-2 w-full rounded-lg border px-3 py-2 lms-form-control"
                    >
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
                        required
                    >
                        <option value="">選択してください</option>
                        @foreach ($courses as $courseOption)
                            <option
                                value="{{ $courseOption->id }}"
                                @selected($course?->id === $courseOption->id)
                            >{{ $courseOption->academic_year }}年度 / {{ $courseOption->course_name }} / {{
                            $courseOption->classGroup->class_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button
                        type="submit"
                        class="w-full rounded-lg px-5 py-2.5 font-semibold lms-button-primary"
                    >対象生徒を表示</button>
                </div>
            </form>
        </section>

        @if ($course !== null)
            <form
                method="POST"
                action="{{ route('teacher.evaluations.save') }}"
                class="space-y-6"
                data-evaluation-form
                data-preview-config='@json($evaluationPreviewConfig)'
            >
                @csrf
                @method('PUT')
                <input class="lms-form-control" type="hidden" name="course_id" value="{{ $course->id }}">
                <input class="lms-form-control" type="hidden" name="academic_year" value="{{ $academicYear }}">
                <input class="lms-form-control" type="hidden" name="term_name" value="{{ $term->value }}">

                <section class="p-6 lms-panel">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="text-xl font-bold lms-text-neutral-strong">{{ $course->course_name }}</h2>
                            <p
                                class="mt-1 text-sm lms-text-neutral-subtle"
                            >{{ $course->subject->subject_name }} / {{ $course->grade->label() }} / {{
                            $course->classGroup->class_name }}</p>
                        </div>
                        @if ($course->google_classroom_url !== null)
                            <a
                                href="{{ $course->google_classroom_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="font-semibold lms-link-primary"
                            >Google Classroomを開く</a>
                        @endif
                    </div>
                </section>

                <section class="overflow-hidden lms-panel">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y lms-divide-neutral-subtle lms-table lms-table--balanced">
                            <thead class="lms-bg-neutral-subtle">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted">生徒</th>
                                    <th
                                        class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted
                                            lms-table-col--medium"
                                    >提出物点</th>
                                    <th
                                        class="px-5 py-3 text-right text-xs font-semibold lms-text-neutral-muted
                                            lms-table-col--wide"
                                    >出欠点</th>
                                    <th
                                        class="px-5 py-3 text-left text-xs font-semibold lms-text-neutral-muted
                                            lms-table-col--medium"
                                    >授業態度点</th>
                                    <th
                                        class="px-5 py-3 text-right text-xs font-semibold lms-text-neutral-muted
                                            lms-table-col--compact"
                                    >総合点</th>
                                    <th
                                        class="px-5 py-3 text-center text-xs font-semibold lms-text-neutral-muted
                                            lms-table-col--compact"
                                    >5段階</th>
                                    <th
                                        class="px-5 py-3 text-left text-xs font-semibold
                                            lms-text-neutral-muted lms-table-col--status"
                                    >状態</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y lms-divide-neutral-subtle">
                                @forelse ($rows as $index => $row)
                                    <tr
                                        data-evaluation-row
                                        data-attendance-score="{{ $row->calculation->attendance->score ?? '' }}"
                                    >
                                        <td class="px-5 py-4 text-sm">
                                            <input
                                                class="lms-form-control"
                                                type="hidden"
                                                name="evaluations[{{ $index }}][student_id]"
                                                value="{{ $row->student->id }}"
                                            >
                                            <p
                                                class="font-semibold lms-text-neutral-strong"
                                            >{{ $row->student->student_name }}</p>
                                            <p
                                                class="mt-1 text-xs lms-text-neutral-muted"
                                            >{{ $row->student->student_no ?? '番号未設定' }}</p>
                                        </td>
                                        <td class="px-5 py-4">
                                            <input
                                                class="w-28 rounded-lg border px-3 py-2 lms-form-control
                                                    lms-border-neutral-default"
                                                name="evaluations[{{ $index }}][submission_score]"
                                                aria-label="{{ $row->student->student_name }}さんの提出物点"
                                                type="number"
                                                min="0"
                                                max="100"
                                                step="0.01"
                                                value="{{
                                                    old("evaluations.{$index}.submission_score",
                                                    number_format($row->submissionScore, 2, '.', ''))
                                                }}"
                                                data-submission-score
                                                required
                                            >
                                        </td>
                                        <td
                                            class="lms-table-cell--long px-5 py-4 text-right text-sm align-top"
                                        >
                                            <span
                                                class="font-semibold"
                                            >{{ $row->calculation->attendance->scoreLabel() }}</span>
                                            @if ($row->calculation->attendance->unavailableReason() !== null)
                                                <p
                                                    class="mt-1 max-w-52 text-left text-xs leading-5 lms-text-warning"
                                                >{{ $row->calculation->attendance->unavailableReason() }}</p>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4">
                                            <input
                                                class="w-28 rounded-lg border px-3 py-2 lms-form-control
                                                    lms-border-neutral-default"
                                                name="evaluations[{{ $index }}][attitude_score]"
                                                aria-label="{{ $row->student->student_name }}さんの授業態度点"
                                                type="number"
                                                min="0"
                                                max="100"
                                                step="0.01"
                                                value="{{
                                                    old("evaluations.{$index}.attitude_score",
                                                    number_format($row->attitudeScore, 2, '.', ''))
                                                }}"
                                                data-attitude-score
                                                required
                                            >
                                        </td>
                                        <td
                                            class="whitespace-nowrap px-5 py-4 text-right text-sm font-semibold"
                                            data-total-score
                                        >{{ $row->calculation->totalScoreLabel() }}</td>
                                        <td
                                            class="whitespace-nowrap px-5 py-4 text-center text-sm font-semibold"
                                            data-grade-level
                                        >{{ $row->calculation->gradeLevelLabel() }}</td>
                                        <td
                                            class="whitespace-nowrap px-5 py-4 text-sm"
                                        >{{ $row->evaluation?->status->label() ?? '未入力' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-12 text-center text-sm lms-text-neutral-muted">
                                            対象となる在籍生徒はいません。
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($rows->isNotEmpty())
                    <section
                        class="grid gap-4 p-6 sm:grid-cols-[1fr_auto_1fr] sm:items-end lms-panel"
                    >
                        <div>
                            <label
                                for="status"
                                class="block text-sm font-semibold lms-text-neutral-secondary"
                            >保存状態</label>
                            <select
                                id="status"
                                name="status"
                                class="mt-2 rounded-lg border px-3 py-2 lms-form-control"
                                required
                            >
                                @foreach ($statuses as $status)
                                    <option
                                        value="{{ $status->value }}"
                                        @selected(old('status', 'draft') === $status->value)
                                        @disabled($status === \App\Enums\EvaluationStatus::Confirmed && !
                                        $gradingConfigured)
                                    >{{ $status->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex justify-center gap-3">
                            <a
                                href="{{
                                    route('teacher.evaluations.index', ['academic_year' => $academicYear, 'term_name'
                                    => $term->value])
                                }}"
                                class="rounded-lg border lms-border-neutral-default px-5 py-3 font-semibold
                                    lms-hover-bg-neutral-subtle"
                            >キャンセル</a>
                            <button
                                type="submit"
                                class="rounded-lg px-5 py-3 font-semibold lms-button-primary"
                            >評価を保存</button>
                        </div>
                    </section>
                @endif
            </form>
        @endif
    </div>

    <script>
        const evaluationForm = document.querySelector('[data-evaluation-form]');
        const previewConfig = evaluationForm === null
            ? { roundingMode: null, gradeThresholds: {} }
            : JSON.parse(evaluationForm.dataset.previewConfig);

        const roundScore = (score) => {
            const scaledScore = score * 100;

            if (previewConfig.roundingMode === 'floor') {
                return Math.floor(scaledScore) / 100;
            }

            if (previewConfig.roundingMode === 'ceil') {
                return Math.ceil(scaledScore) / 100;
            }

            return Math.round((score + Number.EPSILON) * 100) / 100;
        };

        const resolveGrade = (score) => {
            for (const level of [5, 4, 3, 2, 1]) {
                const threshold = Number.parseFloat(
                    previewConfig.gradeThresholds[level],
                );

                if (! Number.isNaN(threshold) && score >= threshold) {
                    return String(level);
                }
            }

            return '-';
        };

        document.querySelectorAll('[data-evaluation-row]').forEach((row) => {
            const submission = row.querySelector('[data-submission-score]');
            const attitude = row.querySelector('[data-attitude-score]');
            const total = row.querySelector('[data-total-score]');
            const grade = row.querySelector('[data-grade-level]');
            const attendance = Number.parseFloat(row.dataset.attendanceScore);

            const recalculate = () => {
                const submissionValue = Number.parseFloat(submission.value);
                const attitudeValue = Number.parseFloat(attitude.value);

                if (
                    Number.isNaN(attendance)
                    || Number.isNaN(submissionValue)
                    || Number.isNaN(attitudeValue)
                ) {
                    total.textContent = '-';
                    grade.textContent = '-';
                    return;
                }

                const totalScore = roundScore(
                    (submissionValue + attendance + attitudeValue) / 3,
                );

                total.textContent = totalScore.toFixed(2);
                grade.textContent = previewConfig.roundingMode === null
                    ? '-'
                    : resolveGrade(totalScore);
            };

            submission.addEventListener('input', recalculate);
            attitude.addEventListener('input', recalculate);
            recalculate();
        });
    </script>
@endsection
