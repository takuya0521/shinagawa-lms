@php
    $activeSlots = $slots->reject(fn ($slot) => (bool) $slot->getAttribute('is_cancelled'));
    $periodCounts = $activeSlots->groupBy('period_no')->sortKeys()->map->count();
    $maximumCount = max(1, (int) $periodCounts->max());
    $attendanceSlots = $activeSlots->filter(fn ($slot) => (int) $slot->getAttribute('target_count') > 0);
    $completeCount = $attendanceSlots->filter(fn ($slot) => (int) $slot->getAttribute('missing_count') === 0)->count();
    $completeRate = $attendanceSlots->isEmpty() ? 0 : (int) round($completeCount / $attendanceSlots->count() * 100);
@endphp

<div class="dashboard-analytics">
    <section class="lms-panel dashboard-chart" aria-labelledby="dashboard-lessons-title">
        <header class="dashboard-section-heading">
            <div><h2 id="dashboard-lessons-title">時限別の授業数</h2><p>本日の時間割・休講を除く</p></div>
            <span class="dashboard-chart-total">{{ $activeSlots->count() }}<small> 授業</small></span>
        </header>
        @if ($periodCounts->isNotEmpty())
            <div class="dashboard-bars">
                @foreach ($periodCounts as $period => $count)
                    <div class="dashboard-bar">
                        <strong>{{ $count }}<small>件</small></strong>
                        <div class="dashboard-bar-track" aria-hidden="true">
                            <span style="height: {{ $count / $maximumCount * 100 }}%"></span>
                        </div>
                        <span>{{ $period }}時限</span>
                    </div>
                @endforeach
            </div>
        @else
            <p class="dashboard-chart-empty">本日の授業はありません。</p>
        @endif
    </section>
    <section class="lms-panel dashboard-completion" aria-labelledby="dashboard-completion-title">
        <header class="dashboard-section-heading">
            <div><h2 id="dashboard-completion-title">本日の出欠登録</h2><p>対象生徒全員の登録が揃った授業</p></div>
        </header>
        @if ($attendanceSlots->isNotEmpty())
            <div class="dashboard-progress-ring" style="--dashboard-rate: {{ $completeRate }}%">
                <strong>{{ $completeRate }}<small>%</small></strong>
                <span>登録完了</span>
            </div>
            <p class="dashboard-completion-count">{{ $completeCount }} / {{ $attendanceSlots->count() }}授業</p>
        @else
            <p class="dashboard-chart-empty">本日の出欠登録対象はありません。</p>
        @endif
    </section>
</div>
