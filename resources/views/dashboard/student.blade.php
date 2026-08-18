@extends('layouts.app')

@section('page-style', 'resources/css/pages/dashboard/student.css')
@section('page-class', 'page-dashboard-student')

@section('title', '生徒トップ')
@section('header-title', '生徒トップ')

@section('content')
    @php
        $dashboardAnnouncements = $announcements->take(4);
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
        $externalLinkIcons = [
            \App\Enums\ExternalLinkType::Classroom->value => 'classroom.webp',
            \App\Enums\ExternalLinkType::Calendar->value => 'calendar.svg',
            \App\Enums\ExternalLinkType::Forms->value => 'forms.svg',
            \App\Enums\ExternalLinkType::Chat->value => 'chat.svg',
            \App\Enums\ExternalLinkType::Drive->value => 'drive.svg',
            \App\Enums\ExternalLinkType::Meet->value => 'meet.svg',
        ];
    @endphp

    <div class="student-dashboard">
        {{-- LMSの導入部は装飾を抑え、ログイン中の生徒情報を短時間で確認できる構成にする。 --}}
        <section class="student-dashboard-overview" aria-labelledby="student-dashboard-overview-title">
            <div class="student-dashboard-overview__heading">
                <h2 id="student-dashboard-overview-title">こんにちは、{{ $student->student_name }}さん</h2>
            </div>

            <dl class="student-dashboard-overview__details">
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

        <div class="student-dashboard-primary-grid">
            {{-- 既存の時間割データから、生徒本人に紐づく授業だけを要約表示する。 --}}
            <section class="student-dashboard-panel" aria-labelledby="student-dashboard-timetable-title">
                <header class="student-dashboard-panel__header">
                    <div class="student-dashboard-panel__title">
                        <span
                            class="student-dashboard-panel__icon student-dashboard-panel__icon--pink"
                            aria-hidden="true"
                        >
                            <x-nav-icon name="book" />
                        </span>
                        <div>
                            <span>週間時間割</span>
                            <h2 id="student-dashboard-timetable-title">授業</h2>
                        </div>
                    </div>
                    <a
                        class="student-dashboard-panel__link"
                        href="{{ route('student.timetable.index', ['academic_year' => $academicYear]) }}"
                    >
                        すべて見る
                    </a>
                </header>

                <div class="student-dashboard-timetable">
                    @forelse ($timetableSlots->take(6) as $slot)
                        <article class="student-dashboard-lesson">
                            <div class="student-dashboard-lesson__period">
                                <span>{{ $slot->day_of_week->shortLabel() }}曜</span>
                                <strong>{{ $slot->period_no }}時限</strong>
                            </div>

                            <div class="student-dashboard-lesson__content">
                                <h3>{{ $slot->course->course_name }}</h3>
                                <p>
                                    {{ $slot->course->subject->subject_name }}
                                    @if ($slot->course->teacher !== null)
                                        ・{{ $slot->course->teacher->user->name }}先生
                                    @endif
                                </p>
                            </div>

                            @if ($slot->start_time !== null && $slot->end_time !== null)
                                <time class="student-dashboard-lesson__time">
                                    {{ substr((string) $slot->start_time, 0, 5) }}～{{ substr((string) $slot->end_time,
                                    0, 5) }}
                                </time>
                            @endif

                            @if ($slot->course->google_classroom_url !== null)
                                <a
                                    class="student-dashboard-lesson__classroom"
                                    href="{{ $slot->course->google_classroom_url }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <img
                                        src="{{ asset('images/google/classroom.webp') }}"
                                        alt=""
                                        width="16"
                                        height="16"
                                        aria-hidden="true"
                                    >
                                    <span>Classroom</span>
                                </a>
                            @endif
                        </article>
                    @empty
                        <div class="student-dashboard-empty">
                            <strong>時間割はまだ登録されていません</strong>
                            <p>対象年度の時間割が登録されると、本人の授業が表示されます。</p>
                        </div>
                    @endforelse
                </div>

                @if ($timetableSlots->count() > 6)
                    <p class="student-dashboard-panel__note">
                        ほか {{ $timetableSlots->count() - 6 }}件の授業があります。
                    </p>
                @endif
            </section>

            {{-- 公開対象に含まれる既存のお知らせだけを、一覧性を優先して表示する。 --}}
            <section class="student-dashboard-panel" aria-labelledby="student-dashboard-notice-title">
                <header class="student-dashboard-panel__header">
                    <div class="student-dashboard-panel__title">
                        <span
                            class="student-dashboard-panel__icon student-dashboard-panel__icon--pink"
                            aria-hidden="true"
                        >
                            <x-nav-icon name="bell" />
                        </span>
                        <div>
                            <span>学校からのお知らせ</span>
                            <h2 id="student-dashboard-notice-title">お知らせ</h2>
                        </div>
                    </div>
                    <a class="student-dashboard-panel__link" href="{{ route('student.announcements.index') }}">
                        一覧を見る
                    </a>
                </header>

                <div class="student-dashboard-notice-list">
                    @forelse ($dashboardAnnouncements as $announcement)
                        <a
                            class="student-dashboard-notice"
                            href="{{ route('student.announcements.show', $announcement) }}"
                        >
                            <time>
                                {{ ($announcement->publish_start_at ?? $announcement->created_at)->format('Y/m/d') }}
                            </time>
                            <span class="student-dashboard-notice__body">
                                <span class="student-dashboard-notice__meta">
                                    @if ($announcement->is_important)
                                        <strong>重要</strong>
                                    @endif
                                    <small>{{ $announcement->notice_type->label() }}</small>
                                </span>
                                <span class="student-dashboard-notice__title">{{ $announcement->title }}</span>
                            </span>
                            <span class="student-dashboard-notice__arrow" aria-hidden="true">→</span>
                        </a>
                    @empty
                        <div class="student-dashboard-empty">
                            <strong>掲載中のお知らせはありません</strong>
                            <p>学校からのお知らせが公開されると表示されます。</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="student-dashboard-summary-grid">
            {{-- 既存の確定済み年間評価だけを集計結果として表示する。 --}}
            <section class="student-dashboard-summary" aria-labelledby="student-dashboard-evaluation-title">
                <header class="student-dashboard-summary__header">
                    <div>
                        <span>確定済み年間評価</span>
                        <h2 id="student-dashboard-evaluation-title">成績</h2>
                    </div>
                    <a href="{{ route('student.evaluations.index', ['academic_year' => $academicYear]) }}">詳細を見る</a>
                </header>

                @if ($evaluation['count'] > 0)
                    <div class="student-dashboard-evaluation">
                        <div class="student-dashboard-evaluation__main">
                            <span>5段階評価の平均</span>
                            <strong>{{ $formatScore($evaluation['grade_level']) }}</strong>
                            <small>確定済み {{ $evaluation['count'] }}科目</small>
                        </div>

                        <dl class="student-dashboard-metrics">
                            <div>
                                <dt>総合点</dt>
                                <dd>{{ $formatScore($evaluation['total_score']) }}点</dd>
                            </div>
                            <div>
                                <dt>提出物</dt>
                                <dd>{{ $formatScore($evaluation['submission_score']) }}点</dd>
                            </div>
                            <div>
                                <dt>出欠</dt>
                                <dd>{{ $formatScore($evaluation['attendance_score']) }}点</dd>
                            </div>
                            <div>
                                <dt>授業態度</dt>
                                <dd>{{ $formatScore($evaluation['attitude_score']) }}点</dd>
                            </div>
                        </dl>
                    </div>
                @else
                    <div class="student-dashboard-empty student-dashboard-empty--summary">
                        <strong>確定済みの評価はありません</strong>
                        <p>評価が確定すると、ここに表示されます。</p>
                    </div>
                @endif
            </section>

            {{-- 既存の年度内出欠集計だけを表示し、未登録時は空状態を示す。 --}}
            <section class="student-dashboard-summary" aria-labelledby="student-dashboard-attendance-title">
                <header class="student-dashboard-summary__header">
                    <div>
                        <span>{{ $academicYear }}年度</span>
                        <h2 id="student-dashboard-attendance-title">出欠</h2>
                    </div>
                </header>

                @if ($attendance['total'] > 0)
                    <div class="student-dashboard-attendance">
                        <div class="student-dashboard-attendance__main">
                            <span>出席率</span>
                            <strong>{{ $attendance['rate'] }}%</strong>
                            <small>{{ $attendance['attended'] }}/{{ $attendance['total'] }}件 出席相当</small>
                        </div>

                        <dl class="student-dashboard-metrics">
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
                            <div>
                                <dt>早退</dt>
                                <dd>{{ $attendance['early_leave'] }}件</dd>
                            </div>
                        </dl>
                    </div>
                @else
                    <div class="student-dashboard-empty student-dashboard-empty--summary">
                        <strong>出欠記録はまだありません</strong>
                        <p>授業の出欠が登録されると、年度内の集計が表示されます。</p>
                    </div>
                @endif
            </section>
        </div>

        {{-- 設計済みの画面と登録済み外部リンクだけを、既存機能への導線としてまとめる。 --}}
        <section class="student-dashboard-services" aria-labelledby="student-dashboard-services-title">
            <header class="student-dashboard-services__header">
                <div>
                    <span>既存機能へのショートカット</span>
                    <h2 id="student-dashboard-services-title">利用メニュー</h2>
                </div>
                <a href="{{ route('student.google-drive.index') }}">Google Workspace</a>
            </header>

            <div class="student-dashboard-service-grid">
                <a
                    class="student-dashboard-service"
                    href="{{ $classroomUrl ?? route('student.timetable.index', ['academic_year' => $academicYear]) }}"
                    @if ($classroomUrl !== null)
                        target="_blank"
                        rel="noopener noreferrer"
                    @endif
                >
                    <span
                        class="student-dashboard-service__icon student-dashboard-service__icon--google"
                        aria-hidden="true"
                    >
                        <img
                            src="{{ asset('images/google/classroom.webp') }}"
                            alt=""
                            width="28"
                            height="28"
                        >
                    </span>
                    <span class="student-dashboard-service__content">
                        <strong>Google Classroom</strong>
                        <small>授業ごとのClassroomを確認</small>
                    </span>
                    <span class="student-dashboard-service__arrow" aria-hidden="true">→</span>
                </a>

                <a
                    class="student-dashboard-service"
                    href="{{ route('student.google-drive.index') }}"
                >
                    <span
                        class="student-dashboard-service__icon student-dashboard-service__icon--google"
                        aria-hidden="true"
                    >
                        <img
                            src="{{ asset('images/google/drive.svg') }}"
                            alt=""
                            width="28"
                            height="28"
                        >
                    </span>
                    <span class="student-dashboard-service__content">
                        <strong>Google Drive</strong>
                        <small>学習資料・共有ファイルを確認</small>
                    </span>
                    <span class="student-dashboard-service__arrow" aria-hidden="true">→</span>
                </a>

                <a class="student-dashboard-service" href="{{ route('student.google-calendar.index') }}">
                    <span
                        class="student-dashboard-service__icon student-dashboard-service__icon--google"
                        aria-hidden="true"
                    >
                        <img
                            src="{{ asset('images/google/calendar.svg') }}"
                            alt=""
                            width="28"
                            height="28"
                        >
                    </span>
                    <span class="student-dashboard-service__content">
                        <strong>Google Calendar</strong>
                        <small>今後の予定・Meetを確認</small>
                    </span>
                    <span class="student-dashboard-service__arrow" aria-hidden="true">→</span>
                </a>

                <a class="student-dashboard-service" href="{{ route('student.google-chat.index') }}">
                    <span
                        class="student-dashboard-service__icon student-dashboard-service__icon--google"
                        aria-hidden="true"
                    >
                        <img
                            src="{{ asset('images/google/chat.svg') }}"
                            alt=""
                            width="28"
                            height="28"
                        >
                    </span>
                    <span class="student-dashboard-service__content">
                        <strong>Google Chat</strong>
                        <small>参加中スペースを確認</small>
                    </span>
                    <span class="student-dashboard-service__arrow" aria-hidden="true">→</span>
                </a>

                <a class="student-dashboard-service" href="{{ route('student.annual-schedule') }}">
                    <span
                        class="student-dashboard-service__icon student-dashboard-service__icon--google"
                        aria-hidden="true"
                    >
                        <img
                            src="{{ asset('images/google/calendar.svg') }}"
                            alt=""
                            width="28"
                            height="28"
                        >
                    </span>
                    <span class="student-dashboard-service__content">
                        <strong>年間予定</strong>
                        <small>学校行事と年間予定を確認</small>
                    </span>
                    <span class="student-dashboard-service__arrow" aria-hidden="true">→</span>
                </a>

                <a class="student-dashboard-service" href="{{ route('student.interview-request') }}">
                    <span
                        class="student-dashboard-service__icon student-dashboard-service__icon--google"
                        aria-hidden="true"
                    >
                        <img
                            src="{{ asset('images/google/forms.svg') }}"
                            alt=""
                            width="28"
                            height="28"
                        >
                    </span>
                    <span class="student-dashboard-service__content">
                        <strong>面談希望申込</strong>
                        <small>面談を希望する場合に回答</small>
                    </span>
                    <span class="student-dashboard-service__arrow" aria-hidden="true">→</span>
                </a>


                {{-- 管理者が対象者別に登録した外部リンクも、ダッシュボードから直接利用できるようにする。 --}}
                @foreach ($externalLinks->take(6) as $link)
                    <a
                        class="student-dashboard-service"
                        href="{{ $link->url }}"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <span
                            class="student-dashboard-service__icon student-dashboard-service__icon--google"
                            aria-hidden="true"
                        >
                            <img
                                src="{{ asset('images/google/'.$externalLinkIcons[$link->link_type->value]) }}"
                                alt=""
                                width="28"
                                height="28"
                            >
                        </span>
                        <span class="student-dashboard-service__content">
                            <strong>{{ $link->link_name }}</strong>
                            <small>{{ $link->link_type->label() }}</small>
                        </span>
                        <span class="student-dashboard-service__arrow" aria-hidden="true">→</span>
                    </a>
                @endforeach
            </div>
        </section>
    </div>
@endsection
