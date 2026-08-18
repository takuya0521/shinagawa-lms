<details class="gw-message-menu" data-disclosure>
    <summary class="gw-icon-button" aria-label="メッセージ操作"><x-google-icon name="more" :size="18" /></summary>
    <div class="gw-message-menu__panel">
        <form method="POST" action="{{ route('google-workspace.chat.reactions.store', [$space->id, $message->id]) }}">
            @csrf
            <input type="hidden" name="emoji" value="👍">
            <button type="submit"><x-google-icon name="emoji" :size="17" />リアクション</button>
        </form>
        <details class="gw-message-reply">
            <summary>スレッドへ返信</summary>
            <form method="POST" action="{{ route('google-workspace.chat.messages.store', $space->id) }}">
                @csrf
                <input type="hidden" name="thread_name" value="{{ $message->threadName }}">
                <textarea name="text" rows="2" placeholder="返信を入力" required maxlength="40000"></textarea>
                <button type="submit">返信</button>
            </form>
        </details>
        @if (in_array($message->resourceName, $pinnedMessageNames, true))
            <form
                method="POST"
                action="{{ route('google-workspace.chat.messages.unpin', [$space->id, $message->id]) }}"
            >@csrf @method('DELETE')<button type="submit">固定を解除</button></form>
        @else
            <form
                method="POST"
                action="{{ route('google-workspace.chat.messages.pin', [$space->id, $message->id]) }}"
            >@csrf<button type="submit">固定する</button></form>
        @endif
        @include('google-workspace.chat.partials.message-editor')
    </div>
</details>
