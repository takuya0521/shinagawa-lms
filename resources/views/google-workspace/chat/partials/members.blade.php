<section class="gw-drawer-section">
    <form
        class="gw-form gw-form--compact"
        method="POST"
        action="{{ route('google-workspace.chat.members.store', $space->id) }}"
    >
        @csrf
        <label><span>メンバーを追加</span><input type="email" name="email" placeholder="メールアドレス" required></label>
        <button class="gw-button gw-button--primary" type="submit">追加・招待</button>
    </form>
    <div class="gw-member-list">
        @forelse ($memberships as $membership)
            <article>
                <span
                    class="gw-chat-space-avatar"
                    aria-hidden="true"
                >{{ \Illuminate\Support\Str::substr($membership->displayName, 0, 1) }}</span>
                <div>
                <strong>{{ $membership->displayName }}</strong>
                <small>{{ $membership->email ?? $membership->memberName }} · {{ $membership->roleLabel() }}</small>
                </div>
                @if ($space->canManageMembers && $membership->role !== 'ROLE_MANAGER')
                    <form
                        method="POST"
                        action="{{ route('google-workspace.chat.members.destroy', [$space->id, $membership->id]) }}"
                    >
                        @csrf
                        @method('DELETE')
                        <button
                            class="gw-icon-button"
                            type="submit"
                            aria-label="{{ $membership->displayName }}を削除"
                        ><x-google-icon name="close" :size="18" /></button>
                    </form>
                @endif
            </article>
        @empty
            <p class="gw-empty">メンバーを取得できませんでした。</p>
        @endforelse
    </div>
</section>
