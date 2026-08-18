{{-- 登録画面と編集画面でアカウント情報と学籍情報を一体管理し、項目対応のずれを防ぐ。 --}}
@php
    $selectedAccountStatus = old(
        'status',
        $user->status?->value ?? \App\Enums\UserStatus::Active->value,
    );

    $selectedStudentStatus = old(
        'student_status',
        $student->status?->value
            ?? \App\Enums\StudentStatus::Active->value,
    );

    $selectedClassGroupId = old(
        'class_group_id',
        $student->class_group_id,
    );
@endphp

<div class="space-y-8">
    <section>
        <h2 class="text-lg font-bold lms-text-neutral-strong">
            生徒基本情報
        </h2>

        <p class="mt-1 text-sm lms-text-neutral-subtle">
            学校で管理する生徒情報を入力します。
        </p>

        <div class="mt-5 grid gap-5 md:grid-cols-2">
            <div>
                <label
                    for="student_no"
                    class="block text-sm font-medium lms-text-neutral-secondary"
                >
                    生徒番号
                </label>

                <input class="lms-form-control mt-2 w-full rounded-lg border lms-border-neutral-default px-3 py-2"
                    id="student_no"
                    name="student_no"
                    type="text"
                    value="{{ old('student_no', $student->student_no) }}"
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

                <input class="lms-form-control mt-2 w-full rounded-lg border lms-border-neutral-default px-3 py-2"
                    id="student_name"
                    name="student_name"
                    type="text"
                    value="{{ old(
                        'student_name',
                        $student->student_name,
                    ) }}"
                    required
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
                    required
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
                                    $student->grade?->value,
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
                    for="class_group_id"
                    class="block text-sm font-medium lms-text-neutral-secondary"
                >
                    クラス
                    <span class="lms-text-danger">*</span>
                </label>

                <select
                    id="class_group_id"
                    name="class_group_id"
                    required
                    class="mt-2 w-full rounded-lg border px-3 py-2 lms-form-control"
                >
                    <option value="">
                        選択してください
                    </option>

                    @foreach ($classGroups as $classGroup)
                        <option
                            value="{{ $classGroup->id }}"
                            @selected(
                                (string) $selectedClassGroupId
                                === (string) $classGroup->id
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
                    for="affiliation"
                    class="block text-sm font-medium lms-text-neutral-secondary"
                >
                    所属
                </label>

                <input class="lms-form-control mt-2 w-full rounded-lg border lms-border-neutral-default px-3 py-2"
                    id="affiliation"
                    name="affiliation"
                    type="text"
                    value="{{ old(
                        'affiliation',
                        $student->affiliation,
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

                <input class="lms-form-control mt-2 w-full rounded-lg border lms-border-neutral-default px-3 py-2"
                    id="partner_school"
                    name="partner_school"
                    type="text"
                    value="{{ old(
                        'partner_school',
                        $student->partner_school,
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
                    for="student_status"
                    class="block text-sm font-medium lms-text-neutral-secondary"
                >
                    在籍状態
                    <span class="lms-text-danger">*</span>
                </label>

                <select
                    id="student_status"
                    name="student_status"
                    required
                    class="mt-2 w-full rounded-lg border px-3 py-2 lms-form-control"
                >
                    @foreach ($studentStatuses as $studentStatus)
                        <option
                            value="{{ $studentStatus->value }}"
                            @selected(
                                $selectedStudentStatus
                                === $studentStatus->value
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

    <section class="border-t lms-border-neutral-subtle pt-8">
        <h2 class="text-lg font-bold lms-text-neutral-strong">
            ログインアカウント
        </h2>

        <p class="mt-1 text-sm lms-text-neutral-subtle">
            生徒がLMSへログインするための情報を入力します。
        </p>

        <div class="lms-account-login-fields mt-5">
            <div>
                <label
                    for="name"
                    class="block text-sm font-medium lms-text-neutral-secondary"
                >
                    アカウント氏名
                    <span class="lms-text-danger">*</span>
                </label>

                <input class="lms-form-control mt-2 w-full rounded-lg border lms-border-neutral-default px-3 py-2"
                    id="name"
                    name="name"
                    type="text"
                    value="{{ old('name', $user->name) }}"
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
                    value="{{ old('email', $user->email) }}"
                    required
                >

                @error('email')
                    <p class="mt-1 text-sm lms-text-danger">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div class="lms-account-login-fields__single">
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
                    @foreach ($accountStatuses as $accountStatus)
                        <option
                            value="{{ $accountStatus->value }}"
                            @selected(
                                $selectedAccountStatus
                                === $accountStatus->value
                            )
                        >
                            {{ $accountStatus->label() }}
                        </option>
                    @endforeach
                </select>

                @error('status')
                    <p class="mt-1 text-sm lms-text-danger">
                        {{ $message }}
                    </p>
                @enderror
            </div>

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

                <input
                    id="password"
                    name="password"
                    type="password"
                    @required(! $user->exists)
                    autocomplete="new-password"
                    class="
                        lms-form-control lms-bg-surface mt-2 w-full rounded-lg border
                        lms-border-neutral-default px-3 py-2
                    "
                >

                @if ($user->exists)
                    <p class="mt-1 text-xs lms-text-neutral-muted">
                        変更しない場合は空欄にしてください。
                    </p>
                @endif

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

                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    @required(! $user->exists)
                    autocomplete="new-password"
                    class="
                        lms-form-control lms-bg-surface mt-2 w-full rounded-lg border
                        lms-border-neutral-default px-3 py-2
                    "
                >
            </div>
        </div>
    </section>

    <div class="flex flex-wrap justify-center gap-3 border-t lms-border-neutral-subtle pt-6">
        <button
            type="submit"
            class="rounded-lg px-6 py-3 font-semibold lms-button-primary"
        >
            {{ $submitLabel }}
        </button>

        <a
            href="{{ $cancelUrl }}"
            class="rounded-lg border lms-border-neutral-default px-6 py-3 font-semibold lms-hover-bg-neutral-subtle"
        >
            キャンセル
        </a>
    </div>
</div>
