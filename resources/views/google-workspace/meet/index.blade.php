@extends('layouts.app')

@section('page-style', 'resources/css/pages/google-workspace/workspace.css')
@section('page-script', 'resources/js/google-workspace.js')
@section('page-class', 'page-google-workspace page-google-workspace-meet')
@section('title', 'Google Meet')
@section('header-title', 'Google Meet')

@section('content')
    <div class="gw-page gw-page--meet" data-workspace-page>
        <x-google-workspace.service-header
            service="meet"
            title="Meet"
            icon="images/google/meet.svg"
            :search-action="route('google-workspace.meet.index')"
            search-name="meeting_code"
            :search-value="$meetingCode"
            search-placeholder="会議コードで検索"
        />

        @include('google-workspace._connection')

        @if ($isAuthorized)
            <main class="gw-workspace-content">
                @if (is_array($createdSpace))
                    <section class="gw-meet-created" aria-label="作成したGoogle Meet">
                        <div>
                            <span class="gw-eyebrow">作成した会議</span>
                            <strong>{{ $createdSpace['meeting_code'] ?? '' }}</strong>
                        </div>
                        @if (is_string($createdSpace['meeting_uri'] ?? null))
                            <a
                                class="gw-button gw-button--primary"
                                href="{{ $createdSpace['meeting_uri'] }}"
                                target="_blank"
                                rel="noopener noreferrer"
                            >Meetに参加</a>
                        @endif
                    </section>
                @endif

                <section class="gw-grid gw-grid--two gw-meet-actions">
                    <div class="gw-panel">
                        <span class="gw-eyebrow">今すぐ開始</span>
                        <h2>新しい会議リンクを作成</h2>
                        <p class="gw-meta">ログイン中のGoogleアカウント名義でMeetスペースを作成します。</p>
                        <form method="POST" action="{{ route('google-workspace.meet.store') }}">
                            @csrf
                            <button class="gw-button gw-button--primary" type="submit">
                                <x-google-icon name="meet" :size="18" />
                                <span>会議を作成</span>
                            </button>
                        </form>
                    </div>

                    <div class="gw-panel">
                        <span class="gw-eyebrow">予定された会議</span>
                        <h2>Calendarで予定を管理</h2>
                        <p class="gw-meta">日時・参加者を設定する会議はGoogle Calendarから作成できます。</p>
                        <a
                            class="gw-button gw-button--secondary"
                            href="{{ route('google-workspace.calendar.index') }}"
                        >Calendarを開く</a>
                    </div>
                </section>

                <div
                    class="gw-async-region gw-async-region--embedded"
                    data-workspace-async-region
                    data-workspace-async-url="{{ route('google-workspace.meet.content', request()->query()) }}"
                    aria-busy="true"
                >
                    @include('google-workspace._async-loading', ['label' => 'Google Meet'])
                </div>
            </main>
        @endif
    </div>
@endsection
