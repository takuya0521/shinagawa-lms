<details class="gw-message-editor">
    <summary>編集・削除</summary>
    <form method="POST" action="{{ route('google-workspace.chat.messages.update', [$space->id, $message->id]) }}">
        @csrf
        @method('PATCH')
        <textarea name="text" rows="3" required maxlength="40000">{{ $message->text }}</textarea>
        <button type="submit">本文を更新</button>
    </form>
    <form
        method="POST"
        action="{{ route('google-workspace.chat.messages.destroy', [$space->id, $message->id]) }}"
        data-confirm-message="このメッセージを削除しますか？"
    >
        @csrf
        @method('DELETE')
        <label class="gw-check"><input type="checkbox" name="force" value="1"><span>スレッド返信も含める</span></label>
        <button class="gw-text-danger" type="submit">削除</button>
    </form>
</details>
