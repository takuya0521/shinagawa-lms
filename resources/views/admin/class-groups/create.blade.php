@extends('layouts.app')

@section('page-style', 'resources/css/pages/admin/class-groups/create.css')
@section('page-class', 'page-pattern-form page-admin-class-groups-create')

@section('title', 'クラス登録')
@section('header-title', 'クラス登録')

@section('content')
    <div class="mx-auto max-w-4xl">
        <header>
            <p class="text-sm font-semibold text-slate-500">
                CLASS GROUP REGISTRATION
            </p>

            <h1 class="mt-1 text-2xl font-bold text-slate-900">
                クラス登録
            </h1>
        </header>

        <form
            method="POST"
            action="{{ route('admin.class-groups.store') }}"
            class="mt-6 rounded-2xl bg-white p-6 shadow-sm"
        >
            @csrf

            @include('admin.class-groups._form', [
                'submitLabel' => '登録する',
            ])
        </form>
    </div>
@endsection
