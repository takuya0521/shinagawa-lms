@extends('layouts.app')

@section('page-style', 'resources/css/pages/google-workspace/workspace.css')
@section('page-script', 'resources/js/google-workspace.js')
@section('page-class', 'page-google-workspace page-google-workspace-forms')
@section('title', 'Google Forms')
@section('header-title', 'Google Forms')

@section('content')
    <div class="gw-page gw-page--forms" data-workspace-page>
        <x-google-workspace.service-header
            service="forms"
            title="Forms"
            icon="images/google/forms.svg"
            :search-action="route('google-workspace.forms.index')"
            :search-value="$keyword"
            search-placeholder="フォーム名で検索"
        />

        @include('google-workspace._connection')

        @if ($isAuthorized)
            <main class="gw-workspace-content">
                <details class="gw-panel gw-form-create" @if ($errors->any()) open @endif>
                    <summary>新しいフォームを作成</summary>
                    <form class="gw-form" method="POST" action="{{ route('google-workspace.forms.store') }}">
                        @csrf
                        <label>
                            <span>フォーム名</span>
                            <input
                                type="text"
                                name="title"
                                value="{{ old('title') }}"
                                maxlength="200"
                                required
                            >
                        </label>
                        <label>
                            <span>説明</span>
                            <textarea name="description" maxlength="5000">{{ old('description') }}</textarea>
                        </label>
                        <label class="gw-check">
                            <input type="checkbox" name="publish" value="1" @checked(old('publish', true))>
                            <span>作成後すぐに公開して回答を受け付ける</span>
                        </label>
                        <div class="gw-actions">
                            <button class="gw-button gw-button--primary" type="submit">フォームを作成</button>
                        </div>
                    </form>
                </details>

                <div
                    class="gw-async-region gw-async-region--embedded"
                    data-workspace-async-region
                    data-workspace-async-url="{{ route('google-workspace.forms.content', request()->query()) }}"
                    aria-busy="true"
                >
                    @include('google-workspace._async-loading', ['label' => 'Google Forms'])
                </div>
            </main>
        @endif
    </div>
@endsection
