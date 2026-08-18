@extends('layouts.app')

@section('page-class', 'page-pattern-announcement-list page-student-announcements-index')

@section('title', 'お知らせ')
@section('header-title', 'お知らせ')

@section('content')
    <div class="space-y-6">
        <section class="p-6 lms-panel">
            @include('announcements._filters', [
                'action' => route('student.announcements.index'),
            ])
        </section>
        @include('announcements._cards', [
            'showRoute' => 'student.announcements.show',
        ])
    </div>
@endsection
