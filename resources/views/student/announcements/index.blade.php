@extends('layouts.app')

@section('page-style', 'resources/css/pages/student/announcements/index.css')
@section('page-class', 'page-pattern-announcement-list page-student-announcements-index')

@section('title', 'お知らせ')
@section('header-title', 'お知らせ')

@section('content')
    <div class="space-y-6">
        <section class="rounded-2xl bg-white p-6 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">S-004</p>
            <h1 class="mt-1 text-2xl font-bold">お知らせ</h1>
            <p class="mt-2 text-sm text-slate-600">自分の学年・クラスに関係する学校からのお知らせを確認します。</p>
        </section>
        <section class="rounded-2xl bg-white p-6 shadow-sm">
            @include('announcements._filters', [
                'action' => route('student.announcements.index'),
            ])
        </section>
        @include('announcements._cards', [
            'showRoute' => 'student.announcements.show',
        ])
    </div>
@endsection
