<section class="gw-section gw-classroom-hero">
    <div class="gw-classroom-hero__main">
        <div class="gw-classroom-hero__icon">
            <img src="{{ asset('images/google/classroom.webp') }}" alt="" width="44" height="44">
        </div>
        <div>
            <span class="gw-eyebrow">Google Classroom</span>
            <h2>{{ $detail->course->name }}</h2>
            @if ($detail->course->section !== null)
                <p class="gw-meta">{{ $detail->course->section }}</p>
            @endif
        </div>
    </div>
    <div class="gw-classroom-hero__actions">
        <span @class(['gw-status-chip', 'is-active' => $detail->course->state === 'ACTIVE'])>
            {{ $detail->course->stateLabel() }}
        </span>
        @if ($detail->course->alternateLink !== null)
            <a
                class="gw-button gw-button--primary"
                href="{{ $detail->course->alternateLink }}"
                target="_blank"
                rel="noopener noreferrer"
            >Classroomで開く</a>
        @endif
    </div>
</section>

@if ($detail->course->description !== null || $detail->course->room !== null
    || $detail->course->enrollmentCode !== null)
    <section class="gw-section">
        <div class="gw-section__header">
            <div>
                <p>クラス情報</p>
                <h2>概要</h2>
            </div>
        </div>
        <dl class="gw-classroom-info">
            @if ($detail->course->room !== null)
                <div>
                    <dt>教室</dt>
                    <dd>{{ $detail->course->room }}</dd>
                </div>
            @endif
            @if ($detail->course->enrollmentCode !== null)
                <div>
                    <dt>参加コード</dt>
                    <dd><span class="gw-code">{{ $detail->course->enrollmentCode }}</span></dd>
                </div>
            @endif
            @if ($detail->course->updatedAt !== null)
                <div>
                    <dt>最終更新</dt>
                    <dd>{{ $detail->course->updatedAt->format('Y/m/d H:i') }}</dd>
                </div>
            @endif
        </dl>
        @if ($detail->course->description !== null)
            <p class="gw-classroom-description">{{ $detail->course->description }}</p>
        @endif
    </section>
@endif

<section class="gw-section">
    <div class="gw-section__header">
        <div>
            <p>Classwork</p>
            <h2>課題</h2>
        </div>
        <span class="gw-count">{{ count($detail->courseWork) }}件</span>
    </div>

    @if ($detail->courseWork === [])
        <div class="gw-empty-state gw-empty-state--compact">
            <div class="gw-empty-state__icon"><x-google-icon name="classroom" :size="34" /></div>
            <h3>課題はありません</h3>
            <p>閲覧できる課題が追加されると、ここに表示されます。</p>
        </div>
    @else
        <div class="gw-classroom-feed">
            @foreach ($detail->courseWork as $courseWork)
                <article class="gw-classroom-feed-item">
                    <div class="gw-classroom-feed-item__marker">
                        <x-google-icon name="classroom" :size="20" />
                    </div>
                    <div class="gw-classroom-feed-item__body">
                        <div class="gw-classroom-feed-item__heading">
                            <div>
                                <span class="gw-eyebrow">{{ $courseWork->workTypeLabel() }}</span>
                                <h3>{{ $courseWork->title }}</h3>
                            </div>
                            <span @class(['gw-status-chip', 'is-active' => $courseWork->state === 'PUBLISHED'])>
                                {{ $courseWork->stateLabel() }}
                            </span>
                        </div>
                        @if ($courseWork->description !== null)
                            <p>{{ $courseWork->description }}</p>
                        @endif
                        <div class="gw-classroom-feed-item__meta">
                            <span>期限: {{ $courseWork->dueAt?->format('Y/m/d H:i') ?? '指定なし' }}</span>
                            @if ($courseWork->maxPoints !== null)
                                <span>配点: {{ rtrim(rtrim(number_format($courseWork->maxPoints, 2), '0'), '.') }}点</span>
                            @endif
                            @if ($courseWork->updatedAt !== null)
                                <span>更新: {{ $courseWork->updatedAt->format('Y/m/d H:i') }}</span>
                            @endif
                        </div>
                        @if ($courseWork->alternateLink !== null)
                            <div class="gw-actions">
                                <a
                                    class="gw-button gw-button--secondary gw-button--compact"
                                    href="{{ $courseWork->alternateLink }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >課題を開く</a>
                            </div>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>

<section class="gw-section">
    <div class="gw-section__header">
        <div>
            <p>Stream</p>
            <h2>お知らせ</h2>
        </div>
        <span class="gw-count">{{ count($detail->announcements) }}件</span>
    </div>

    @if ($detail->announcements === [])
        <div class="gw-empty-state gw-empty-state--compact">
            <div class="gw-empty-state__icon"><x-google-icon name="chat" :size="34" /></div>
            <h3>お知らせはありません</h3>
            <p>閲覧できるお知らせが追加されると、ここに表示されます。</p>
        </div>
    @else
        <div class="gw-classroom-announcements">
            @foreach ($detail->announcements as $announcement)
                <article class="gw-classroom-announcement">
                    <div class="gw-classroom-announcement__body">
                        <p>{{ $announcement->text }}</p>
                        @if ($announcement->updatedAt !== null)
                            <span>更新 {{ $announcement->updatedAt->format('Y/m/d H:i') }}</span>
                        @endif
                    </div>
                    @if ($announcement->alternateLink !== null)
                        <a
                            class="gw-icon-button gw-icon-button--text"
                            href="{{ $announcement->alternateLink }}"
                            target="_blank"
                            rel="noopener noreferrer"
                        >Googleで開く</a>
                    @endif
                </article>
            @endforeach
        </div>
    @endif
</section>
