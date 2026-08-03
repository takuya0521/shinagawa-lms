@extends('layouts.app')

@section('page-style', 'resources/css/pages/admin/evaluations/index.css')
@section('page-class', 'page-pattern-list page-admin-evaluations-index')

@section('title', '成績管理')
@section('header-title', '成績管理')

@section('content')
    <div class="space-y-6">
        <section class="rounded-2xl bg-white p-6 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">A-029</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">成績一覧</h1>
            <p class="mt-2 text-sm text-slate-600">全生徒・全授業の最終評価と未入力状況を確認します。</p>
        </section>

        <section class="rounded-2xl bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('admin.evaluations.index') }}" class="grid gap-4 lg:grid-cols-4">
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
                    <label for="student_id" class="block text-sm font-semibold text-slate-700">生徒</label>
                    <select id="student_id" name="student_id" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="">すべて</option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}" @selected($selectedStudentId === $student->id)>{{ $student->student_no ?? '-' }} / {{ $student->student_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="course_id" class="block text-sm font-semibold text-slate-700">授業</label>
                    <select id="course_id" name="course_id" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="">すべて</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course->id }}" @selected($selectedCourseId === $course->id)>{{ $course->academic_year }} / {{ $course->course_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="subject_id" class="block text-sm font-semibold text-slate-700">科目</label>
                    <select id="subject_id" name="subject_id" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="">すべて</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}" @selected($selectedSubjectId === $subject->id)>{{ $subject->subject_name }}</option>
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
                <div class="flex items-end">
                    <label class="flex w-full items-center gap-2 rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-semibold text-slate-700">
                        <input type="checkbox" name="missing_only" value="1" @checked($missingOnly)>未入力のみ
                    </label>
                </div>
                <div class="flex items-end gap-3">
                    <button type="submit" class="rounded-lg bg-slate-900 px-5 py-2.5 font-semibold text-white">検索</button>
                    <a href="{{ route('admin.evaluations.index') }}" class="rounded-lg border border-slate-300 px-5 py-2.5 font-semibold">クリア</a>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50"><tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">生徒</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">授業・科目</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-slate-500">3項目点</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-slate-500">総合点</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-slate-500">5段階</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">状態</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-slate-500">操作</th>
                    </tr></thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($evaluations as $evaluation)
                            <tr>
                                <td class="px-5 py-4 text-sm"><p class="font-semibold">{{ $evaluation->student_name }}</p><p class="mt-1 text-xs text-slate-500">{{ $evaluation->student_no ?? '番号未設定' }} / {{ $evaluation->class_name }}</p></td>
                                <td class="px-5 py-4 text-sm"><p class="font-semibold">{{ $evaluation->course_name }}</p><p class="mt-1 text-xs text-slate-500">{{ $evaluation->subject_name }} / 担当 {{ $evaluation->teacher_name ?? '未設定' }}</p></td>
                                @if ($evaluation->evaluation_id !== null)
                                    <td class="px-5 py-4 text-right text-sm">{{ number_format((float) $evaluation->submission_score, 2) }} / {{ number_format((float) $evaluation->attendance_score, 2) }} / {{ number_format((float) $evaluation->attitude_score, 2) }}</td>
                                    <td class="px-5 py-4 text-right text-sm font-semibold">{{ number_format((float) $evaluation->total_score, 2) }}</td>
                                    <td class="px-5 py-4 text-center text-sm font-semibold">{{ $evaluation->grade_level ?? '-' }}</td>
                                    <td class="px-5 py-4 text-sm">{{ \App\Enums\EvaluationStatus::from($evaluation->evaluation_status)->label() }}</td>
                                    <td class="px-5 py-4 text-right text-sm"><a href="{{ route('admin.evaluations.edit', $evaluation->evaluation_id) }}" class="font-semibold text-blue-700">詳細・修正</a></td>
                                @else
                                    <td colspan="3" class="px-5 py-4 text-center text-sm text-slate-400">未入力</td>
                                    <td class="px-5 py-4 text-sm"><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">未入力</span></td>
                                    <td class="px-5 py-4 text-right text-sm">-</td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-6 py-12 text-center text-sm text-slate-500">条件に一致する評価対象はありません。</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($evaluations->hasPages())<div class="border-t border-slate-200 px-6 py-4">{{ $evaluations->links() }}</div>@endif
        </section>
    </div>
@endsection
