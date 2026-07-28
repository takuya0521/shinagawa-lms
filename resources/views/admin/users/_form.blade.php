@php
    $editing = isset($user);
    $selectedRole = old(
        'role',
        $editing
            ? $user->role->value
            : \App\Enums\UserRole::Student->value,
    );
    $selectedStatus = old(
        'status',
        $editing
            ? $user->status->value
            : \App\Enums\UserStatus::Active->value,
    );
@endphp

<div class="grid gap-6">
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
            value="{{ old('name', $user->name ?? '') }}"
            maxlength="255"
            required
            autocomplete="name"
            class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
        >

        @error('name')
            <p class="mt-2 text-sm text-red-600">
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
            value="{{ old('email', $user->email ?? '') }}"
            maxlength="255"
            required
            autocomplete="email"
            class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
        >

        @error('email')
            <p class="mt-2 text-sm text-red-600">
                {{ $message }}
            </p>
        @enderror
    </div>

    <div class="grid gap-6 sm:grid-cols-2">
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
                <p class="mt-2 text-sm text-red-600">
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
                <p class="mt-2 text-sm text-red-600">
                    {{ $message }}
                </p>
            @enderror
        </div>
    </div>

    <div>
        <label
            for="password"
            class="block text-sm font-medium text-slate-700"
        >
            パスワード

            @unless ($editing)
                <span class="text-red-600">*</span>
            @endunless
        </label>

        <input
            id="password"
            name="password"
            type="password"
            autocomplete="new-password"
            @required(! $editing)
            class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
        >

        <p class="mt-2 text-sm text-slate-500">
            12文字以上で、英大文字・英小文字・数字・記号を含めてください。
            @if ($editing)
                変更しない場合は空欄にしてください。
            @endif
        </p>

        @error('password')
            <p class="mt-2 text-sm text-red-600">
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
            autocomplete="new-password"
            @required(! $editing)
            class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
        >
    </div>
</div>