<details class="gw-panel gw-file-tool">
    <summary>複製・削除</summary>
    <form class="gw-form" method="POST" action="{{ route('google-workspace.drive.copy', $file->id) }}">
        @csrf
        <label>
            <span>複製後の名前</span>
            <input type="text" name="name" placeholder="未入力なら元の名前">
        </label>
        <label>
            <span>複製先フォルダID</span>
            <input type="text" name="parent_id">
        </label>
        <button class="gw-button gw-button--secondary" type="submit">複製</button>
    </form>
    @if ($file->canDelete)
        <form
            class="gw-danger-zone"
            method="POST"
            action="{{ route('google-workspace.drive.destroy', $file->id) }}"
            data-confirm-message="ファイルを完全削除します。元に戻せません。実行しますか？"
        >
            @csrf
            @method('DELETE')
            <button class="gw-button gw-button--danger" type="submit">完全削除</button>
        </form>
    @endif
</details>
