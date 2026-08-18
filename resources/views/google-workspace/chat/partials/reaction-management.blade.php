<div class="gw-reaction-management">
    <a
        href="{{
            route('google-workspace.chat.show', ['spaceId' => $space->id, 'reaction_message_id' => $message->id])
            }}#message-{{ $message->id
        }}"
    >リアクションを管理</a>
    @if ($selectedReactionMessageId === $message->id)
        <div class="gw-compact-list">
            @forelse ($selectedReactions as $reaction)
                <div>
                    <span>
                    <strong>{{ $reaction->emoji }}</strong>
                    <small>{{ $reaction->userName ?? 'Google Chat利用者' }}</small>
                    </span>
                    <form
                        method="POST"
                        action="{{
                            route('google-workspace.chat.reactions.destroy', [$space->id, $message->id,
                            basename($reaction->resourceName)])
                        }}"
                    >
                        @csrf
                        @method('DELETE')
                        <button type="submit">自分の反応を削除</button>
                    </form>
                </div>
            @empty
                <p class="gw-empty">個別リアクションはありません。</p>
            @endforelse
        </div>
    @endif
</div>
