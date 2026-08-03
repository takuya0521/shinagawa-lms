{-- 登録画面と編集画面で入力項目と初期値処理を共有し、項目追加時の修正漏れを防ぐ。 --}
@php
    $editing = $announcement->exists;
    $selectedTargetValues = old('target_values', $selectedTargets);
    $selectedStatus = old(
        'status',
        $editing
            ? $announcement->status->value
            : \App\Enums\AnnouncementStatus::Draft->value,
    );
@endphp

<div class="space-y-6">
    <div>
        <label for="title" class="block text-sm font-semibold text-slate-700">
            タイトル <span class="text-red-600">*</span>
        </label>
        <input
            id="title"
            name="title"
            type="text"
            maxlength="255"
            value="{{ old('title', $announcement->title) }}"
            class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
            required
        >
        @error('title')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="body" class="block text-sm font-semibold text-slate-700">
            本文 <span class="text-red-600">*</span>
        </label>
        <textarea
            id="body"
            name="body"
            rows="10"
            maxlength="50000"
            class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
            required
        >{{ old('body', $announcement->body) }}</textarea>
        <p class="mt-2 text-sm text-slate-500">
            安全のためHTMLタグは保存時に除去され、改行を保ったテキストとして表示されます。
        </p>
        @error('body')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label for="notice_type" class="block text-sm font-semibold text-slate-700">
                お知らせ種別 <span class="text-red-600">*</span>
            </label>
            <select
                id="notice_type"
                name="notice_type"
                class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2 shadow-sm"
                required
            >
                @foreach ($noticeTypes as $noticeType)
                    <option
                        value="{{ $noticeType->value }}"
                        @selected(old('notice_type', $announcement->notice_type?->value ?? \App\Enums\AnnouncementNoticeType::School->value) === $noticeType->value)
                    >
                        {{ $noticeType->label() }}
                    </option>
                @endforeach
            </select>
            @error('notice_type')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="status" class="block text-sm font-semibold text-slate-700">
                公開状態 <span class="text-red-600">*</span>
            </label>
            <select
                id="status"
                name="status"
                class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2 shadow-sm"
                required
            >
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected($selectedStatus === $status->value)>
                        {{ $status->label() }}
                    </option>
                @endforeach
            </select>
            @error('status')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label for="publish_start_at" class="block text-sm font-semibold text-slate-700">
                掲載開始日時
            </label>
            <input
                id="publish_start_at"
                name="publish_start_at"
                type="datetime-local"
                value="{{ old('publish_start_at', $announcement->publish_start_at?->format('Y-m-d\TH:i')) }}"
                class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2 shadow-sm"
            >
            <p class="mt-2 text-sm text-slate-500">未入力の場合は即時公開として扱います。</p>
            @error('publish_start_at')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="publish_end_at" class="block text-sm font-semibold text-slate-700">
                掲載終了日時
            </label>
            <input
                id="publish_end_at"
                name="publish_end_at"
                type="datetime-local"
                value="{{ old('publish_end_at', $announcement->publish_end_at?->format('Y-m-d\TH:i')) }}"
                class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2 shadow-sm"
            >
            <p class="mt-2 text-sm text-slate-500">未入力の場合は無期限です。</p>
            @error('publish_end_at')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label class="flex items-center gap-3 rounded-lg border border-slate-300 px-4 py-3">
            <input
                type="hidden"
                name="is_important"
                value="0"
            >
            <input
                type="checkbox"
                name="is_important"
                value="1"
                class="rounded border-slate-300"
                @checked(old('is_important', $announcement->is_important) == 1)
            >
            <span>
                <span class="block font-semibold text-slate-800">重要なお知らせとして表示</span>
                <span class="block text-sm text-slate-500">一覧とダッシュボードで優先表示します。</span>
            </span>
        </label>
        @error('is_important')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <fieldset class="rounded-xl border border-slate-200 p-5">
        <legend class="px-2 text-sm font-semibold text-slate-700">
            公開対象 <span class="text-red-600">*</span>
        </legend>

        <div class="space-y-5">
            <label class="flex items-center gap-3">
                <input
                    type="checkbox"
                    name="target_values[]"
                    value="all"
                    class="rounded border-slate-300"
                    @checked(in_array('all', $selectedTargetValues, true))
                >
                <span class="font-semibold">全員</span>
            </label>

            <div>
                <p class="text-sm font-semibold text-slate-700">ロール</p>
                <div class="mt-2 flex flex-wrap gap-4">
                    @foreach ($roles as $role)
                        @php($targetValue = 'role:'.$role->value)
                        <label class="flex items-center gap-2">
                            <input
                                type="checkbox"
                                name="target_values[]"
                                value="{{ $targetValue }}"
                                class="rounded border-slate-300"
                                @checked(in_array($targetValue, $selectedTargetValues, true))
                            >
                            {{ $role->label() }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <p class="text-sm font-semibold text-slate-700">学年</p>
                <div class="mt-2 flex flex-wrap gap-4">
                    @foreach ($grades as $grade)
                        @php($targetValue = 'grade:'.$grade->value)
                        <label class="flex items-center gap-2">
                            <input
                                type="checkbox"
                                name="target_values[]"
                                value="{{ $targetValue }}"
                                class="rounded border-slate-300"
                                @checked(in_array($targetValue, $selectedTargetValues, true))
                            >
                            {{ $grade->label() }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <p class="text-sm font-semibold text-slate-700">クラス</p>
                <div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($classGroups as $classGroup)
                        @php($targetValue = 'class_group:'.$classGroup->id)
                        <label class="flex items-center gap-2">
                            <input
                                type="checkbox"
                                name="target_values[]"
                                value="{{ $targetValue }}"
                                class="rounded border-slate-300"
                                @checked(in_array($targetValue, $selectedTargetValues, true))
                            >
                            {{ $classGroup->class_code }} / {{ $classGroup->class_name }}
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <p class="mt-4 text-sm text-slate-500">
            「全員」を選択する場合、その他の対象は選択できません。
        </p>

        @error('target_values')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
        @error('target_values.*')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </fieldset>

    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:justify-end">
        <a
            href="{{ route('admin.announcements.index') }}"
            class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-5 py-3 font-semibold hover:bg-slate-50"
        >
            キャンセル
        </a>
        <button
            type="submit"
            class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-5 py-3 font-semibold text-white hover:bg-slate-700"
        >
            {{ $editing ? '更新する' : '登録する' }}
        </button>
    </div>
</div>
