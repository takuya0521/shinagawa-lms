<details class="gw-panel gw-file-tool">
    <summary>ファイル情報を編集</summary>
    @if ($file->canEdit)
        <form class="gw-form" method="POST" action="{{ route('google-workspace.drive.update', $file->id) }}">
            @csrf
            @method('PATCH')
            <label>
                <span>名前</span>
                <input type="text" name="name" value="{{ old('name', $file->name) }}" required>
            </label>
            <label>
                <span>説明</span>
                <textarea name="description" rows="4">{{ old('description', $file->description) }}</textarea>
            </label>
            <label>
                <span>移動先フォルダID</span>
                <input type="text" name="parent_id" placeholder="ルートへ移動する場合は空欄">
            </label>
            <label class="gw-check">
                <input type="checkbox" name="starred" value="1" @checked($file->starred)>
                <span>スターを付ける</span>
            </label>
            <button class="gw-button gw-button--primary" type="submit">更新</button>
        </form>
    @else
        <p class="gw-meta">このファイルを編集する権限がありません。</p>
    @endif
</details>
