{{-- 登録画面と編集画面で公開範囲の入力規則を共有し、対象設定の不整合を防ぐ。 --}}
@php
    $editing = $externalLink->exists;
    $selectedScopeType = old(
        'scope_type',
        $editing
            ? $externalLink->scope_type->value
            : \App\Enums\ExternalLinkScopeType::Global->value,
    );
    $selectedScopeId = (int) old('scope_id', $externalLink->scope_id ?? 0);
@endphp

<div class="space-y-6">
    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label for="link_type" class="block text-sm font-semibold lms-text-neutral-secondary">
                リンク種別 <span class="lms-text-danger">*</span>
            </label>
            <select
                id="link_type"
                name="link_type"
                class="mt-2 block w-full rounded-lg border px-3 py-2 lms-form-control"
                required
            >
                @foreach ($linkTypes as $linkType)
                    <option
                        value="{{ $linkType->value }}"
                        @selected(old('link_type', $externalLink->link_type?->value) === $linkType->value)
                    >
                        {{ $linkType->label() }}
                    </option>
                @endforeach
            </select>
            @error('link_type')<p class="mt-2 text-sm lms-text-danger">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="link_name" class="block text-sm font-semibold lms-text-neutral-secondary">
                リンク名 <span class="lms-text-danger">*</span>
            </label>
            <input
                class="lms-form-control mt-2 block w-full rounded-lg border lms-border-neutral-default px-3 py-2"
                id="link_name"
                name="link_name"
                type="text"
                maxlength="100"
                value="{{ old('link_name', $externalLink->link_name) }}"
                required
            >
            @error('link_name')<p class="mt-2 text-sm lms-text-danger">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label for="url" class="block text-sm font-semibold lms-text-neutral-secondary">
            URL <span class="lms-text-danger">*</span>
        </label>
        <input
            class="lms-form-control mt-2 block w-full rounded-lg border lms-border-neutral-default px-3 py-2"
            id="url"
            name="url"
            type="url"
            maxlength="500"
            value="{{ old('url', $externalLink->url) }}"
            placeholder="https://..."
            required
        >
        <p class="mt-2 text-sm lms-text-neutral-muted">HTTPSのURLのみ登録できます。</p>
        @error('url')<p class="mt-2 text-sm lms-text-danger">{{ $message }}</p>@enderror
    </div>

    <div class="grid gap-6 md:grid-cols-3">
        <div>
            <label for="scope_type" class="block text-sm font-semibold lms-text-neutral-secondary">
                公開範囲 <span class="lms-text-danger">*</span>
            </label>
            <select
                id="scope_type"
                name="scope_type"
                class="mt-2 block w-full rounded-lg border px-3 py-2 lms-form-control"
                required
            >
                @foreach ($scopeTypes as $scopeType)
                    <option
                        value="{{ $scopeType->value }}"
                        @selected($selectedScopeType === $scopeType->value)
                    >{{ $scopeType->label() }}</option>
                @endforeach
            </select>
            @error('scope_type')<p class="mt-2 text-sm lms-text-danger">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="display_order" class="block text-sm font-semibold lms-text-neutral-secondary">
                表示順 <span class="lms-text-danger">*</span>
            </label>
            <input
                class="lms-form-control mt-2 block w-full rounded-lg border lms-border-neutral-default px-3 py-2"
                id="display_order"
                name="display_order"
                type="number"
                min="0"
                max="9999"
                value="{{ old('display_order', $externalLink->display_order ?? 0) }}"
                required
            >
            @error('display_order')<p class="mt-2 text-sm lms-text-danger">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="status" class="block text-sm font-semibold lms-text-neutral-secondary">
                状態 <span class="lms-text-danger">*</span>
            </label>
            <select
                id="status"
                name="status"
                class="mt-2 block w-full rounded-lg border px-3 py-2 lms-form-control"
                required
            >
                @foreach ($statuses as $status)
                    <option
                        value="{{ $status->value }}"
                        @selected(old('status', $externalLink->status?->value ??
                        \App\Enums\MasterStatus::Active->value) === $status->value)
                    >{{ $status->label() }}</option>
                @endforeach
            </select>
            @error('status')<p class="mt-2 text-sm lms-text-danger">{{ $message }}</p>@enderror
        </div>
    </div>

    <section class="rounded-xl border lms-border-neutral-subtle p-5">
        <h2 class="font-bold lms-text-neutral-strong">公開対象の指定</h2>
        <p class="mt-2 text-sm lms-text-neutral-muted">
            「公開範囲」で選んだ種類に対応する対象だけが保存されます。全体を選んだ場合、対象選択は不要です。
        </p>

        <div class="mt-5 grid gap-5 md:grid-cols-2">
            <div>
                <label for="role_scope_id" class="block text-sm font-semibold lms-text-neutral-secondary">ロール対象</label>
                <select
                    id="role_scope_id"
                    name="role_scope_id"
                    class="mt-2 block w-full rounded-lg border px-3 py-2 lms-form-control"
                >
                    <option value="">選択してください</option>
                    @foreach ($roles as $role)
                        <option
                            value="{{ $role->scopeId() }}"
                            @selected((int) old('role_scope_id', $selectedScopeType === 'role' ? $selectedScopeId : 0)
                            === $role->scopeId())
                        >{{ $role->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label
                    for="class_group_scope_id"
                    class="block text-sm font-semibold lms-text-neutral-secondary"
                >クラス対象</label>
                <select
                    id="class_group_scope_id"
                    name="class_group_scope_id"
                    class="mt-2 block w-full rounded-lg border px-3 py-2 lms-form-control"
                >
                    <option value="">選択してください</option>
                    @foreach ($classGroups as $classGroup)
                        <option
                            value="{{ $classGroup->id }}"
                            @selected((int) old('class_group_scope_id', $selectedScopeType === 'class_group' ?
                            $selectedScopeId : 0) === $classGroup->id)
                        >{{ $classGroup->class_code }} / {{ $classGroup->class_name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="course_scope_id" class="block text-sm font-semibold lms-text-neutral-secondary">授業対象</label>
                <select
                    id="course_scope_id"
                    name="course_scope_id"
                    class="mt-2 block w-full rounded-lg border px-3 py-2 lms-form-control"
                >
                    <option value="">選択してください</option>
                    @foreach ($courses as $course)
                        <option
                            value="{{ $course->id }}"
                            @selected((int) old('course_scope_id', $selectedScopeType === 'course' ? $selectedScopeId :
                            0) === $course->id)
                        >{{ $course->academic_year }} / {{ $course->course_name }} / {{ $course->classGroup->class_name
                        }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label
                    for="student_scope_id"
                    class="block text-sm font-semibold lms-text-neutral-secondary"
                >生徒対象</label>
                <select
                    id="student_scope_id"
                    name="student_scope_id"
                    class="mt-2 block w-full rounded-lg border px-3 py-2 lms-form-control"
                >
                    <option value="">選択してください</option>
                    @foreach ($students as $student)
                        <option
                            value="{{ $student->id }}"
                            @selected((int) old('student_scope_id', $selectedScopeType === 'student' ? $selectedScopeId
                            : 0) === $student->id)
                        >{{ $student->student_no ?? '番号未設定' }} / {{ $student->student_name }} / {{
                        $student->classGroup->class_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @error('scope_id')<p class="mt-3 text-sm lms-text-danger">{{ $message }}</p>@enderror
    </section>

    <div class="flex flex-col-reverse gap-3 border-t lms-border-neutral-subtle pt-6 sm:flex-row sm:justify-end">
        <a
            href="{{ route('admin.external-links.index') }}"
            class="rounded-lg border lms-border-neutral-default px-5 py-3 text-center font-semibold
                lms-hover-bg-neutral-subtle"
        >キャンセル</a>
        <button
            type="submit"
            class="rounded-lg px-5 py-3 font-semibold lms-button-primary"
        >{{ $editing ? '更新する' : '登録する' }}</button>
    </div>
</div>
