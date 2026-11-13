{{-- 登録画面と編集画面でロール別アカウント項目を共有し、権限変更時の入力漏れを防ぐ。 --}}
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
        <h2 class="text-lg font-bold lms-text-neutral-strong">
            アカウント情報
        </h2>

        <div class="mt-4 grid gap-5 md:grid-cols-2">
            <div>
                <label
                    for="name"
                    class="block text-sm font-medium lms-text-neutral-secondary"
                >
                    氏名
                    <span class="lms-text-danger">*</span>
                </label>

                <input class="lms-form-control mt-2 w-full rounded-lg border lms-border-neutral-default px-3 py-2"
                    id="name"
                    name="name"
                    type="text"
                    value="{{ \App\Support\OldInput::text('name', $user->name) }}"
                    required
                >

                @error('name')
                    <p class="mt-1 text-sm lms-text-danger">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label
                    for="email"
                    class="block text-sm font-medium lms-text-neutral-secondary"
                >
                    メールアドレス
                    <span class="lms-text-danger">*</span>
                </label>

                <input class="lms-form-control mt-2 w-full rounded-lg border lms-border-neutral-default px-3 py-2"
                    id="email"
                    name="email"
                    type="email"
                    value="{{ \App\Support\OldInput::text('email', $user->email) }}"
                    required
                >

                @error('email')
                    <p class="mt-1 text-sm lms-text-danger">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label
                    for="role"
                    class="block text-sm font-medium lms-text-neutral-secondary"
                >
                    権限（ロール）
                    <span class="lms-text-danger">*</span>
                </label>

                <select
                    id="role"
                    name="role"
                    required
                    class="mt-2 w-full rounded-lg border px-3 py-2 lms-form-control"
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
                    <p class="mt-1 text-sm lms-text-danger">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label
                    for="status"
                    class="block text-sm font-medium lms-text-neutral-secondary"
                >
                    利用状態
                    <span class="lms-text-danger">*</span>
                </label>

                <select
                    id="status"
                    name="status"
                    required
                    class="mt-2 w-full rounded-lg border px-3 py-2 lms-form-control"
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
                    <p class="mt-1 text-sm lms-text-danger">
                        {{ $message }}
                    </p>
                @enderror
            </div>
        </div>
    </section>

    <section class="border-t lms-border-neutral-subtle pt-6" aria-labelledby="user-password-heading">
        <h2 id="user-password-heading" class="text-lg font-bold lms-text-neutral-strong">
            パスワード
        </h2>

        <div id="user-password-help" class="mt-2 space-y-1 text-sm lms-text-neutral-muted">
            @if ($user->exists)
                <p>変更しない場合は空欄にしてください。</p>
            @endif
            <p>半角で12文字以上。大文字・小文字・数字・記号をすべて含めてください。半角スペースも使用できます。</p>
        </div>

        <div class="mt-4 grid gap-5 md:grid-cols-2">
            <div>
                <label
                    for="password"
                    class="block text-sm font-medium lms-text-neutral-secondary"
                >
                    パスワード

                    @unless ($user->exists)
                        <span class="lms-text-danger">*</span>
                    @endunless
                </label>

                <x-password-visibility input-id="password">
                    <input
                        id="password"
                        aria-describedby="user-password-help"
                        name="password"
                        type="password"
                        @required(! $user->exists)
                        autocomplete="new-password"
                        class="
                            lms-form-control lms-bg-surface w-full rounded-lg border
                            lms-border-neutral-default px-3 py-2 pr-12
                        "
                    >
                </x-password-visibility>

                @error('password')
                    <p class="mt-1 text-sm lms-text-danger">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label
                    for="password_confirmation"
                    class="block text-sm font-medium lms-text-neutral-secondary"
                >
                    パスワード確認

                    @unless ($user->exists)
                        <span class="lms-text-danger">*</span>
                    @endunless
                </label>

                <x-password-visibility input-id="password_confirmation" label="確認用パスワード">
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        @required(! $user->exists)
                        autocomplete="new-password"
                        class="
                            lms-form-control lms-bg-surface w-full rounded-lg border
                            lms-border-neutral-default pl-3 pr-12 py-2
                        "
                    >
                </x-password-visibility>
            </div>
        </div>
    </section>

    <section
        id="student-fields"
        data-student-role="{{ \App\Enums\UserRole::Student->value }}"
        class="rounded-xl border lms-border-neutral-subtle lms-bg-neutral-subtle p-5"
        @if ($selectedRole !== \App\Enums\UserRole::Student->value)
            hidden
        @endif
    >
        <h2 class="text-lg font-bold lms-text-neutral-strong">
            生徒情報
        </h2>

        <p class="mt-1 text-sm lms-text-neutral-subtle">
            生徒として登録するための情報を入力してください。
        </p>

        <div class="mt-4 grid gap-5 md:grid-cols-2">
            <div>
                <label
                    for="student_no"
                    class="block text-sm font-medium lms-text-neutral-secondary"
                >
                    生徒番号
                </label>

                <input
                class="lms-form-control mt-2 w-full rounded-lg border lms-border-neutral-default lms-bg-surface px-3
                    py-2"
                    id="student_no"
                    name="student_no"
                    type="text"
                    value="{{ \App\Support\OldInput::text(
                        'student_no',
                        $studentProfile?->student_no,
                    ) }}"
                >

                @error('student_no')
                    <p class="mt-1 text-sm lms-text-danger">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label
                    for="student_name"
                    class="block text-sm font-medium lms-text-neutral-secondary"
                >
                    生徒氏名
                    <span class="lms-text-danger">*</span>
                </label>

                <input
                class="lms-form-control mt-2 w-full rounded-lg border lms-border-neutral-default lms-bg-surface px-3
                    py-2"
                    id="student_name"
                    name="student_name"
                    type="text"
                    value="{{ \App\Support\OldInput::text(
                        'student_name',
                        $studentProfile?->student_name ?? $user->name,
                    ) }}"
                    data-student-required
                >

                @error('student_name')
                    <p class="mt-1 text-sm lms-text-danger">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label
                    for="grade"
                    class="block text-sm font-medium lms-text-neutral-secondary"
                >
                    学年
                    <span class="lms-text-danger">*</span>
                </label>

                <select
                    id="grade"
                    name="grade"
                    data-student-required
                    class="mt-2 w-full rounded-lg border px-3 py-2 lms-form-control"
                >
                    <option value="">
                        選択してください
                    </option>

                    @foreach ($grades as $grade)
                        <option
                            value="{{ $grade->value }}"
                            @selected(
                                old(
                                    'grade',
                                    $studentProfile?->grade?->value,
                                ) === $grade->value
                            )
                        >
                            {{ $grade->label() }}
                        </option>
                    @endforeach
                </select>

                @error('grade')
                    <p class="mt-1 text-sm lms-text-danger">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label
                    for="affiliation"
                    class="block text-sm font-medium lms-text-neutral-secondary"
                >
                    所属
                </label>

                <input
                class="lms-form-control mt-2 w-full rounded-lg border lms-border-neutral-default lms-bg-surface px-3
                    py-2"
                    id="affiliation"
                    name="affiliation"
                    type="text"
                    value="{{ \App\Support\OldInput::text(
                        'affiliation',
                        $studentProfile?->affiliation,
                    ) }}"
                >

                @error('affiliation')
                    <p class="mt-1 text-sm lms-text-danger">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label
                    for="partner_school"
                    class="block text-sm font-medium lms-text-neutral-secondary"
                >
                    提携校
                </label>

                <input
                class="lms-form-control mt-2 w-full rounded-lg border lms-border-neutral-default lms-bg-surface px-3
                    py-2"
                    id="partner_school"
                    name="partner_school"
                    type="text"
                    value="{{ \App\Support\OldInput::text(
                        'partner_school',
                        $studentProfile?->partner_school,
                    ) }}"
                >

                @error('partner_school')
                    <p class="mt-1 text-sm lms-text-danger">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label
                    for="class_group_id"
                    class="block text-sm font-medium lms-text-neutral-secondary"
                >
                    クラス
                    <span class="lms-text-danger">*</span>
                </label>

                <select
                    id="class_group_id"
                    name="class_group_id"
                    data-student-required
                    class="mt-2 w-full rounded-lg border px-3 py-2 lms-form-control"
                >
                    <option value="">
                        選択してください
                    </option>

                    @foreach ($classGroups as $classGroup)
                        <option
                            value="{{ $classGroup->id }}"
                            @selected(
                                \App\Support\OldInput::text(
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
                    <p class="mt-1 text-sm lms-text-danger">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label
                    for="student_status"
                    class="block text-sm font-medium lms-text-neutral-secondary"
                >
                    在籍状態
                    <span class="lms-text-danger">*</span>
                </label>

                <select
                    id="student_status"
                    name="student_status"
                    data-student-required
                    class="mt-2 w-full rounded-lg border px-3 py-2 lms-form-control"
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
                    <p class="mt-1 text-sm lms-text-danger">
                        {{ $message }}
                    </p>
                @enderror
            </div>
        </div>
    </section>

    <div class="flex flex-wrap justify-end gap-3 border-t lms-border-neutral-subtle pt-5">
        <button
            type="submit"
            class="rounded-lg px-6 py-3 font-semibold lms-button-primary"
        >
            {{ $submitLabel }}
        </button>

        <a
            href="{{ route('admin.users.index') }}"
            class="rounded-lg border lms-border-neutral-default px-6 py-3 font-semibold lms-hover-bg-neutral-subtle"
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
