@extends('layouts.app')

@section('page-class', 'page-pattern-announcement-list page-teacher-announcements-index')

@section('title', '掲示板')
@section('header-title', '掲示板')

@section('content')
    <div class="space-y-6">
        <section class="p-6 lms-panel">
            @include('announcements._filters', [
                'action' => route('teacher.announcements.index'),
            ])
        </section>
        @include('announcements._cards', [
            'showRoute' => 'teacher.announcements.show',
        ])
    </div>
@endsection
