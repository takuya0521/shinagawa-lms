<section class="gw-section">
    <div class="gw-section__header"><h2>コメント</h2><span class="gw-count">{{ $comments->count() }}件</span></div>
    <form
        class="gw-form gw-form--inline"
        method="POST"
        action="{{ route('google-workspace.drive.comments.store', $file->id) }}"
    >
        @csrf
        <input type="text" name="content" placeholder="コメントを入力" required maxlength="5000">
        <button class="gw-button gw-button--primary" type="submit">追加</button>
    </form>
    <div class="gw-comments">
        @forelse ($comments as $comment)
            <article class="gw-comment">
                <header>
                <strong>{{ $comment->authorName }}</strong>
                <time>{{ $comment->createdAt->format('Y/m/d H:i') }}</time>
                </header>
                <p>{{ $comment->content }}</p>
                @foreach ($comment->replies as $reply)
                    <div class="gw-reply"><strong>{{ $reply['author'] }}</strong><p>{{ $reply['content'] }}</p></div>
                @endforeach
                <form
                    class="gw-form gw-form--inline"
                    method="POST"
                    action="{{ route('google-workspace.drive.replies.store', [$file->id, $comment->id]) }}"
                >
                    @csrf
                    <input type="text" name="content" placeholder="返信を入力" required>
                    <button type="submit">返信</button>
                </form>
                <form
                    method="POST"
                    action="{{ route('google-workspace.drive.comments.destroy', [$file->id, $comment->id]) }}"
                >
                    @csrf
                    @method('DELETE')
                    <button class="gw-text-danger" type="submit">コメント削除</button>
                </form>
            </article>
        @empty
            <p class="gw-empty">コメントはありません。</p>
        @endforelse
    </div>
</section>
