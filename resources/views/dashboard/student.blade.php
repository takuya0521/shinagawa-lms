@extends('layouts.app')

@section('page-style', 'resources/css/pages/dashboard/student.css')
@section('page-class', 'page-dashboard-student')

@section('title', '生徒トップ')
@section('header-title', '生徒トップ')
@section('page-kicker', 'Student Portal')
@section('header-description', '学校からのお知らせ、時間割、確定済み評価、出欠記録、Googleサービスを確認できます。')

@section('content')
    <div class="student-dashboard-content">
    @php
        $featuredAnnouncement = $announcements->first();
        $otherAnnouncements = $announcements->slice(1, 3);
        $evaluation = $dashboardSummary['evaluation'];
        $attendance = $dashboardSummary['attendance'];
        $formatScore = static function (?float $value): string {
            if ($value === null) {
                return '—';
            }

            return rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.');
        };
        $classroomLink = $externalLinks->first(
            static fn ($link): bool => $link->link_type === \App\Enums\ExternalLinkType::Classroom,
        );
        $classroomUrl = $classroomLink?->url
            ?? $timetableSlots->first(
                static fn ($slot): bool => $slot->course->google_classroom_url !== null,
            )?->course->google_classroom_url;
        $communicationLink = $externalLinks->first(
            static fn ($link): bool => in_array(
                $link->link_type,
                [
                    \App\Enums\ExternalLinkType::Chat,
                    \App\Enums\ExternalLinkType::Drive,
                    \App\Enums\ExternalLinkType::Meet,
                ],
                true,
            ),
        );
    @endphp

    {{-- 生徒本人の情報を明示し、共通ヘッダーのアカウント名と生徒台帳上の氏名を区別できるようにする。 --}}
    <section class="student-profile-summary" aria-labelledby="student-profile-heading">
        <div class="student-profile-summary__body">
            <p class="student-section-label">Student Profile</p>
            <h2 id="student-profile-heading">{{ $student->student_name }}さん</h2>
            <p>学校からのお知らせや学習状況を、このページでまとめて確認できます。</p>
        </div>

        <dl class="student-profile-summary__details">
            <div>
                <dt>学年</dt>
                <dd>{{ $student->grade->label() }}</dd>
            </div>
            <div>
                <dt>クラス</dt>
                <dd>{{ $student->classGroup?->class_name ?? '未設定' }}</dd>
            </div>
            <div>
                <dt>学籍番号</dt>
                <dd>{{ $student->student_no ?: '未設定' }}</dd>
            </div>
        </dl>
    </section>

    {{-- 生徒トップから本人対象の授業を確認できることが既存仕様のため、週間時間割の要約を表示する。 --}}
    <section class="student-panel student-panel--wide" id="weekly-timetable">
        <div class="student-panel__head">
            <div>
                <p class="student-section-label">Weekly Timetable</p>
                <h2>今週の時間割</h2>
            </div>
            <a href="{{ route('student.timetable.index', ['academic_year' => $academicYear]) }}">時間割を見る</a>
        </div>

        <div class="student-schedule-grid">
            @forelse ($timetableSlots->take(6) as $slot)
                <article class="student-schedule-card">
                    <div class="student-schedule-card__time">
                        <span>{{ $slot->day_of_week->shortLabel() }}曜</span>
                        <strong>{{ $slot->period_no }}時限</strong>
                    </div>
                    <div class="student-schedule-card__body">
                        <h3>{{ $slot->course->course_name }}</h3>
                        <p>
                            {{ $slot->course->subject->subject_name }}
                            @if ($slot->course->teacher !== null)
                                ・{{ $slot->course->teacher->user->name }}先生
                            @endif
                        </p>
                        @if ($slot->start_time !== null && $slot->end_time !== null)
                            <small>
                                {{ substr((string) $slot->start_time, 0, 5) }}～{{ substr((string) $slot->end_time, 0, 5) }}
                            </small>
                        @endif
                    </div>
                    @if ($slot->course->google_classroom_url !== null)
                        <a
                            href="{{ $slot->course->google_classroom_url }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="student-schedule-card__classroom"
                        >Classroom</a>
                    @endif
                </article>
            @empty
                <div class="student-summary-empty student-summary-empty--schedule">
                    <strong>時間割はまだ登録されていません</strong>
                    <p>対象年度の時間割が登録されると、本人の授業だけがここに表示されます。</p>
                </div>
            @endforelse
        </div>

        @if ($timetableSlots->count() > 6)
            <p class="student-helper-text">
                ほか {{ $timetableSlots->count() - 6 }}件の授業があります。週間時間割からすべて確認できます。
            </p>
        @endif
    </section>

    <section class="student-panel student-panel--wide" id="notice">
        <div class="student-panel__head">
            <div>
                <p class="student-section-label">LMS Notice</p>
                <h2>掲示板・学校からのお知らせ</h2>
            </div>
            <a href="{{ route('student.announcements.index') }}">一覧を見る</a>
        </div>

        @if ($featuredAnnouncement !== null)
            <div class="student-notice-board">
                <a href="{{ route('student.announcements.show', $featuredAnnouncement) }}" @class([
                    'student-notice-board__main',
                    'student-notice-board__main--important' => $featuredAnnouncement->is_important,
                ])>
                    <span class="student-notice-category">
                        {{ $featuredAnnouncement->is_important ? '重要' : $featuredAnnouncement->notice_type->label() }}
                    </span>
                    <div>
                        <p class="student-featured-notice-date">
                            {{ ($featuredAnnouncement->publish_start_at ?? $featuredAnnouncement->created_at)->format('Y/m/d') }}
                        </p>
                        <h3>{{ $featuredAnnouncement->title }}</h3>
                        <p>{{ \Illuminate\Support\Str::limit(strip_tags($featuredAnnouncement->body), 100) }}</p>
                    </div>
                </a>

                <div class="student-notice-board__list">
                    @forelse ($otherAnnouncements as $announcement)
                        <a href="{{ route('student.announcements.show', $announcement) }}" class="student-notice-item">
                            <span class="student-notice-date">
                                {{ ($announcement->publish_start_at ?? $announcement->created_at)->format('m/d') }}
                            </span>
                            <div>
                                <div class="student-notice-item__meta">
                                    @if ($announcement->is_important)
                                        <span>重要</span>
                                    @endif
                                    <small>{{ $announcement->notice_type->label() }}</small>
                                </div>
                                <h3>{{ $announcement->title }}</h3>
                                <p>{{ \Illuminate\Support\Str::limit(strip_tags($announcement->body), 62) }}</p>
                            </div>
                        </a>
                    @empty
                        <div class="student-notice-empty student-notice-empty--compact">
                            ほかのお知らせはありません。
                        </div>
                    @endforelse
                </div>
            </div>
        @else
            <div class="student-notice-empty">
                <strong>掲載中のお知らせはありません</strong>
                <p>学校からのお知らせが公開されると、ここに表示されます。</p>
            </div>
        @endif
    </section>

    <section class="student-panel student-panel--wide" id="google-tools">
        <div class="student-panel__head">
            <div>
                <p class="student-section-label">Google Workspace</p>
                <h2>Google連携メニュー</h2>
            </div>
            <a href="{{ route('student.external-resources') }}">連携一覧</a>
        </div>

        <div class="student-tool-grid">
            <article class="student-tool-card student-tool-card--blue">
                <div class="student-tool-card__icon" aria-hidden="true">GC</div>
                <div>
                    <h3>Google Classroom</h3>
                    <p>授業別のお知らせ・課題・提出状況を確認します。</p>
                </div>
                <a
                    href="{{ $classroomUrl ?? route('student.timetable.index', ['academic_year' => $academicYear]) }}"
                    @if ($classroomUrl !== null) target="_blank" rel="noopener noreferrer" @endif
                >開く</a>
            </article>

            <article class="student-tool-card student-tool-card--green">
                <div class="student-tool-card__icon" aria-hidden="true">
                    {{ $communicationLink?->link_type === \App\Enums\ExternalLinkType::Drive ? 'DR' : ($communicationLink?->link_type === \App\Enums\ExternalLinkType::Meet ? 'MT' : 'CH') }}
                </div>
                <div>
                    <h3>{{ $communicationLink?->link_name ?? 'Google Chat' }}</h3>
                    <p>{{ $communicationLink?->link_type->label() ?? '学校・教員からの個別連絡を確認します。' }}</p>
                </div>
                <a
                    href="{{ $communicationLink?->url ?? route('student.external-resources') }}"
                    @if ($communicationLink !== null) target="_blank" rel="noopener noreferrer" @endif
                >開く</a>
            </article>

            <article class="student-tool-card student-tool-card--orange">
                <div class="student-tool-card__icon" aria-hidden="true">FM</div>
                <div>
                    <h3>面談希望フォーム</h3>
                    <p>面談を希望する場合はこちらから回答します。</p>
                </div>
                <a href="{{ route('student.interview-request') }}">回答する</a>
            </article>

            <article class="student-tool-card student-tool-card--purple">
                <div class="student-tool-card__icon" aria-hidden="true">CL</div>
                <div>
                    <h3>年間行事カレンダー</h3>
                    <p>学校行事や年間予定をカレンダーで確認します。</p>
                </div>
                <a href="{{ route('student.annual-schedule') }}">見る</a>
            </article>
        </div>
    </section>

    <div class="student-two-column-grid">
        <section class="student-panel" id="grade">
            <div class="student-panel__head">
                <div>
                    <p class="student-section-label">Annual Evaluation</p>
                    <h2>確定済み年間評価の平均</h2>
                </div>
                <a href="{{ route('student.evaluations.index', ['academic_year' => $academicYear]) }}">成績詳細</a>
            </div>

            @if ($evaluation['count'] > 0)
                <div class="student-grade-summary">
                    <div class="student-grade-score">
                        <span>5段階評価の平均</span>
                        <strong>{{ $formatScore($evaluation['grade_level']) }}</strong>
                        <p>総合点平均：{{ $formatScore($evaluation['total_score']) }}点</p>
                        <small>確定済み {{ $evaluation['count'] }}科目の平均</small>
                    </div>

                    <div class="student-grade-breakdown">
                        <div class="student-metric-row">
                            <div>
                                <span>提出物</span>
                                <strong>{{ $formatScore($evaluation['submission_score']) }}点</strong>
                            </div>
                            <progress class="student-progress-bar" aria-label="提出物評価" value="{{ $evaluation['submission_score'] ?? 0 }}" max="100"></progress>
                        </div>
                        <div class="student-metric-row">
                            <div>
                                <span>出欠</span>
                                <strong>{{ $formatScore($evaluation['attendance_score']) }}点</strong>
                            </div>
                            <progress class="student-progress-bar" aria-label="出欠評価" value="{{ $evaluation['attendance_score'] ?? 0 }}" max="100"></progress>
                        </div>
                        <div class="student-metric-row">
                            <div>
                                <span>授業態度</span>
                                <strong>{{ $formatScore($evaluation['attitude_score']) }}点</strong>
                            </div>
                            <progress class="student-progress-bar" aria-label="授業態度評価" value="{{ $evaluation['attitude_score'] ?? 0 }}" max="100"></progress>
                        </div>
                    </div>
                </div>
            @else
                <div class="student-summary-empty">
                    <strong>確定済みの評価はありません</strong>
                    <p>評価が確定すると、総合評価と各項目の平均点が表示されます。</p>
                </div>
            @endif
        </section>

        <section class="student-panel" id="attendance">
            <div class="student-panel__head">
                <div>
                    <p class="student-section-label">Attendance</p>
                    <h2>授業別の出欠記録</h2>
                </div>
                <span class="student-panel__year">{{ $academicYear }}年度</span>
            </div>

            @if ($attendance['total'] > 0)
                <div class="student-attendance-box">
                    <div class="student-attendance-rate">
                        <strong>{{ $attendance['rate'] }}%</strong>
                        <small>{{ $attendance['attended'] }}/{{ $attendance['total'] }}件 出席相当</small>
                    </div>

                    <dl class="student-attendance-detail">
                        <div>
                            <dt>出席</dt>
                            <dd>{{ $attendance['present'] }}件</dd>
                        </div>
                        <div>
                            <dt>欠席</dt>
                            <dd>{{ $attendance['absent'] }}件</dd>
                        </div>
                        <div>
                            <dt>遅刻</dt>
                            <dd>{{ $attendance['late'] }}件</dd>
                        </div>
                    </dl>

                    @if ($attendance['early_leave'] > 0)
                        <p class="student-helper-text">早退：{{ $attendance['early_leave'] }}件</p>
                    @endif
                </div>
            @else
                <div class="student-summary-empty student-summary-empty--blue">
                    <strong>出欠記録はまだありません</strong>
                    <p>授業の出欠が登録されると、年度内の出席率が表示されます。</p>
                </div>
            @endif
        </section>
    </div>
    </div>
@endsection
