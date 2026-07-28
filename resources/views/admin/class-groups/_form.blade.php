@php
    $selectedStatus = old(
        'status',
        $classGroup->status?->value
            ?? \App\Enums\MasterStatus::Active->value,
    );
@endphp

<div class="space-y-6">
    <div class="grid gap-5 md:grid-cols-2">
        <div>
            <label
                for="class_code"
                class="block text-sm font-medium text-slate-700"
            >
                クラスコード
                <span class="text-red-600">*</span>
            </label>

            <input
                id="class_code"
                name="class_code"
                type="text"
                value="{{ old(
                    'class_code',
                    $classGroup->class_code,
                ) }}"
                placeholder="例：AM"
                required
                maxlength="30"
                class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 uppercase"
            >

            <p class="mt-1 text-xs text-slate-500">
                半角英数字、ハイフン、アンダースコアを使用できます。
            </p>

            @error('class_code')
                <p class="mt-1 text-sm text-red-600">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div>
            <label
                for="class_name"
                class="block text-sm font-medium text-slate-700"
            >
                クラス名
                <span class="text-red-600">*</span>
            </label>

            <input
                id="class_name"
                name="class_name"
                type="text"
                value="{{ old(
                    'class_name',
                    $classGroup->class_name,
                ) }}"
                placeholder="例：午前クラス"
                required
                maxlength="100"
                class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
            >

            @error('class_name')
                <p class="mt-1 text-sm text-red-600">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div class="md:col-span-2">
            <label
                for="description"
                class="block text-sm font-medium text-slate-700"
            >
                説明
            </label>

            <textarea
                id="description"
                name="description"
                rows="4"
                maxlength="255"
                placeholder="クラスの説明を入力してください"
                class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
            >{{ old('description', $classGroup->description) }}</textarea>

            @error('description')
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
                状態
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
                        @selected(
                            $selectedStatus === $status->value
                        )
                    >
                        {{ $status->label() }}
                    </option>
                @endforeach
            </select>

            <p class="mt-1 text-xs text-slate-500">
                無効にすると、新しい生徒の所属先として選択できなくなります。
            </p>

            @error('status')
                <p class="mt-1 text-sm text-red-600">
                    {{ $message }}
                </p>
            @enderror
        </div>
    </div>

    <div class="flex flex-wrap gap-3 border-t border-slate-200 pt-6">
        <button
            type="submit"
            class="rounded-lg bg-slate-900 px-6 py-3 font-semibold text-white hover:bg-slate-700"
        >
            {{ $submitLabel }}
        </button>

        <a
            href="{{ route('admin.class-groups.index') }}"
            class="rounded-lg border border-slate-300 px-6 py-3 font-semibold hover:bg-slate-50"
        >
            キャンセル
        </a>
    </div>
</div>