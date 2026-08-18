@extends('layouts.app')

@section('page-style', 'resources/css/pages/google-workspace/workspace.css')
@section('page-script', 'resources/js/google-workspace.js')
@section('page-class', 'page-google-workspace page-google-workspace-calendar')
@section('title', 'Google Calendar 予定詳細')
@section('header-title', 'Google Calendar')

@section('content')
    <div class="gw-page" data-workspace-page>
        <x-google-workspace.service-header
            service="calendar"
            title="カレンダー"
            icon="images/google/calendar.svg"
        />

        @include('google-workspace._connection')

        @if ($isAuthorized)
            <div
                class="gw-async-region"
                data-workspace-async-region
                data-workspace-async-url="{{
                    route('google-workspace.calendar.events.show-content', ['calendarId' => $calendarId, 'eventId' =>
                    $eventId])
                }}"
                aria-busy="true"
            >
                @include('google-workspace._async-loading', ['label' => '予定詳細'])
            </div>
        @endif
    </div>
@endsection
