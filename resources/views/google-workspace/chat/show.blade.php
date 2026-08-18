@extends('layouts.app')

@section('page-style', 'resources/css/pages/google-workspace/workspace.css')
@section('page-script', 'resources/js/google-workspace.js')
@section('page-class', 'page-google-workspace page-google-workspace-chat page-google-workspace-chat-show')
@section('title', 'Google Chat')
@section('header-title', 'Google Chat')

@section('content')
    <div class="gw-page gw-page--chat" data-workspace-page>
        <x-google-workspace.service-header
            service="chat"
            title="Chat"
            icon="images/google/chat.svg"
        />

        @include('google-workspace._connection')

        @if ($isAuthorized)
            <div
                class="gw-async-region"
                data-workspace-async-region
                data-workspace-async-url="{{
                    route('google-workspace.chat.show-content', ['spaceId' => $spaceId] + request()->query())
                }}"
                aria-busy="true"
            >
                @include('google-workspace._async-loading', ['label' => 'Google Chat'])
            </div>
        @endif
    </div>
@endsection
