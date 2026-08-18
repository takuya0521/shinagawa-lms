<div class="gw-message-list">
    @forelse ($messages->reverse() as $message)
        <article class="gw-message" id="message-{{ $message->id }}">
            <div
                class="gw-message__avatar"
                aria-hidden="true"
            >{{ \Illuminate\Support\Str::substr($message->senderName, 0, 1) }}</div>
            <div class="gw-message__body">
                <header>
                    <strong>{{ $message->senderName }}</strong>
                    <time
                        datetime="{{ $message->createdAt->toIso8601String() }}"
                    >{{ $message->createdAt->format('n月j日 H:i') }}</time>
                    @if (in_array($message->resourceName, $pinnedMessageNames, true))
                        <span class="gw-pin">固定</span>
                    @endif
                </header>
                <div class="gw-message__text">{!! nl2br(e($message->text !== '' ? $message->text : '（本文なし）')) !!}</div>

                @if ($message->attachments !== [])
                    <div class="gw-attachments">
                        @foreach ($message->attachments as $attachment)
                            @if ($attachment['name'] !== '')
                                <a
                                    href="{{
                                        route('google-workspace.chat.attachments.download', [$space->id, $message->id,
                                        basename($attachment['name'])])
                                    }}"
                                >
                                    <x-google-icon name="attach" :size="17" />
                                    <span>{{ $attachment['content_name'] }}</span>
                                </a>
                            @else
                                <span>{{ $attachment['content_name'] }}</span>
                            @endif
                        @endforeach
                    </div>
                @endif

                @if ($message->reactions->isNotEmpty())
                    <div class="gw-reactions" aria-label="リアクション集計">
                        @foreach ($message->reactions as $reaction)
                            <span>{{ $reaction->emoji }}</span>
                        @endforeach
                    </div>
                @endif

                @include('google-workspace.chat.partials.reaction-management')
            </div>
            @include('google-workspace.chat.partials.message-tools')
        </article>
    @empty
        <div class="gw-empty-state">
            <span class="gw-empty-state__icon"><x-google-icon name="chat" :size="42" /></span>
            <h3>メッセージはまだありません</h3>
            <p>最初のメッセージを送信して会話を始めましょう。</p>
        </div>
    @endforelse
</div>
