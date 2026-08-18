@extends('layouts.app')

@section('page-class', 'page-pattern-form page-admin-external-links-create')

@section('title', '外部リンク登録')
@section('header-title', '外部リンク登録')

@section('content')
    <section class="mx-auto max-w-5xl p-6 lms-panel">
        <form method="POST" action="{{ route('admin.external-links.store') }}" class="">
            @csrf
            @include('admin.external-links._form')
        </form>
    </section>
@endsection
