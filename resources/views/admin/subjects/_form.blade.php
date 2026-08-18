{{-- 登録画面と編集画面で科目マスタ項目を共有し、コード体系と状態管理を統一する。 --}}
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
            class="block text-sm font-medium lms-text-neutral-secondary"
        >
            科目コード
            <span class="lms-text-danger">*</span>
        </label>

        <input
        class="lms-form-control mt-1 block w-full rounded-md lms-border-neutral-default shadow-sm lms-focus-border
            lms-focus-ring"
            id="subject_code"
            name="subject_code"
            type="text"
            maxlength="30"
            value="{{ old(
                'subject_code',
                $editing ? $subject->subject_code : '',
            ) }}"
            autocomplete="off"
            required
        >

        <p class="mt-1 text-sm lms-text-neutral-muted">
            半角英大文字、数字、ハイフン、アンダースコアを使用できます。
        </p>

        @error('subject_code')
            <p class="mt-2 text-sm lms-text-danger">
                {{ $message }}
            </p>
        @enderror
    </div>

    <div>
        <label
            for="subject_name"
            class="block text-sm font-medium lms-text-neutral-secondary"
        >
            科目名
            <span class="lms-text-danger">*</span>
        </label>

        <input
        class="lms-form-control mt-1 block w-full rounded-md lms-border-neutral-default shadow-sm lms-focus-border
            lms-focus-ring"
            id="subject_name"
            name="subject_name"
            type="text"
            maxlength="100"
            value="{{ old(
                'subject_name',
                $editing ? $subject->subject_name : '',
            ) }}"
            required
        >

        @error('subject_name')
            <p class="mt-2 text-sm lms-text-danger">
                {{ $message }}
            </p>
        @enderror
    </div>

    <div>
        <label
            for="status"
            class="block text-sm font-medium lms-text-neutral-secondary"
        >
            状態
            <span class="lms-text-danger">*</span>
        </label>

        <select
            id="status"
            name="status"
            class="mt-1 block w-full rounded-md shadow-sm lms-form-control"
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

    <div class="flex items-center justify-end gap-3">
        <a
            href="{{ route('admin.subjects.index') }}"
            class="inline-flex items-center rounded-md border lms-border-neutral-default lms-bg-surface px-4 py-2
                text-sm font-semibold lms-text-neutral-secondary shadow-sm lms-hover-bg-neutral-subtle"
        >
            キャンセル
        </a>

        <button
            type="submit"
            class="inline-flex items-center rounded-md px-4 py-2 text-sm font-semibold shadow-sm lms-button-primary"
        >
            {{ $editing ? '更新する' : '登録する' }}
        </button>
    </div>
</div>
