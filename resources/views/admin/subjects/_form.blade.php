{-- 登録画面と編集画面で科目マスタ項目を共有し、コード体系と状態管理を統一する。 --}
@php
    $editing = isset($subject);

    $selectedStatus = old(
        'status',
        $editing
            ? $subject->status->value
            : \App\Enums\MasterStatus::Active->value,
    );
@endphp

<div class="space-y-6">
    <div>
        <label
            for="subject_code"
            class="block text-sm font-medium text-gray-700"
        >
            科目コード
            <span class="text-red-600">*</span>
        </label>

        <input
            id="subject_code"
            name="subject_code"
            type="text"
            maxlength="30"
            value="{{ old(
                'subject_code',
                $editing ? $subject->subject_code : '',
            ) }}"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            autocomplete="off"
            required
        >

        <p class="mt-1 text-sm text-gray-500">
            半角英大文字、数字、ハイフン、アンダースコアを使用できます。
        </p>

        @error('subject_code')
            <p class="mt-2 text-sm text-red-600">
                {{ $message }}
            </p>
        @enderror
    </div>

    <div>
        <label
            for="subject_name"
            class="block text-sm font-medium text-gray-700"
        >
            科目名
            <span class="text-red-600">*</span>
        </label>

        <input
            id="subject_name"
            name="subject_name"
            type="text"
            maxlength="100"
            value="{{ old(
                'subject_name',
                $editing ? $subject->subject_name : '',
            ) }}"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            required
        >

        @error('subject_name')
            <p class="mt-2 text-sm text-red-600">
                {{ $message }}
            </p>
        @enderror
    </div>

    <div>
        <label
            for="status"
            class="block text-sm font-medium text-gray-700"
        >
            状態
            <span class="text-red-600">*</span>
        </label>

        <select
            id="status"
            name="status"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
            <p class="mt-2 text-sm text-red-600">
                {{ $message }}
            </p>
        @enderror
    </div>

    <div class="flex items-center justify-end gap-3">
        <a
            href="{{ route('admin.subjects.index') }}"
            class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
        >
            キャンセル
        </a>

        <button
            type="submit"
            class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
        >
            {{ $editing ? '更新する' : '登録する' }}
        </button>
    </div>
</div>