@extends('layouts.app')

@section('page-style', 'resources/css/pages/google-workspace/workspace.css')
@section('page-script', 'resources/js/google-workspace.js')
@section('page-class', 'page-google-workspace page-google-workspace-classroom-show')
@section('title', 'Google Classroom 詳細')
@section('header-title', 'Google Classroom')

@section('content')
    <div class="gw-page gw-page--classroom" data-workspace-page>
        <x-google-workspace.service-header
            service="classroom"
            title="Classroom"
            icon="images/google/classroom.webp"
        />

        @include('google-workspace._connection')

        @if ($isAuthorized)
            <main class="gw-workspace-content">
                <a class="gw-back" href="{{ route('google-workspace.classroom.index') }}">
                    <x-google-icon name="chevron-left" :size="18" />
                    <span>クラス一覧へ戻る</span>
                </a>

                <div
                    class="gw-async-region gw-async-region--embedded"
                    data-workspace-async-region
                    data-workspace-async-url="{{ route('google-workspace.classroom.show-content', $courseId) }}"
                    aria-busy="true"
                >
                    @include('google-workspace._async-loading', ['label' => 'Google Classroom'])
                </div>
            </main>
        @endif
    </div>
@endsection
