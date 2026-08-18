@if ($chatError !== null)
    <section class="gw-alert gw-alert--error" role="alert">{{ $chatError }}</section>
@endif

<div class="gw-chat-shell">
    <aside class="gw-chat-sidebar" aria-label="Google Chatスペース">
        @include('google-workspace.chat.partials.create-space')
        @include('google-workspace.chat.partials.space-list')
    </aside>

    <section class="gw-chat-empty" aria-labelledby="chat-empty-heading">
        <span class="gw-chat-empty__illustration"><x-google-icon name="chat" :size="52" /></span>
        <h2 id="chat-empty-heading">チャットを選択してください</h2>
        <p>左側のスペースを開くと、メッセージの閲覧・投稿・返信をLMS内で行えます。</p>
    </section>
</div>
