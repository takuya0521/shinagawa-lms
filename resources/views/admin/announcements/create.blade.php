@extends('layouts.app')

@section('page-class', 'page-pattern-form page-admin-announcements-create')

@section('title', 'お知らせ投稿')
@section('header-title', 'お知らせ投稿')

@section('content')
    <section class="mx-auto max-w-5xl p-6 lms-panel">

        <form method="POST" action="{{ route('admin.announcements.store') }}" class="">
            @csrf
            @include('admin.announcements._form')
        </form>
    </section>
@endsection
