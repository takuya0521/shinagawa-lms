{{-- 登録画面と編集画面で教員情報とアカウント情報を共有し、更新項目の差異を防ぐ。 --}}
@php
    $selectedTeacherStatus = old(
        'teacher_status',
        $teacher->status?->value
            ?? \App\Enums\MasterStatus::Active->value,
    );

    $selectedAccountStatus = old(
        'status',
        $user->status?->value
            ?? \App\Enums\UserStatus::Active->value,
    );
@endphp

<div class="space-y-8">
    <section>
        <h2 class="text-lg font-bold lms-text-neutral-strong">
            教員情報
        </h2>

        <p class="mt-1 text-sm lms-text-neutral-subtle">
            教員として管理する情報を入力します。
        </p>

        <div class="mt-5 grid gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <label
                    for="subject_notes"
                    class="block text-sm font-medium lms-text-neutral-secondary"
                >
                    担当科目メモ
                </label>

                <textarea
                    id="subject_notes"
                    name="subject_notes"
                    rows="6"
                    maxlength="2000"
                    placeholder="例：英語、英会話、検定対策を担当"
                    class="mt-2 w-full rounded-lg border px-3 py-2 lms-form-control"
                >{{ old('subject_notes', $teacher->subject_notes) }}</textarea>

                <p class="mt-1 text-xs lms-text-neutral-muted">
                    担当科目、担当範囲、授業上の補足を入力できます。
                </p>

                @error('subject_notes')
                    <p class="mt-1 text-sm lms-text-danger">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label
                    for="teacher_status"
                    class="block text-sm font-medium lms-text-neutral-secondary"
                >
                    教員状態
                    <span class="lms-text-danger">*</span>
                </label>

                <select
                    id="teacher_status"
                    name="teacher_status"
                    required
                    class="mt-2 w-full rounded-lg border px-3 py-2 lms-form-control"
                >
                    @foreach ($teacherStatuses as $teacherStatus)
                        <option
                            value="{{ $teacherStatus->value }}"
                            @selected(
                                $selectedTeacherStatus
                                === $teacherStatus->value
                            )
                        >
                            {{ $teacherStatus->label() }}
                        </option>
                    @endforeach
                </select>

                @error('teacher_status')
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
            教員がLMSへログインするための情報を入力します。
        </p>

        <div class="lms-account-login-fields mt-5">
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
                    アカウント利用状態
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
