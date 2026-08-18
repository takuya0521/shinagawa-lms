@extends('layouts.app')

@section('page-style', 'resources/css/pages/google-workspace/workspace.css')
@section('page-script', 'resources/js/google-workspace.js')
@section('page-class', 'page-google-workspace page-google-workspace-calendar')
@section('title', 'Google Calendar')
@section('header-title', 'Google Calendar')

@section('content')
    <div class="gw-page gw-page--calendar" data-workspace-page>
        <x-google-workspace.service-header
            service="calendar"
            title="カレンダー"
            icon="images/google/calendar.svg"
            :search-action="route('google-workspace.calendar.index')"
            :search-value="$keyword"
            search-placeholder="予定を検索"
        >
            <input type="hidden" name="calendar_id" value="{{ $calendarId }}">
            <input type="hidden" name="display" value="{{ $displayMode }}">
            <input type="hidden" name="anchor" value="{{ $anchor->format('Y-m-d') }}">
        </x-google-workspace.service-header>

        @include('google-workspace._connection')

        @if ($isAuthorized)
            <div
                class="gw-async-region"
                data-workspace-async-region
                data-workspace-async-url="{{ route('google-workspace.calendar.content', request()->query()) }}"
                aria-busy="true"
            >
                @include('google-workspace._async-loading', ['label' => 'Google Calendar'])
            </div>
        @endif
    </div>
@endsection
