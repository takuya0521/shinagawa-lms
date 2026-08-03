@extends('layouts.app')

@section('page-style', 'resources/css/pages/admin/evaluations/edit.css')
@section('page-class', 'page-pattern-form page-admin-evaluations-edit')

@section('title', '生徒別成績詳細・修正')
@section('header-title', '生徒別成績詳細・修正')

@section('content')
    <div class="mx-auto max-w-4xl space-y-6">
        <section class="rounded-2xl bg-white p-6 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">A-030</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">生徒別成績詳細・修正</h1>
            <div class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                <p><span class="font-semibold">生徒：</span>{{ $finalEvaluation->student->student_name }}（{{ $finalEvaluation->student->student_no ?? '番号未設定' }}）</p>
                <p><span class="font-semibold">授業：</span>{{ $finalEvaluation->course->course_name }}</p>
                <p><span class="font-semibold">科目：</span>{{ $finalEvaluation->course->subject->subject_name }}</p>
                <p><span class="font-semibold">年度・期間：</span>{{ $finalEvaluation->academic_year }}年度 / {{ $finalEvaluation->term_name->label() }}</p>
                <p><span class="font-semibold">状態：</span>{{ $finalEvaluation->status->label() }}</p>
                <p><span class="font-semibold">入力者：</span>{{ $finalEvaluation->evaluator->name }}</p>
            </div>
        </section>

        @if (! $gradingConfigured)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">評価閾値または端数処理が未確定です。確定済み評価の再計算は設定確定後に行ってください。</div>
        @endif

        <section class="rounded-2xl bg-white p-6 shadow-sm">
            <form
                method="POST"
                action="{{ route('admin.evaluations.update', $finalEvaluation) }}"
                class="space-y-6"
                data-evaluation-correction-form
                data-preview-config='@json($evaluationPreviewConfig)'
            >
                @csrf
                @method('PUT')
                <div class="grid gap-5 md:grid-cols-3">
                    @foreach ([['submission_score', '提出物点'], ['attendance_score', '出欠点'], ['attitude_score', '授業態度点']] as [$field, $label])
                        <div>
                            <label for="{{ $field }}" class="block text-sm font-semibold text-slate-700">{{ $label }}</label>
                            <input id="{{ $field }}" name="{{ $field }}" type="number" min="0" max="100" step="0.01" value="{{ old($field, $finalEvaluation->{$field}) }}" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2" required>
                            @error($field)<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>
                <div class="grid gap-5 rounded-xl bg-slate-50 p-5 sm:grid-cols-2">
                    <div>
                        <p class="text-sm text-slate-500">再計算後の総合点</p>
                        <p class="mt-1 text-2xl font-bold" data-preview-total>{{ number_format((float) $finalEvaluation->total_score, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">再計算後の5段階評価</p>
                        <p class="mt-1 text-2xl font-bold" data-preview-grade>{{ $finalEvaluation->grade_level ?? '-' }}</p>
                    </div>
                </div>
                <div>
                    <label for="correction_reason" class="block text-sm font-semibold text-slate-700">修正理由 <span class="text-red-600">*</span></label>
                    <textarea id="correction_reason" name="correction_reason" rows="4" maxlength="500" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2" required>{{ old('correction_reason') }}</textarea>
                    <p class="mt-1 text-xs text-slate-500">修正前後の値、理由、実行者、IPアドレスを操作ログへ保存します。</p>
                    @error('correction_reason')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-200 pt-6">
                    <a href="{{ route('admin.evaluations.index', ['academic_year' => $finalEvaluation->academic_year, 'term_name' => $finalEvaluation->term_name->value]) }}" class="rounded-lg border border-slate-300 px-5 py-3 font-semibold">キャンセル</a>
                    <button type="submit" class="rounded-lg bg-slate-900 px-5 py-3 font-semibold text-white">修正を保存</button>
                </div>
            </form>
        </section>
    </div>

    <script>
        const correctionForm = document.querySelector(
            '[data-evaluation-correction-form]',
        );

        if (correctionForm !== null) {
            const previewConfig = JSON.parse(
                correctionForm.dataset.previewConfig,
            );
            const inputs = [
                correctionForm.querySelector('#submission_score'),
                correctionForm.querySelector('#attendance_score'),
                correctionForm.querySelector('#attitude_score'),
            ];
            const totalLabel = correctionForm.querySelector(
                '[data-preview-total]',
            );
            const gradeLabel = correctionForm.querySelector(
                '[data-preview-grade]',
            );

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

            const recalculate = () => {
                const values = inputs.map(
                    (input) => Number.parseFloat(input.value),
                );

                if (values.some((value) => Number.isNaN(value))) {
                    totalLabel.textContent = '-';
                    gradeLabel.textContent = '-';
                    return;
                }

                const totalScore = roundScore(
                    values.reduce((sum, value) => sum + value, 0) / 3,
                );

                totalLabel.textContent = totalScore.toFixed(2);
                gradeLabel.textContent = previewConfig.roundingMode === null
                    ? '-'
                    : resolveGrade(totalScore);
            };

            inputs.forEach((input) => {
                input.addEventListener('input', recalculate);
            });
            recalculate();
        }
    </script>
@endsection
