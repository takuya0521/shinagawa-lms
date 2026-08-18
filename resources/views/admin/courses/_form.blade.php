{{-- 登録画面と編集画面で授業項目を共有し、学年・クラス・科目の組み合わせを同じ順序で扱う。 --}}
@php
    $editing = isset($course);

    $selectedAcademicYear = old(
        'academic_year',
        $editing
            ? $course->academic_year
            : now()->year,
    );

    $selectedGrade = old(
        'grade',
        $editing
            ? $course->grade->value
            : '',
    );

    $selectedClassGroupId = (string) old(
        'class_group_id',
        $editing
            ? $course->class_group_id
            : '',
    );

    $selectedSubjectId = (string) old(
        'subject_id',
        $editing
            ? $course->subject_id
            : '',
    );

    $selectedTeacherId = (string) old(
        'teacher_id',
        $editing
            ? $course->teacher_id
            : '',
    );

    $selectedStatus = old(
        'status',
        $editing
            ? $course->status->value
            : \App\Enums\MasterStatus::Active->value,
    );
@endphp

<div class="space-y-6">
    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label
                for="academic_year"
                class="block text-sm font-semibold lms-text-neutral-secondary"
            >
                年度
                <span class="lms-text-danger">*</span>
            </label>

            <select
                id="academic_year"
                name="academic_year"
                class="mt-2 block w-full rounded-lg border px-3 py-2 lms-text-neutral-strong shadow-sm lms-focus-border
                    focus:outline-none focus:ring-2 lms-focus-ring lms-form-control"
                required
            >
                @foreach ($academicYears as $academicYear)
                    <option
                        value="{{ $academicYear }}"
                        @selected(
                            (string) $selectedAcademicYear
                                === (string) $academicYear
                        )
                    >
                        {{ $academicYear }}年度
                    </option>
                @endforeach
            </select>

            @error('academic_year')
                <p class="mt-2 text-sm lms-text-danger">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div>
            <label
                for="grade"
                class="block text-sm font-semibold lms-text-neutral-secondary"
            >
                学年
                <span class="lms-text-danger">*</span>
            </label>

            <select
                id="grade"
                name="grade"
                class="mt-2 block w-full rounded-lg border px-3 py-2 lms-text-neutral-strong shadow-sm lms-focus-border
                    focus:outline-none focus:ring-2 lms-focus-ring lms-form-control"
                required
            >
                <option value="">
                    選択してください
                </option>

                @foreach ($grades as $grade)
                    <option
                        value="{{ $grade->value }}"
                        @selected(
                            (string) $selectedGrade
                                === $grade->value
                        )
                    >
                        {{ $grade->label() }}
                    </option>
                @endforeach
            </select>

            @error('grade')
                <p class="mt-2 text-sm lms-text-danger">
                    {{ $message }}
                </p>
            @enderror
        </div>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label
                for="class_group_id"
                class="block text-sm font-semibold lms-text-neutral-secondary"
            >
                クラス
                <span class="lms-text-danger">*</span>
            </label>

            <select
                id="class_group_id"
                name="class_group_id"
                class="mt-2 block w-full rounded-lg border px-3 py-2 lms-text-neutral-strong shadow-sm lms-focus-border
                    focus:outline-none focus:ring-2 lms-focus-ring lms-form-control"
                required
            >
                <option value="">
                    選択してください
                </option>

                @foreach ($classGroups as $classGroup)
                    <option
                        value="{{ $classGroup->id }}"
                        @selected(
                            $selectedClassGroupId
                                === (string) $classGroup->id
                        )
                    >
                        {{ $classGroup->class_code }}
                        /
                        {{ $classGroup->class_name }}

                        @if ($classGroup->status === \App\Enums\MasterStatus::Inactive)
                            （無効）
                        @endif
                    </option>
                @endforeach
            </select>

            @error('class_group_id')
                <p class="mt-2 text-sm lms-text-danger">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div>
            <label
                for="subject_id"
                class="block text-sm font-semibold lms-text-neutral-secondary"
            >
                科目
                <span class="lms-text-danger">*</span>
            </label>

            <select
                id="subject_id"
                name="subject_id"
                class="mt-2 block w-full rounded-lg border px-3 py-2 lms-text-neutral-strong shadow-sm lms-focus-border
                    focus:outline-none focus:ring-2 lms-focus-ring lms-form-control"
                required
            >
                <option value="">
                    選択してください
                </option>

                @foreach ($subjects as $subject)
                    <option
                        value="{{ $subject->id }}"
                        @selected(
                            $selectedSubjectId
                                === (string) $subject->id
                        )
                    >
                        {{ $subject->subject_code }}
                        /
                        {{ $subject->subject_name }}

                        @if ($subject->status === \App\Enums\MasterStatus::Inactive)
                            （無効）
                        @endif
                    </option>
                @endforeach
            </select>

            @error('subject_id')
                <p class="mt-2 text-sm lms-text-danger">
                    {{ $message }}
                </p>
            @enderror
        </div>
    </div>

    <div>
        <label
            for="course_name"
            class="block text-sm font-semibold lms-text-neutral-secondary"
        >
            授業名
            <span class="lms-text-danger">*</span>
        </label>

        <input
        class="lms-form-control mt-2 block w-full rounded-lg border lms-border-neutral-default px-3 py-2
            lms-text-neutral-strong shadow-sm lms-focus-border focus:outline-none focus:ring-2 lms-focus-ring"
            id="course_name"
            name="course_name"
            type="text"
            maxlength="100"
            value="{{ old(
                'course_name',
                $editing
                    ? $course->course_name
                    : '',
            ) }}"
            placeholder="例：数学Ⅰ"
            required
        >

        @error('course_name')
            <p class="mt-2 text-sm lms-text-danger">
                {{ $message }}
            </p>
        @enderror
    </div>

    <div>
        <label
            for="teacher_id"
            class="block text-sm font-semibold lms-text-neutral-secondary"
        >
            担当教員
        </label>

        <select
            id="teacher_id"
            name="teacher_id"
            class="mt-2 block w-full rounded-lg border px-3 py-2 lms-text-neutral-strong shadow-sm lms-focus-border
                focus:outline-none focus:ring-2 lms-focus-ring lms-form-control"
        >
            <option value="">
                未設定
            </option>

            @foreach ($teachers as $teacher)
                <option
                    value="{{ $teacher->id }}"
                    @selected(
                        $selectedTeacherId
                            === (string) $teacher->id
                    )
                >
                    {{ $teacher->user->name }}
                    /
                    {{ $teacher->user->email }}

                    @if (
                        $teacher->status === \App\Enums\MasterStatus::Inactive
                        || $teacher->user->status === \App\Enums\UserStatus::Suspended
                    )
                        （無効）
                    @endif
                </option>
            @endforeach
        </select>

        <p class="mt-2 text-sm lms-text-neutral-muted">
            担当教員が決まっていない場合は、未設定のまま登録できます。
        </p>

        @error('teacher_id')
            <p class="mt-2 text-sm lms-text-danger">
                {{ $message }}
            </p>
        @enderror
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label
                for="google_classroom_url"
                class="block text-sm font-semibold lms-text-neutral-secondary"
            >
                Google Classroom URL
            </label>

            <input
            class="lms-form-control mt-2 block w-full rounded-lg border lms-border-neutral-default px-3 py-2
                lms-text-neutral-strong shadow-sm lms-focus-border focus:outline-none focus:ring-2 lms-focus-ring"
                id="google_classroom_url"
                name="google_classroom_url"
                type="url"
                maxlength="500"
                value="{{ old(
                    'google_classroom_url',
                    $editing
                        ? $course->google_classroom_url
                        : '',
                ) }}"
                placeholder="https://classroom.google.com/..."
            >

            @error('google_classroom_url')
                <p class="mt-2 text-sm lms-text-danger">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div>
            <label
                for="google_classroom_id"
                class="block text-sm font-semibold lms-text-neutral-secondary"
            >
                Google Classroom外部ID
            </label>

            <input
            class="lms-form-control mt-2 block w-full rounded-lg border lms-border-neutral-default px-3 py-2
                lms-text-neutral-strong shadow-sm lms-focus-border focus:outline-none focus:ring-2 lms-focus-ring"
                id="google_classroom_id"
                name="google_classroom_id"
                type="text"
                maxlength="100"
                value="{{ old(
                    'google_classroom_id',
                    $editing
                        ? $course->google_classroom_id
                        : '',
                ) }}"
                placeholder="Google Classroom側の識別子"
            >

            @error('google_classroom_id')
                <p class="mt-2 text-sm lms-text-danger">
                    {{ $message }}
                </p>
            @enderror
        </div>
    </div>

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

    <div class="flex flex-col-reverse gap-3 border-t lms-border-neutral-subtle pt-6 sm:flex-row sm:justify-end">
        <a
            href="{{ route('admin.courses.index') }}"
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
