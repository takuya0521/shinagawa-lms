@php($activePanel = request()->string('panel')->trim()->toString())

<div class="gw-chat-shell gw-chat-shell--conversation">
            <aside class="gw-chat-sidebar" aria-label="Google Chatスペース">
                @include('google-workspace.chat.partials.create-space')
                @include('google-workspace.chat.partials.space-list')
            </aside>

            <section class="gw-chat-conversation" aria-labelledby="chat-space-heading">
                <header class="gw-chat-conversation__header">
                    <div
                        class="gw-chat-space-avatar"
                        aria-hidden="true"
                    >{{ \Illuminate\Support\Str::substr($space->displayName, 0, 1) }}</div>
                    <div class="gw-chat-conversation__identity">
                        <h2 id="chat-space-heading">{{ $space->displayName }}</h2>
                        <p>{{ $space->description ?? $space->typeLabel() }}</p>
                    </div>
                    <div class="gw-chat-conversation__actions">
                        <a
                            class="gw-icon-button"
                            href="{{
                                route('google-workspace.chat.show', ['spaceId' => $space->id, 'panel' => 'members'])
                            }}"
                            aria-label="メンバーを表示"
                            title="メンバー"
                        ><x-google-icon name="users" :size="20" /></a>
                        @if ($space->isNamedSpace())
                            <a
                                class="gw-icon-button"
                                href="{{
                                    route('google-workspace.chat.show', ['spaceId' => $space->id, 'panel' =>
                                    'settings'])
                                }}"
                                aria-label="スペース設定を表示"
                                title="設定"
                            ><x-google-icon name="settings" :size="20" /></a>
                        @endif
                        <a
                            class="gw-icon-button"
                            href="{{ route('google-workspace.chat.show', ['spaceId' => $space->id, 'refresh' => 1]) }}"
                            aria-label="メッセージを更新"
                            title="更新"
                        ><x-google-icon name="refresh" :size="20" /></a>
                        @if ($space->spaceUri !== null)
                            <a
                                class="gw-icon-button gw-icon-button--text"
                                href="{{ $space->spaceUri }}"
                                target="_blank"
                                rel="noopener noreferrer"
                            >Googleで開く</a>
                        @endif
                    </div>
                </header>

                <div class="gw-chat-messages" data-chat-messages>
                    @include('google-workspace.chat.partials.messages')
                </div>

                @include('google-workspace.chat.partials.compose')
            </section>

            @if (in_array($activePanel, ['members', 'settings'], true))
                <aside class="gw-chat-drawer" aria-label="{{ $activePanel === 'members' ? 'メンバー' : 'スペース設定' }}">
                    <header>
                        <h3>{{ $activePanel === 'members' ? 'メンバー' : 'スペースの詳細' }}</h3>
                        <a
                            class="gw-icon-button"
                            href="{{ route('google-workspace.chat.show', $space->id) }}"
                            aria-label="パネルを閉じる"
                        >
                            <x-google-icon name="close" :size="20" />
                        </a>
                    </header>
                    @if ($activePanel === 'members')
                        @include('google-workspace.chat.partials.members')
                    @else
                        @include('google-workspace.chat.partials.space-settings')
                    @endif
                </aside>
            @endif
        </div>
