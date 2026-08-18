<section class="gw-section">
    <div class="gw-section__header">
        <div>
            <p>{{ $keyword !== null ? '検索結果' : 'Google Classroomで参加しているクラス' }}</p>
            <h2>クラス一覧</h2>
        </div>
        <span class="gw-count">{{ $courses->count() }}件</span>
    </div>

    @if ($courses->isEmpty())
        <div class="gw-empty-state gw-empty-state--compact">
            <div class="gw-empty-state__brand-icon">
                <img src="{{ asset('images/google/classroom.webp') }}" alt="" width="42" height="42">
            </div>
            <h3>表示できるクラスがありません</h3>
            <p>検索条件またはクラスの状態を変更してください。</p>
        </div>
    @else
        <div class="gw-classroom-grid">
            @foreach ($courses as $course)
                <article class="gw-classroom-card">
                    <div class="gw-classroom-card__head">
                        <div class="gw-classroom-card__icon">
                            <img src="{{ asset('images/google/classroom.webp') }}" alt="" width="34" height="34">
                        </div>
                        <span @class(['gw-status-chip', 'is-active' => $course->state === 'ACTIVE'])>
                            {{ $course->stateLabel() }}
                        </span>
                    </div>
                    <div class="gw-classroom-card__body">
                        <h3>{{ $course->name }}</h3>
                        @if ($course->section !== null)
                            <p class="gw-classroom-card__section">{{ $course->section }}</p>
                        @endif
                        <p class="gw-meta">
                            {{ $course->room ?? '教室指定なし' }}
                            @if ($course->updatedAt !== null)
                                · 更新 {{ $course->updatedAt->format('Y/m/d H:i') }}
                            @endif
                        </p>
                    </div>
                    <div class="gw-classroom-card__actions">
                        <a
                            class="gw-button gw-button--secondary gw-button--compact"
                            href="{{ route('google-workspace.classroom.show', $course->id) }}"
                        >詳細</a>
                        @if ($course->alternateLink !== null)
                            <a
                                class="gw-icon-button gw-icon-button--text"
                                href="{{ $course->alternateLink }}"
                                target="_blank"
                                rel="noopener noreferrer"
                            >Googleで開く</a>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>
