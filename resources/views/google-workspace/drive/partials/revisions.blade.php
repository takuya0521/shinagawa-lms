<section class="gw-section">
    <div class="gw-section__header"><h2>版履歴</h2><span class="gw-count">{{ $revisions->count() }}件</span></div>
    <ul class="gw-list gw-revision-list">
        @forelse ($revisions as $revision)
            <li class="gw-revision-item">
                <div class="gw-revision-item__body">
                    <strong>版 {{ $loop->iteration }}</strong>
                    <span>
                        {{ $revision->modifiedAt?->format('Y/m/d H:i') ?? '日時なし' }}
                        ・ {{ $revision->modifierName ?? '更新者不明' }}
                    </span>
                </div>
            </li>
        @empty
            <li class="gw-empty">版履歴はありません。</li>
        @endforelse
    </ul>
</section>
