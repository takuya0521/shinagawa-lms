@extends('layouts.app')

@section('page-style', 'resources/css/pages/teacher/announcements/show.css')
@section('page-class', 'page-pattern-announcement-detail page-teacher-announcements-show')

@section('title', 'お知らせ詳細')
@section('header-title', 'お知らせ詳細')

@section('content')
    <div class="mx-auto max-w-4xl space-y-4">
        <a href="{{ route('teacher.announcements.index') }}" class="inline-flex text-sm font-semibold text-blue-700 hover:text-blue-900">← 掲示板へ戻る</a>
        @include('announcements._detail')
    </div>
@endsection
