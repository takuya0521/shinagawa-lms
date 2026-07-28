@php
    $selectedRole = old(
        'role',
        $user->role?->value ?? \App\Enums\UserRole::Teacher->value,
    );

    $selectedStatus = old(
        'status',
        $user->status?->value ?? \App\Enums\UserStatus::Active->value,
    );

    $selectedStudentStatus = old(
        'student_status',
        $studentProfile?->status?->value
            ?? \App\Enums\StudentStatus::Active->value,
    );
@endphp

<div class="space-y-8">
    <section>
        <h2 class="text-lg font-bold text-slate-900">
            アカウント情報
        </h2>

        <div class="mt-4 grid gap-5 md:grid-cols-2">
            <div>
                <label
                    for="name"
                    class="block text-sm font-medium text-slate-700"
                >
                    氏名
                    <span class="text-red-600">*</span>
                </label>

                <input
                    id="name"
                    name="name"
                    type="text"
                    value="{{ old('name', $user->name) }}"
                    required
                    class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
                >

                @error('name')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label
                    for="email"
                    class="block text-sm font-medium text-slate-700"
                >
                    メールアドレス
                    <span class="text-red-600">*</span>
                </label>

                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email', $user->email) }}"
                    required
                    class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
                >

                @error('email')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label
                    for="role"
                    class="block text-sm font-medium text-slate-700"
                >
                    ロール
                    <span class="text-red-600">*</span>
                </label>

                <select
                    id="role"
                    name="role"
                    required
                    class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
                >
                    @foreach ($roles as $role)
                        <option
                            value="{{ $role->value }}"
                            @selected($selectedRole === $role->value)
                        >
                            {{ $role->label() }}
                        </option>
                    @endforeach
                </select>

                @error('role')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label
                    for="status"
                    class="block text-sm font-medium text-slate-700"
                >
                    利用状態
                    <span class="text-red-600">*</span>
                </label>

                <select
                    id="status"
                    name="status"
                    required
                    class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
                >
                    @foreach ($statuses as $status)
                        <option
                            value="{{ $status->value }}"
                            @selected($selectedStatus === $status->value)
                        >
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>

                @error('status')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label
                    for="password"
                    class="block text-sm font-medium text-slate-700"
                >
                    パスワード

                    @unless ($user->exists)
                        <span class="text-red-600">*</span>
                    @endunless
                </label>

                <input
                    id="password"
                    name="password"
                    type="password"
                    @required(! $user->exists)
                    autocomplete="new-password"
                    class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
                >

                @if ($user->exists)
                    <p class="mt-1 text-xs text-slate-500">
                        変更しない場合は空欄にしてください。
                    </p>
                @endif

                @error('password')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label
                    for="password_confirmation"
                    class="block text-sm font-medium text-slate-700"
                >
                    パスワード確認
                </label>

                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    @required(! $user->exists)
                    autocomplete="new-password"
                    class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
                >
            </div>
        </div>
    </section>

    <section
        id="student-fields"
        data-student-role="{{ \App\Enums\UserRole::Student->value }}"
        class="rounded-xl border border-slate-200 bg-slate-50 p-5"
        @if ($selectedRole !== \App\Enums\UserRole::Student->value)
            hidden
        @endif
    >
        <h2 class="text-lg font-bold text-slate-900">
            生徒情報
        </h2>

        <p class="mt-1 text-sm text-slate-600">
            ロールが「生徒」の場合に登録されます。
        </p>

        <div class="mt-4 grid gap-5 md:grid-cols-2">
            <div>
                <label
                    for="student_no"
                    class="block text-sm font-medium text-slate-700"
                >
                    生徒番号
                </label>

                <input
                    id="student_no"
                    name="student_no"
                    type="text"
                    value="{{ old(
                        'student_no',
                        $studentProfile?->student_no,
                    ) }}"
                    class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2"
                >

                @error('student_no')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label
                    for="student_name"
                    class="block text-sm font-medium text-slate-700"
                >
                    生徒氏名
                    <span class="text-red-600">*</span>
                </label>

                <input
                    id="student_name"
                    name="student_name"
                    type="text"
                    value="{{ old(
                        'student_name',
                        $studentProfile?->student_name ?? $user->name,
                    ) }}"
                    data-student-required
                    class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2"
                >

                @error('student_name')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label
                    for="grade"
                    class="block text-sm font-medium text-slate-700"
                >
                    学年
                    <span class="text-red-600">*</span>
                </label>

                <input
                    id="grade"
                    name="grade"
                    type="text"
                    value="{{ old(
                        'grade',
                        $studentProfile?->grade,
                    ) }}"
                    placeholder="例：1"
                    data-student-required
                    class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2"
                >

                @error('grade')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label
                    for="affiliation"
                    class="block text-sm font-medium text-slate-700"
                >
                    所属
                </label>

                <input
                    id="affiliation"
                    name="affiliation"
                    type="text"
                    value="{{ old(
                        'affiliation',
                        $studentProfile?->affiliation,
                    ) }}"
                    class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2"
                >

                @error('affiliation')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label
                    for="partner_school"
                    class="block text-sm font-medium text-slate-700"
                >
                    提携校
                </label>

                <input
                    id="partner_school"
                    name="partner_school"
                    type="text"
                    value="{{ old(
                        'partner_school',
                        $studentProfile?->partner_school,
                    ) }}"
                    class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2"
                >

                @error('partner_school')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label
                    for="class_group_id"
                    class="block text-sm font-medium text-slate-700"
                >
                    クラス
                    <span class="text-red-600">*</span>
                </label>

                <select
                    id="class_group_id"
                    name="class_group_id"
                    data-student-required
                    class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2"
                >
                    <option value="">
                        選択してください
                    </option>

                    @foreach ($classGroups as $classGroup)
                        <option
                            value="{{ $classGroup->id }}"
                            @selected(
                                (string) old(
                                    'class_group_id',
                                    $studentProfile?->class_group_id,
                                ) === (string) $classGroup->id
                            )
                        >
                            {{ $classGroup->class_name }}
                        </option>
                    @endforeach
                </select>

                @error('class_group_id')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label
                    for="student_status"
                    class="block text-sm font-medium text-slate-700"
                >
                    在籍状態
                    <span class="text-red-600">*</span>
                </label>

                <select
                    id="student_status"
                    name="student_status"
                    data-student-required
                    class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2"
                >
                    @foreach ($studentStatuses as $studentStatus)
                        <option
                            value="{{ $studentStatus->value }}"
                            @selected(
                                $selectedStudentStatus === $studentStatus->value
                            )
                        >
                            {{ $studentStatus->label() }}
                        </option>
                    @endforeach
                </select>

                @error('student_status')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>
        </div>
    </section>

    <div class="flex flex-wrap gap-3">
        <button
            type="submit"
            class="rounded-lg bg-slate-900 px-6 py-3 font-semibold text-white hover:bg-slate-700"
        >
            {{ $submitLabel }}
        </button>

        <a
            href="{{ route('admin.users.index') }}"
            class="rounded-lg border border-slate-300 px-6 py-3 font-semibold hover:bg-slate-50"
        >
            キャンセル
        </a>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const roleSelect = document.getElementById('role');
        const studentFields = document.getElementById('student-fields');

        if (!roleSelect || !studentFields) {
            return;
        }

        const studentRole = studentFields.dataset.studentRole;

        const requiredFields = studentFields.querySelectorAll(
            '[data-student-required]',
        );

        const synchronizeStudentFields = () => {
            const isStudent = roleSelect.value === studentRole;

            studentFields.hidden = !isStudent;

            requiredFields.forEach((field) => {
                field.required = isStudent;
            });
        };

        roleSelect.addEventListener(
            'change',
            synchronizeStudentFields,
        );

        synchronizeStudentFields();
    });
</script>