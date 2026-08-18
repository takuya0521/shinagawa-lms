<form
    class="gw-chat-compose"
    method="POST"
    action="{{ route('google-workspace.chat.messages.store', $space->id) }}"
    enctype="multipart/form-data"
>
    @csrf
    <label class="sr-only" for="chat-message-text">メッセージ</label>
    <textarea
        id="chat-message-text"
        name="text"
        rows="1"
        placeholder="{{ $space->displayName }} にメッセージを送信"
        maxlength="40000"
    ></textarea>
    <label class="gw-icon-button" title="ファイルを添付">
        <x-google-icon name="attach" :size="20" />
        <input class="sr-only" type="file" name="attachment">
    </label>
    <button class="gw-icon-button gw-icon-button--send" type="submit" aria-label="メッセージを送信">
        <x-google-icon name="send" :size="20" />
    </button>
</form>
