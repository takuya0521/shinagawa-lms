<details class="gw-new-menu__section">
    <summary>
        <x-google-icon name="folder" :size="20" />
        <span>新しい項目</span>
    </summary>
    <form class="gw-form gw-form--compact" method="POST" action="{{ route('google-workspace.drive.store') }}">
        @csrf
        <input type="hidden" name="parent_id" value="{{ $writeParentId }}">
        <label>
            <span>名前</span>
            <input type="text" name="name" value="{{ old('name') }}" required maxlength="255">
        </label>
        <label>
            <span>種類</span>
            <select name="type" required>
                <option value="folder">フォルダ</option>
                <option value="document">Google ドキュメント</option>
                <option value="spreadsheet">Google スプレッドシート</option>
                <option value="presentation">Google スライド</option>
            </select>
        </label>
        <button class="gw-button gw-button--primary" type="submit">作成</button>
    </form>
</details>
