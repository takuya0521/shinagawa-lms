{-- 登録画面と編集画面で教員情報とアカウント情報を共有し、更新項目の差異を防ぐ。 --}
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
        <h2 class="text-lg font-bold text-slate-900">
            教員情報
        </h2>

        <p class="mt-1 text-sm text-slate-600">
            教員として管理する情報を入力します。
        </p>

        <div class="mt-5 grid gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <label
                    for="subject_notes"
                    class="block text-sm font-medium text-slate-700"
                >
                    担当科目メモ
                </label>

                <textarea
                    id="subject_notes"
                    name="subject_notes"
                    rows="6"
                    maxlength="2000"
                    placeholder="例：英語、英会話、検定対策を担当"
                    class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
                >{{ old('subject_notes', $teacher->subject_notes) }}</textarea>

                <p class="mt-1 text-xs text-slate-500">
                    担当科目、担当範囲、授業上の補足を入力できます。
                </p>

                @error('subject_notes')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label
                    for="teacher_status"
                    class="block text-sm font-medium text-slate-700"
                >
                    教員状態
                    <span class="text-red-600">*</span>
                </label>

                <select
                    id="teacher_status"
                    name="teacher_status"
                    required
                    class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
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
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>
        </div>
    </section>

    <section class="border-t border-slate-200 pt-8">
        <h2 class="text-lg font-bold text-slate-900">
            ログインアカウント
        </h2>

        <p class="mt-1 text-sm text-slate-600">
            教員がLMSへログインするための情報を入力します。
        </p>

        <div class="mt-5 grid gap-5 md:grid-cols-2">
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
                    for="status"
                    class="block text-sm font-medium text-slate-700"
                >
                    アカウント利用状態
                    <span class="text-red-600">*</span>
                </label>

                <select
                    id="status"
                    name="status"
                    required
                    class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
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

                    @unless ($user->exists)
                        <span class="text-red-600">*</span>
                    @endunless
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

    <div class="flex flex-wrap gap-3 border-t border-slate-200 pt-6">
        <button
            type="submit"
            class="rounded-lg bg-slate-900 px-6 py-3 font-semibold text-white hover:bg-slate-700"
        >
            {{ $submitLabel }}
        </button>

        <a
            href="{{ $cancelUrl }}"
            class="rounded-lg border border-slate-300 px-6 py-3 font-semibold hover:bg-slate-50"
        >
            キャンセル
        </a>
    </div>
</div>