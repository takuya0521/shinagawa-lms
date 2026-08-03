@extends('layouts.app')

@section('page-style', 'resources/css/pages/admin/external-links/create.css')
@section('page-class', 'page-pattern-form page-admin-external-links-create')

@section('title', '外部リンク登録')
@section('header-title', '外部リンク登録')

@section('content')
    <section class="mx-auto max-w-5xl rounded-2xl bg-white p-6 shadow-sm">
        <div class="border-b border-slate-200 pb-5">
            <h1 class="text-2xl font-bold">外部リンク登録</h1>
            <p class="mt-2 text-sm text-slate-600">Google Calendar・Forms・Chat・Driveなどの表示先を登録します。</p>
        </div>
        <form method="POST" action="{{ route('admin.external-links.store') }}" class="mt-6">
            @csrf
            @include('admin.external-links._form')
        </form>
    </section>
@endsection
