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
        <h2 class="text-lg font-bold text-slate-900">
            生徒基本情報
        </h2>

        <p class="mt-1 text-sm text-slate-600">
            学校で管理する生徒情報を入力します。
        </p>

        <div class="mt-5 grid gap-5 md:grid-cols-2">
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
                    value="{{ old('student_no', $student->student_no) }}"
                    class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
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
                        $student->student_name,
                    ) }}"
                    required
                    class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
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
                    value="{{ old('grade', $student->grade) }}"
                    placeholder="例：1"
                    required
                    class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
                >

                @error('grade')
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
                    required
                    class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
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
                        $student->affiliation,
                    ) }}"
                    class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
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
                        $student->partner_school,
                    ) }}"
                    class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
                >

                @error('partner_school')
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
                    required
                    class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
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
            生徒がLMSへログインするための情報を入力します。
        </p>

        <div class="mt-5 grid gap-5 md:grid-cols-2">
            <div>
                <label
                    for="name"
                    class="block text-sm font-medium text-slate-700"
                >
                    アカウント氏名
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
                    利用状態
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