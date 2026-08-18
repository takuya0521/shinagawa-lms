<nav class="gw-chat-spaces" aria-label="参加中スペース">
    <h3>チャット</h3>
    @forelse ($spaces as $chatSpace)
        <a
            href="{{ route('google-workspace.chat.show', $chatSpace->id) }}"
            @class(['is-active' => isset($space) && $space->id === $chatSpace->id])
            @if (isset($space) && $space->id === $chatSpace->id) aria-current="page" @endif
        >
            <span
                class="gw-chat-space-avatar"
                aria-hidden="true"
            >{{ \Illuminate\Support\Str::substr($chatSpace->displayName, 0, 1) }}</span>
            <span class="gw-chat-space-copy">
                <strong>{{ $chatSpace->displayName }}</strong>
                <small>{{ $chatSpace->description ?? $chatSpace->typeLabel() }}</small>
            </span>
        </a>
    @empty
        <p class="gw-chat-spaces__empty">参加中のチャットはありません。</p>
    @endforelse
</nav>
