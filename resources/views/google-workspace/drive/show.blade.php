@extends('layouts.app')

@section('page-style', 'resources/css/pages/google-workspace/workspace.css')
@section('page-script', 'resources/js/google-workspace.js')
@section('page-class', 'page-google-workspace page-google-workspace-drive page-google-workspace-drive-show')
@section('title', 'Google Drive ファイル詳細')
@section('header-title', 'Google Drive')

@section('content')
    <div class="gw-page" data-workspace-page>
        <x-google-workspace.service-header
            service="drive"
            title="Drive"
            icon="images/google/drive.svg"
        />

        @include('google-workspace._connection')

        @if ($isAuthorized)
            <div
                class="gw-async-region"
                data-workspace-async-region
                data-workspace-async-url="{{ route('google-workspace.drive.show-content', ['fileId' => $fileId]) }}"
                aria-busy="true"
            >
                @include('google-workspace._async-loading', ['label' => 'ファイル詳細'])
            </div>
        @endif
    </div>
@endsection
