@extends('layouts.app')

@section('page-style', 'resources/css/pages/google-workspace/workspace.css')
@section('page-script', 'resources/js/google-workspace.js')
@section('page-class', 'page-google-workspace page-google-workspace-drive')
@section('title', 'Google Drive')
@section('header-title', 'Google Drive')

@section('content')
    <div class="gw-page gw-page--drive" data-workspace-page>
        <x-google-workspace.service-header
            service="drive"
            title="Drive"
            icon="images/google/drive.svg"
            :search-action="route('google-workspace.drive.index')"
            :search-value="$keyword"
            search-placeholder="ドライブで検索"
        >
            <input type="hidden" name="parent_id" value="{{ $parentId }}">
            <input type="hidden" name="view" value="{{ $viewMode }}">
            <input type="hidden" name="drive_id" value="{{ $driveId }}">
        </x-google-workspace.service-header>

        @include('google-workspace._connection')

        @if ($isAuthorized)
            <div
                class="gw-async-region"
                data-workspace-async-region
                data-workspace-async-url="{{ route('google-workspace.drive.content', request()->query()) }}"
                aria-busy="true"
            >
                @include('google-workspace._async-loading', ['label' => 'Google Drive'])
            </div>
        @endif
    </div>
@endsection
