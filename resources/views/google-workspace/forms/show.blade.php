@extends('layouts.app')

@section('page-style', 'resources/css/pages/google-workspace/workspace.css')
@section('page-script', 'resources/js/google-workspace.js')
@section('page-class', 'page-google-workspace page-google-workspace-forms-show')
@section('title', 'Google Forms 詳細')
@section('header-title', 'Google Forms')

@section('content')
    <div class="gw-page gw-page--forms" data-workspace-page>
        <x-google-workspace.service-header
            service="forms"
            title="Forms"
            icon="images/google/forms.svg"
        />

        @include('google-workspace._connection')

        @if ($isAuthorized)
            <main class="gw-workspace-content">
                <a class="gw-back" href="{{ route('google-workspace.forms.index') }}">
                    <x-google-icon name="chevron-left" :size="18" />
                    <span>フォーム一覧へ戻る</span>
                </a>

                <div
                    class="gw-async-region gw-async-region--embedded"
                    data-workspace-async-region
                    data-workspace-async-url="{{ route('google-workspace.forms.show-content', $formId) }}"
                    aria-busy="true"
                >
                    @include('google-workspace._async-loading', ['label' => 'Google Forms'])
                </div>
            </main>
        @endif
    </div>
@endsection
