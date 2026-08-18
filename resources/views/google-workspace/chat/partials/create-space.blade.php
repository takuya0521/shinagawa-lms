<details class="gw-chat-create" data-disclosure>
    <summary class="gw-new-button">
        <x-google-icon name="plus" :size="23" />
        <span>チャットを新規作成</span>
    </summary>
    <div class="gw-chat-create__panel">
        <form class="gw-form gw-form--compact" method="POST" action="{{ route('google-workspace.chat.spaces.store') }}">
            @csrf
            <label>
                <span>会話の種類</span>
                <select name="space_type" required>
                    <option value="SPACE" @selected(old('space_type', 'SPACE') === 'SPACE')>名前付きスペース</option>
                    <option value="GROUP_CHAT" @selected(old('space_type') === 'GROUP_CHAT')>グループチャット</option>
                    <option value="DIRECT_MESSAGE" @selected(old('space_type') === 'DIRECT_MESSAGE')>ダイレクトメッセージ</option>
                </select>
            </label>
            <label>
            <span>スペース名</span>
            <input type="text" name="display_name" value="{{ old('display_name') }}" maxlength="128">
            </label>
            <label>
            <span>説明</span>
            <textarea name="description" rows="3" maxlength="5000">{{ old('description') }}</textarea>
            </label>
            <label>
                <span>初期メンバー</span>
                <textarea
                    name="members"
                    rows="3"
                    maxlength="10000"
                    placeholder="メールアドレスを改行またはカンマ区切りで入力"
                >{{ old('members') }}</textarea>
            </label>
            <button class="gw-button gw-button--primary" type="submit">作成</button>
        </form>
    </div>
</details>
