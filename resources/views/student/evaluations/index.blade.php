@extends('layouts.app')

@section('page-class', 'page-pattern-list page-student-evaluations-index')

@section('title', '成績一覧')
@section('header-title', '成績一覧')

@section('content')
    <div class="space-y-6">

        <section class="p-6 lms-panel">
            <form
                method="GET"
                action="{{ route('student.evaluations.index') }}"
                class="flex flex-col gap-4 sm:flex-row sm:items-end"
            >
                <div>
                    <label for="academic_year" class="block text-sm font-semibold lms-text-neutral-secondary">
                        年度
                    </label>
                    <select
                        id="academic_year"
                        name="academic_year"
                        class="mt-2 rounded-lg border px-3 py-2 lms-form-control"
                    >
                        @foreach ($academicYears as $year)
                            <option value="{{ $year }}" @selected((int) $academicYear === (int) $year)>
                                {{ $year }}年度
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="term_name" class="block text-sm font-semibold lms-text-neutral-secondary">
                        期間
                    </label>
                    <select
                        id="term_name"
                        name="term_name"
                        class="mt-2 rounded-lg border px-3 py-2 lms-form-control"
                    >
                        @foreach ($terms as $termOption)
                            <option value="{{ $termOption->value }}" @selected($term === $termOption)>
                                {{ $termOption->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="rounded-lg px-5 py-2.5 font-semibold lms-button-primary">
                    表示
                </button>
            </form>
        </section>

        <section class="grid gap-4 md:grid-cols-2">
            @forelse ($evaluations as $evaluation)
                <article class="p-6 lms-panel">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold lms-text-neutral-muted">
                                {{ $evaluation->course->subject->subject_name }}
                            </p>
                            <h2 class="mt-1 text-xl font-bold lms-text-neutral-strong">
                                {{ $evaluation->course->course_name }}
                            </h2>
                        </div>

                        <div class="rounded-xl px-4 py-3 text-center lms-button-primary">
                            <p class="text-xs">5段階</p>
                            <p class="text-2xl font-bold">{{ $evaluation->grade_level ?? '-' }}</p>
                        </div>
                    </div>

                    <div class="mt-5 grid grid-cols-2 gap-4 rounded-xl p-4 lms-bg-neutral-subtle">
                        <div>
                            <p class="text-xs lms-text-neutral-muted">総合点</p>
                            <p class="mt-1 text-xl font-bold">
                                {{ number_format((float) $evaluation->total_score, 2) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs lms-text-neutral-muted">期間</p>
                            <p class="mt-1 font-semibold">{{ $evaluation->term_name->label() }}</p>
                        </div>
                    </div>

                    @if ($evaluation->course->google_classroom_url !== null)
                        <a
                            href="{{ $evaluation->course->google_classroom_url }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mt-5 inline-flex font-semibold lms-link-primary"
                        >
                            Google Classroomで課題詳細を確認
                        </a>
                    @endif
                </article>
            @empty
                <p class="px-6 py-12 text-center text-sm md:col-span-2 lms-panel lms-text-neutral-muted">
                    選択した年度・期間に公開済みの成績はありません。
                </p>
            @endforelse
        </section>

        @if ($evaluations->hasPages())
            <div>
                {{ $evaluations->links() }}
            </div>
        @endif
    </div>
@endsection
