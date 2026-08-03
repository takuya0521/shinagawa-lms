@extends('layouts.app')

@section('page-style', 'resources/css/pages/admin/teachers/create.css')
@section('page-class', 'page-pattern-form page-admin-teachers-create')

@section('title', '教員登録')
@section('header-title', '教員登録')

@section('content')
    <div class="mx-auto max-w-5xl">
        <header>
            <p class="text-sm font-semibold text-slate-500">
                TEACHER REGISTRATION
            </p>

            <h1 class="mt-1 text-2xl font-bold text-slate-900">
                教員登録
            </h1>

            <p class="mt-2 text-sm text-slate-600">
                教員情報とログインアカウントを同時に登録します。
            </p>
        </header>

        <form
            method="POST"
            action="{{ route('admin.teachers.store') }}"
            class="mt-6 rounded-2xl bg-white p-6 shadow-sm"
        >
            @csrf

            @include('admin.teachers._form', [
                'submitLabel' => '登録する',
                'cancelUrl' => route('admin.teachers.index'),
            ])
        </form>
    </div>
@endsection
