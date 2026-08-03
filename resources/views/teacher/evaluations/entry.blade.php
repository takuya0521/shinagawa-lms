@extends('layouts.app')

@section('page-style', 'resources/css/pages/teacher/evaluations/entry.css')
@section('page-class', 'page-pattern-form page-teacher-evaluations-entry')

@section('title', '最終評価入力')
@section('header-title', '最終評価入力')

@section('content')
    <div class="space-y-6">
        <section class="rounded-2xl bg-white p-6 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">T-007</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">最終評価入力</h1>
            <p class="mt-2 text-sm text-slate-600">提出物点と授業態度点を入力します。出欠点はLMSの出欠記録から自動算出します。</p>
        </section>

        @if (! $gradingConfigured)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
                評価閾値または端数処理が未確定です。下書き保存はできますが、評価の確定はできません。
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800">
                <p class="font-semibold">入力内容を確認してください。</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="rounded-2xl bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('teacher.evaluations.entry') }}" class="grid gap-4 lg:grid-cols-[180px_180px_minmax(0,1fr)_auto]">
                <div>
                    <label for="academic_year" class="block text-sm font-semibold text-slate-700">年度</label>
                    <input id="academic_year" name="academic_year" type="number" min="2000" max="2100" value="{{ $academicYear }}" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label for="term_name" class="block text-sm font-semibold text-slate-700">期間</label>
                    <select id="term_name" name="term_name" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2">
                        @foreach ($terms as $termOption)
                            <option value="{{ $termOption->value }}" @selected($term === $termOption)>{{ $termOption->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="course_id" class="block text-sm font-semibold text-slate-700">担当授業</label>
                    <select id="course_id" name="course_id" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2" required>
                        <option value="">選択してください</option>
                        @foreach ($courses as $courseOption)
                            <option value="{{ $courseOption->id }}" @selected($course?->id === $courseOption->id)>{{ $courseOption->academic_year }}年度 / {{ $courseOption->course_name }} / {{ $courseOption->classGroup->class_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full rounded-lg bg-slate-900 px-5 py-2.5 font-semibold text-white">対象生徒を表示</button>
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
                <input type="hidden" name="course_id" value="{{ $course->id }}">
                <input type="hidden" name="academic_year" value="{{ $academicYear }}">
                <input type="hidden" name="term_name" value="{{ $term->value }}">

                <section class="rounded-2xl bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-slate-900">{{ $course->course_name }}</h2>
                            <p class="mt-1 text-sm text-slate-600">{{ $course->subject->subject_name }} / {{ $course->grade->label() }} / {{ $course->classGroup->class_name }}</p>
                        </div>
                        @if ($course->google_classroom_url !== null)
                            <a href="{{ $course->google_classroom_url }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-blue-700 hover:text-blue-900">Google Classroomを開く</a>
                        @endif
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">生徒</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">提出物点</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold text-slate-500">出欠点</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">授業態度点</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold text-slate-500">総合点</th>
                                    <th class="px-5 py-3 text-center text-xs font-semibold text-slate-500">5段階</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">状態</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                @forelse ($rows as $index => $row)
                                    <tr data-evaluation-row data-attendance-score="{{ $row->calculation->attendance->score ?? '' }}">
                                        <td class="px-5 py-4 text-sm">
                                            <input type="hidden" name="evaluations[{{ $index }}][student_id]" value="{{ $row->student->id }}">
                                            <p class="font-semibold text-slate-900">{{ $row->student->student_name }}</p>
                                            <p class="mt-1 text-xs text-slate-500">{{ $row->student->student_no ?? '番号未設定' }}</p>
                                        </td>
                                        <td class="px-5 py-4">
                                            <input name="evaluations[{{ $index }}][submission_score]" type="number" min="0" max="100" step="0.01" value="{{ old("evaluations.{$index}.submission_score", number_format($row->submissionScore, 2, '.', '')) }}" class="w-28 rounded-lg border border-slate-300 px-3 py-2" data-submission-score required>
                                        </td>
                                        <td class="px-5 py-4 text-right text-sm">
                                            <span class="font-semibold">{{ $row->calculation->attendance->scoreLabel() }}</span>
                                            @if ($row->calculation->attendance->unavailableReason() !== null)
                                                <p class="mt-1 max-w-52 text-xs text-amber-700">{{ $row->calculation->attendance->unavailableReason() }}</p>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4">
                                            <input name="evaluations[{{ $index }}][attitude_score]" type="number" min="0" max="100" step="0.01" value="{{ old("evaluations.{$index}.attitude_score", number_format($row->attitudeScore, 2, '.', '')) }}" class="w-28 rounded-lg border border-slate-300 px-3 py-2" data-attitude-score required>
                                        </td>
                                        <td class="px-5 py-4 text-right text-sm font-semibold" data-total-score>{{ $row->calculation->totalScoreLabel() }}</td>
                                        <td class="px-5 py-4 text-center text-sm font-semibold" data-grade-level>{{ $row->calculation->gradeLevelLabel() }}</td>
                                        <td class="px-5 py-4 text-sm">{{ $row->evaluation?->status->label() ?? '未入力' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="px-6 py-12 text-center text-sm text-slate-500">対象となる在籍生徒はいません。</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($rows->isNotEmpty())
                    <section class="flex flex-col gap-4 rounded-2xl bg-white p-6 shadow-sm sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <label for="status" class="block text-sm font-semibold text-slate-700">保存状態</label>
                            <select id="status" name="status" class="mt-2 rounded-lg border border-slate-300 px-3 py-2" required>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->value }}" @selected(old('status', 'draft') === $status->value) @disabled($status === \App\Enums\EvaluationStatus::Confirmed && ! $gradingConfigured)>{{ $status->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex gap-3">
                            <a href="{{ route('teacher.evaluations.index', ['academic_year' => $academicYear, 'term_name' => $term->value]) }}" class="rounded-lg border border-slate-300 px-5 py-3 font-semibold hover:bg-slate-50">キャンセル</a>
                            <button type="submit" class="rounded-lg bg-slate-900 px-5 py-3 font-semibold text-white hover:bg-slate-700">評価を保存</button>
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
