@extends('layouts.app')

@section('page-style', 'resources/css/pages/teacher/announcements/index.css')
@section('page-class', 'page-pattern-announcement-list page-teacher-announcements-index')

@section('title', '掲示板')
@section('header-title', '掲示板')

@section('content')
    <div class="space-y-6">
        <section class="rounded-2xl bg-white p-6 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">T-011</p>
            <h1 class="mt-1 text-2xl font-bold">掲示板</h1>
            <p class="mt-2 text-sm text-slate-600">学校全体・担当学年・担当クラス向けのお知らせを確認します。</p>
        </section>
        <section class="rounded-2xl bg-white p-6 shadow-sm">
            @include('announcements._filters', [
                'action' => route('teacher.announcements.index'),
            ])
        </section>
        @include('announcements._cards', [
            'showRoute' => 'teacher.announcements.show',
        ])
    </div>
@endsection
