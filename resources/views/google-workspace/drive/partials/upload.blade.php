<details class="gw-new-menu__section">
    <summary>
        <x-google-icon name="upload" :size="20" />
        <span>ファイルをアップロード</span>
    </summary>
    <form
        class="gw-form gw-form--compact"
        method="POST"
        action="{{ route('google-workspace.drive.upload') }}"
        enctype="multipart/form-data"
    >
        @csrf
        <input type="hidden" name="parent_id" value="{{ $writeParentId }}">
        <label>
            <span>アップロードファイル</span>
            <input type="file" name="file" required>
        </label>
        <button class="gw-button gw-button--primary" type="submit">アップロード</button>
    </form>
</details>
