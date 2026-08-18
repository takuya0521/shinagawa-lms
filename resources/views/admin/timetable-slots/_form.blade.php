{{-- 登録画面と編集画面で時間割条件を共有し、曜日・時限・授業の組み合わせを同じ規則で扱う。 --}}
@php
    $editing = isset($timetableSlot);

    $resolvedCourseId = old(
        'course_id',
        $editing
            ? $timetableSlot->course_id
            : $selectedCourseId,
    );

    $selectedCourseIdValue = $resolvedCourseId !== null
        ? (string) $resolvedCourseId
        : '';

    $selectedDayOfWeek = (string) old(
        'day_of_week',
        $editing
            ? $timetableSlot->day_of_week->value
            : ($preferredDayOfWeek?->value ?? ''),
    );

    $selectedPeriodNo = (string) old(
        'period_no',
        $editing
            ? $timetableSlot->period_no
            : ($preferredPeriodNo ?? ''),
    );

    $selectedStartTime = old(
        'start_time',
        $editing && $timetableSlot->start_time !== null
            ? substr($timetableSlot->start_time, 0, 5)
            : '',
    );

    $selectedEndTime = old(
        'end_time',
        $editing && $timetableSlot->end_time !== null
            ? substr($timetableSlot->end_time, 0, 5)
            : '',
    );

    $selectedStatus = old(
        'status',
        $editing
            ? $timetableSlot->status->value
            : \App\Enums\MasterStatus::Active->value,
    );
@endphp

<div class="space-y-6">
    <div>
        <label
            for="course_id"
            class="block text-sm font-semibold lms-text-neutral-secondary"
        >
            授業
            <span class="lms-text-danger">*</span>
        </label>

        <select
            id="course_id"
            name="course_id"
            class="mt-2 block w-full rounded-lg border px-3 py-2 lms-text-neutral-strong shadow-sm lms-focus-border
                focus:outline-none focus:ring-2 lms-focus-ring lms-form-control"
            required
        >
            <option value="">
                選択してください
            </option>

            @foreach ($courses as $course)
                <option
                    value="{{ $course->id }}"
                    @selected(
                        $selectedCourseIdValue
                            === (string) $course->id
                    )
                >
                    {{ $course->academic_year }}年度
                    /
                    {{ $course->grade->label() }}
                    /
                    {{ $course->classGroup->class_code }}
                    /
                    {{ $course->course_name }}
                    （{{ $course->subject->subject_name }}）

                    @if ($course->teacher !== null)
                        /
                        {{ $course->teacher->user->name }}
                    @endif

                    @if ($course->status === \App\Enums\MasterStatus::Inactive)
                        （無効）
                    @endif
                </option>
            @endforeach
        </select>

        <p class="mt-2 text-sm lms-text-neutral-muted">
            授業管理で登録済みの年度・学年・クラス・科目・担当教員が使用されます。
        </p>

        @error('course_id')
            <p class="mt-2 text-sm lms-text-danger">
                {{ $message }}
            </p>
        @enderror
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label
                for="day_of_week"
                class="block text-sm font-semibold lms-text-neutral-secondary"
            >
                曜日
                <span class="lms-text-danger">*</span>
            </label>

            <select
                id="day_of_week"
                name="day_of_week"
                class="mt-2 block w-full rounded-lg border px-3 py-2 lms-text-neutral-strong shadow-sm lms-focus-border
                    focus:outline-none focus:ring-2 lms-focus-ring lms-form-control"
                required
            >
                <option value="">
                    選択してください
                </option>

                @foreach ($daysOfWeek as $dayOfWeek)
                    <option
                        value="{{ $dayOfWeek->value }}"
                        @selected(
                            $selectedDayOfWeek
                                === (string) $dayOfWeek->value
                        )
                    >
                        {{ $dayOfWeek->label() }}
                    </option>
                @endforeach
            </select>

            @error('day_of_week')
                <p class="mt-2 text-sm lms-text-danger">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div>
            <label
                for="period_no"
                class="block text-sm font-semibold lms-text-neutral-secondary"
            >
                時限
                <span class="lms-text-danger">*</span>
            </label>

            <select
                id="period_no"
                name="period_no"
                class="mt-2 block w-full rounded-lg border px-3 py-2 lms-text-neutral-strong shadow-sm lms-focus-border
                    focus:outline-none focus:ring-2 lms-focus-ring lms-form-control"
                required
            >
                <option value="">
                    選択してください
                </option>

                @foreach ($periods as $periodNo)
                    <option
                        value="{{ $periodNo }}"
                        @selected(
                            $selectedPeriodNo
                                === (string) $periodNo
                        )
                    >
                        {{ $periodNo }}時限
                    </option>
                @endforeach
            </select>

            @error('period_no')
                <p class="mt-2 text-sm lms-text-danger">
                    {{ $message }}
                </p>
            @enderror
        </div>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label
                for="start_time"
                class="block text-sm font-semibold lms-text-neutral-secondary"
            >
                開始時刻
            </label>

            <input
                id="start_time"
                name="start_time"
                type="time"
                value="{{ $selectedStartTime }}"
                class="mt-2 block w-full rounded-lg border px-3 py-2 lms-text-neutral-strong shadow-sm lms-focus-border
                    focus:outline-none focus:ring-2 lms-focus-ring lms-form-control"
            >

            @error('start_time')
                <p class="mt-2 text-sm lms-text-danger">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div>
            <label
                for="end_time"
                class="block text-sm font-semibold lms-text-neutral-secondary"
            >
                終了時刻
            </label>

            <input
                id="end_time"
                name="end_time"
                type="time"
                value="{{ $selectedEndTime }}"
                class="mt-2 block w-full rounded-lg border px-3 py-2 lms-text-neutral-strong shadow-sm lms-focus-border
                    focus:outline-none focus:ring-2 lms-focus-ring lms-form-control"
            >

            @error('end_time')
                <p class="mt-2 text-sm lms-text-danger">
                    {{ $message }}
                </p>
            @enderror
        </div>
    </div>

    <p class="text-sm lms-text-neutral-muted">
        時刻を設定する場合は、開始時刻と終了時刻の両方を入力してください。
    </p>

    <div>
        <label
            for="status"
            class="block text-sm font-semibold lms-text-neutral-secondary"
        >
            状態
            <span class="lms-text-danger">*</span>
        </label>

        <select
            id="status"
            name="status"
            class="mt-2 block w-full rounded-lg border px-3 py-2 lms-text-neutral-strong shadow-sm lms-focus-border
                focus:outline-none focus:ring-2 lms-focus-ring lms-form-control"
            required
        >
            @foreach ($statuses as $status)
                <option
                    value="{{ $status->value }}"
                    @selected(
                        $selectedStatus === $status->value
                    )
                >
                    {{ $status->label() }}
                </option>
            @endforeach
        </select>

        @error('status')
            <p class="mt-2 text-sm lms-text-danger">
                {{ $message }}
            </p>
        @enderror
    </div>

    <div class="rounded-lg border lms-border-warning lms-bg-warning-soft px-4 py-3 text-sm lms-text-warning-strong">
        有効な時間割では、同じクラスまたは同じ担当教員の同一曜日・時限への重複登録はできません。
    </div>

    <div class="flex flex-col-reverse gap-3 border-t lms-border-neutral-subtle pt-6 sm:flex-row sm:justify-end">
        <a
            href="{{ route('admin.timetable-slots.index') }}"
            class="inline-flex items-center justify-center rounded-lg border lms-border-neutral-default lms-bg-surface
                px-5 py-3 font-semibold lms-text-neutral-secondary lms-hover-bg-neutral-subtle"
        >
            キャンセル
        </a>

        <button
            type="submit"
            class="inline-flex items-center justify-center rounded-lg px-5 py-3 font-semibold lms-button-primary"
        >
            {{ $editing ? '更新する' : '登録する' }}
        </button>
    </div>
</div>
