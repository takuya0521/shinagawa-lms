@extends('layouts.app')

@section('page-class', 'page-pattern-announcement-detail page-student-announcements-show')

@section('title', 'お知らせ詳細')
@section('header-title', 'お知らせ詳細')

@section('content')
    <div class="mx-auto max-w-4xl space-y-4">
        <a
            href="{{ route('student.announcements.index') }}"
            class="inline-flex text-sm font-semibold lms-link-primary"
        >← お知らせ一覧へ戻る</a>
        @include('announcements._detail')
    </div>
@endsection
