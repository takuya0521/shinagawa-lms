@extends('layouts.app')

@section('page-style', 'resources/css/pages/teacher/courses/students.css')
@section('page-class', 'page-pattern-list page-teacher-courses-students')

@section('title', '授業別生徒一覧')
@section('header-title', '授業別生徒一覧')

@section('content')
    <section class="space-y-6">
        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-sm font-semibold text-slate-500">
                        {{ $course->academic_year }}年度 / {{ $course->grade->label() }} / {{ $course->classGroup->class_name }}
                    </p>
                    <h1 class="mt-1 text-2xl font-bold text-slate-900">
                        {{ $course->course_name }}
                    </h1>
                    <p class="mt-1 text-sm text-slate-600">
                        {{ $course->subject->subject_name }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('teacher.evaluations.entry', ['course_id' => $course->id, 'academic_year' => $course->academic_year, 'term_name' => 'annual']) }}" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                        最終評価を入力
                    </a>
                    <a href="{{ route('teacher.courses.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold hover:bg-slate-50">
                        担当授業一覧へ
                    </a>
                </div>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('teacher.courses.students.index', $course) }}" class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_220px_auto_auto]">
                <div>
                    <label for="keyword" class="block text-sm font-semibold text-slate-700">キーワード</label>
                    <input id="keyword" name="keyword" value="{{ $keyword }}" placeholder="生徒番号・氏名" class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label for="status" class="block text-sm font-semibold text-slate-700">状態</label>
                    <select id="status" name="status" class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="">在籍のみ</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected($selectedStatus === $status)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full rounded-lg bg-slate-900 px-5 py-2.5 font-semibold text-white">検索</button>
                </div>
                <div class="flex items-end">
                    <a href="{{ route('teacher.courses.students.index', $course) }}" class="w-full rounded-lg border border-slate-300 px-5 py-2.5 text-center font-semibold">クリア</a>
                </div>
            </form>
        </div>

        <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500">生徒番号</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500">氏名</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500">状態</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500">操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($students as $student)
                            <tr>
                                <td class="px-6 py-4 text-sm font-semibold text-slate-900">{{ $student->student_no ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-slate-700">{{ $student->student_name }}</td>
                                <td class="px-6 py-4 text-sm text-slate-700">{{ $student->status->label() }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <div class="flex justify-end gap-3">
                                        <a
                                            href="{{ route('teacher.interviews.create', ['student_id' => $student->id]) }}"
                                            class="font-semibold text-blue-700 hover:text-blue-900"
                                        >
                                            面談記録
                                        </a>

                                        <a
                                            href="{{ route('teacher.interviews.index', ['student_id' => $student->id]) }}"
                                            class="font-semibold text-slate-600 hover:text-slate-900"
                                        >
                                            面談履歴
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-12 text-center text-sm text-slate-500">対象生徒はいません。</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($students->hasPages())
                <div class="border-t border-slate-200 px-6 py-4">{{ $students->links() }}</div>
            @endif
        </div>
    </section>
@endsection
