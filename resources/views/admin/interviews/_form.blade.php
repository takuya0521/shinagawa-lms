{-- 登録画面と編集画面で面談記録項目を共有し、記録内容の粒度を統一する。 --}
@php
    $editing = $interviewRecord->exists;
    $currentStudentId = (string) old(
        'student_id',
        $selectedStudentId ?? $interviewRecord->student_id,
    );
    $currentTeacherId = (string) old(
        'teacher_id',
        $selectedTeacherId ?? $interviewRecord->teacher_id,
    );
@endphp

<div class="space-y-6">
    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label for="student_id" class="block text-sm font-semibold text-slate-700">
                生徒
                <span class="text-red-600">*</span>
            </label>

            <select
                id="student_id"
                name="student_id"
                class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2"
                required
            >
                <option value="">選択してください</option>

                @foreach ($students as $student)
                    <option
                        value="{{ $student->id }}"
                        @selected($currentStudentId === (string) $student->id)
                    >
                        {{ $student->student_no ?? '番号未設定' }} / {{ $student->student_name }} / {{ $student->grade->label() }} / {{ $student->classGroup->class_name }}
                    </option>
                @endforeach
            </select>

            @error('student_id')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="teacher_id" class="block text-sm font-semibold text-slate-700">
                担当教員
            </label>

            <select
                id="teacher_id"
                name="teacher_id"
                class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2"
            >
                <option value="">未設定</option>

                @foreach ($teachers as $teacher)
                    <option
                        value="{{ $teacher->id }}"
                        @selected($currentTeacherId === (string) $teacher->id)
                    >
                        {{ $teacher->user->name }} / {{ $teacher->user->email }}
                    </option>
                @endforeach
            </select>

            @error('teacher_id')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label for="interview_date" class="block text-sm font-semibold text-slate-700">
                面談日
                <span class="text-red-600">*</span>
            </label>

            <input
                id="interview_date"
                name="interview_date"
                type="date"
                value="{{ old(
                    'interview_date',
                    $editing
                        ? $interviewRecord->interview_date->format('Y-m-d')
                        : now()->format('Y-m-d'),
                ) }}"
                class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2"
                required
            >

            @error('interview_date')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="interview_type" class="block text-sm font-semibold text-slate-700">
                面談種別
            </label>

            <input
                id="interview_type"
                name="interview_type"
                type="text"
                maxlength="30"
                list="interview-type-options"
                value="{{ old('interview_type', $interviewRecord->interview_type) }}"
                placeholder="例：定期面談、希望面談、進路面談"
                class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2"
            >

            <datalist id="interview-type-options">
                @foreach ($interviewTypes as $interviewType)
                    <option value="{{ $interviewType }}"></option>
                @endforeach
            </datalist>

            @error('interview_type')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label for="memo" class="block text-sm font-semibold text-slate-700">
            面談メモ
        </label>

        <textarea
            id="memo"
            name="memo"
            rows="8"
            maxlength="10000"
            class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2"
            placeholder="LMSに保持する簡易メモを入力してください。詳細議事録や添付資料はGoogle Driveで管理します。"
        >{{ old('memo', $interviewRecord->memo) }}</textarea>

        @error('memo')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="next_action" class="block text-sm font-semibold text-slate-700">
            次回対応
        </label>

        <textarea
            id="next_action"
            name="next_action"
            rows="5"
            maxlength="10000"
            class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2"
            placeholder="次回までの確認事項、対応担当、期限などを入力してください。"
        >{{ old('next_action', $interviewRecord->next_action) }}</textarea>

        @error('next_action')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label for="drive_url" class="block text-sm font-semibold text-slate-700">
                Google Drive URL
            </label>

            <input
                id="drive_url"
                name="drive_url"
                type="url"
                maxlength="500"
                value="{{ old('drive_url', $interviewRecord->drive_url) }}"
                placeholder="https://drive.google.com/..."
                class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2"
            >

            <p class="mt-2 text-xs text-slate-500">
                詳細議事録、添付資料、共有フォルダへのHTTPSリンクを登録します。
            </p>

            @error('drive_url')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="meet_url" class="block text-sm font-semibold text-slate-700">
                Google Meet URL
            </label>

            <input
                id="meet_url"
                name="meet_url"
                type="url"
                maxlength="500"
                value="{{ old('meet_url', $interviewRecord->meet_url) }}"
                placeholder="https://meet.google.com/..."
                class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2"
            >

            <p class="mt-2 text-xs text-slate-500">
                オンライン面談を行う場合のHTTPSリンクを登録します。
            </p>

            @error('meet_url')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:justify-end">
        <a
            href="{{ route('admin.interviews.index') }}"
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
