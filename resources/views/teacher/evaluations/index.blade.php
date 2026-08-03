@extends('layouts.app')

@section('page-style', 'resources/css/pages/teacher/evaluations/index.css')
@section('page-class', 'page-pattern-list page-teacher-evaluations-index')

@section('title', '科目別評価一覧')
@section('header-title', '科目別評価一覧')

@section('content')
    <div class="space-y-6">
        <section class="flex flex-col gap-4 rounded-2xl bg-white p-6 shadow-sm sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-slate-500">T-006</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-900">科目別評価一覧</h1>
                <p class="mt-2 text-sm text-slate-600">担当授業の評価入力状況と確定状態を確認します。</p>
            </div>
            <a href="{{ route('teacher.evaluations.entry', ['academic_year' => $academicYear, 'term_name' => $term->value]) }}" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-5 py-3 font-semibold text-white hover:bg-slate-700">最終評価を入力</a>
        </section>

        <section class="rounded-2xl bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('teacher.evaluations.index') }}" class="grid gap-4 lg:grid-cols-5">
                <div>
                    <label for="academic_year" class="block text-sm font-semibold text-slate-700">年度</label>
                    <select id="academic_year" name="academic_year" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2">
                        @foreach ($academicYears as $year)
                            <option value="{{ $year }}" @selected((int) $academicYear === (int) $year)>{{ $year }}年度</option>
                        @endforeach
                    </select>
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
                    <select id="course_id" name="course_id" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="">すべて</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course->id }}" @selected($selectedCourseId === $course->id)>{{ $course->academic_year }} / {{ $course->course_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="status" class="block text-sm font-semibold text-slate-700">状態</label>
                    <select id="status" name="status" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="">すべて</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected($selectedStatus === $status)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-3">
                    <label class="flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-semibold text-slate-700">
                        <input type="checkbox" name="missing_only" value="1" @checked($missingOnly)>
                        未入力のみ
                    </label>
                    <button type="submit" class="rounded-lg bg-slate-900 px-5 py-2.5 font-semibold text-white">検索</button>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">授業</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">生徒</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-slate-500">提出物</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-slate-500">出欠</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-slate-500">授業態度</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-slate-500">総合</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold text-slate-500">5段階</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">状態</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-slate-500">操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($evaluations as $evaluation)
                            <tr>
                                <td class="px-5 py-4 text-sm">
                                    <p class="font-semibold text-slate-900">{{ $evaluation->course_name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $evaluation->subject_name }} / {{ $evaluation->class_name }}</p>
                                </td>
                                <td class="px-5 py-4 text-sm">
                                    <p class="font-semibold text-slate-900">{{ $evaluation->student_name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $evaluation->student_no ?? '番号未設定' }}</p>
                                </td>
                                @if ($evaluation->evaluation_id !== null)
                                    <td class="px-5 py-4 text-right text-sm">{{ number_format((float) $evaluation->submission_score, 2) }}</td>
                                    <td class="px-5 py-4 text-right text-sm">{{ number_format((float) $evaluation->attendance_score, 2) }}</td>
                                    <td class="px-5 py-4 text-right text-sm">{{ number_format((float) $evaluation->attitude_score, 2) }}</td>
                                    <td class="px-5 py-4 text-right text-sm font-semibold">{{ number_format((float) $evaluation->total_score, 2) }}</td>
                                    <td class="px-5 py-4 text-center text-sm font-semibold">{{ $evaluation->grade_level ?? '-' }}</td>
                                    <td class="px-5 py-4 text-sm">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $evaluation->evaluation_status === 'confirmed' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                            {{ \App\Enums\EvaluationStatus::from($evaluation->evaluation_status)->label() }}
                                        </span>
                                    </td>
                                @else
                                    <td colspan="5" class="px-5 py-4 text-center text-sm text-slate-400">未入力</td>
                                    <td class="px-5 py-4 text-sm"><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">未入力</span></td>
                                @endif
                                <td class="px-5 py-4 text-right text-sm">
                                    <a href="{{ route('teacher.evaluations.entry', ['course_id' => $evaluation->course_id, 'academic_year' => $evaluation->academic_year, 'term_name' => $term->value]) }}" class="font-semibold text-blue-700 hover:text-blue-900">入力・編集</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-6 py-12 text-center text-sm text-slate-500">条件に一致する評価対象はありません。</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($evaluations->hasPages())
                <div class="border-t border-slate-200 px-6 py-4">{{ $evaluations->links() }}</div>
            @endif
        </section>
    </div>
@endsection
