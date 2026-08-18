@extends('layouts.app')

@section('page-style', 'resources/css/pages/google-workspace/workspace.css')
@section('page-script', 'resources/js/google-workspace.js')
@section('page-class', 'page-google-workspace page-google-workspace-classroom')
@section('title', 'Google Classroom')
@section('header-title', 'Google Classroom')

@section('content')
    <div class="gw-page gw-page--classroom" data-workspace-page>
        <x-google-workspace.service-header
            service="classroom"
            title="Classroom"
            icon="images/google/classroom.webp"
            :search-action="route('google-workspace.classroom.index')"
            :search-value="$keyword"
            search-placeholder="クラス名で検索"
        >
            <input type="hidden" name="state" value="{{ $courseState }}">
        </x-google-workspace.service-header>

        @include('google-workspace._connection')

        @if ($isAuthorized)
            <main class="gw-workspace-content">
                <nav class="gw-classroom-filter" aria-label="クラス状態">
                    <a
                        href="{{ route('google-workspace.classroom.index', [
                            'keyword' => $keyword,
                            'state' => 'ACTIVE',
                        ]) }}"
                        @class(['is-active' => $courseState === 'ACTIVE'])
                    >利用中</a>
                    <a
                        href="{{ route('google-workspace.classroom.index', [
                            'keyword' => $keyword,
                            'state' => 'ARCHIVED',
                        ]) }}"
                        @class(['is-active' => $courseState === 'ARCHIVED'])
                    >アーカイブ</a>
                    <a
                        href="{{ route('google-workspace.classroom.index', [
                            'keyword' => $keyword,
                            'state' => 'ALL',
                        ]) }}"
                        @class(['is-active' => $courseState === 'ALL'])
                    >すべて</a>
                </nav>

                <div
                    class="gw-async-region gw-async-region--embedded"
                    data-workspace-async-region
                    data-workspace-async-url="{{ route('google-workspace.classroom.content', request()->query()) }}"
                    aria-busy="true"
                >
                    @include('google-workspace._async-loading', ['label' => 'Google Classroom'])
                </div>
            </main>
        @endif
    </div>
@endsection
