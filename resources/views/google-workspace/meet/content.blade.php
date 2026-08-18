@if ($space !== null)
    <section class="gw-section gw-meet-space">
        <div class="gw-section__header">
            <div>
                <p>会議コード検索結果</p>
                <h2>{{ $space->meetingCode }}</h2>
            </div>
            <span @class(['gw-status-chip', 'is-active' => $space->isActive()])>
                {{ $space->isActive() ? '開催中' : '待機中' }}
            </span>
        </div>
        <div class="gw-actions">
            <a
                class="gw-button gw-button--primary"
                href="{{ $space->meetingUri }}"
                target="_blank"
                rel="noopener noreferrer"
            >Meetに参加</a>
            <span class="gw-code">{{ $space->name }}</span>
        </div>
    </section>
@endif

<section class="gw-section">
    <div class="gw-section__header">
        <div>
            <p>{{ $meetingCode !== null ? '検索した会議コードの履歴' : 'ログインユーザーが参照できる履歴' }}</p>
            <h2>最近の会議</h2>
        </div>
        <span class="gw-count">{{ $conferences->count() }}件</span>
    </div>

    @if ($conferences->isEmpty())
        <div class="gw-empty-state gw-empty-state--compact">
            <div class="gw-empty-state__icon"><x-google-icon name="meet" :size="34" /></div>
            <h3>会議履歴はありません</h3>
            <p>会議を開始すると、参照できる履歴がここに表示されます。</p>
        </div>
    @else
        <div class="gw-table-wrap">
            <table class="gw-table">
                <thead>
                    <tr>
                        <th>開始</th>
                        <th>終了</th>
                        <th>時間</th>
                        <th>状態</th>
                        <th>会議ID</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($conferences as $conference)
                        <tr>
                            <td>{{ $conference->startedAt->format('Y/m/d H:i') }}</td>
                            <td>{{ $conference->endedAt?->format('Y/m/d H:i') ?? '開催中' }}</td>
                            <td>{{ $conference->durationLabel() }}</td>
                            <td>
                                <span @class(['gw-status-chip', 'is-active' => $conference->isActive()])>
                                    {{ $conference->isActive() ? '開催中' : '終了' }}
                                </span>
                            </td>
                            <td><span class="gw-code">{{ $conference->shortId() }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
