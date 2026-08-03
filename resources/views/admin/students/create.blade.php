@extends('layouts.app')

@section('page-style', 'resources/css/pages/admin/students/create.css')
@section('page-class', 'page-pattern-form page-admin-students-create')

@section('title', '生徒登録')
@section('header-title', '生徒登録')

@section('content')
    <div class="mx-auto max-w-5xl">
        <header>
            <p class="text-sm font-semibold text-slate-500">
                STUDENT REGISTRATION
            </p>

            <h1 class="mt-1 text-2xl font-bold text-slate-900">
                生徒登録
            </h1>

            <p class="mt-2 text-sm text-slate-600">
                生徒情報とログインアカウントを同時に登録します。
            </p>
        </header>

        <form
            method="POST"
            action="{{ route('admin.students.store') }}"
            class="mt-6 rounded-2xl bg-white p-6 shadow-sm"
        >
            @csrf

            @include('admin.students._form', [
                'submitLabel' => '登録する',
                'cancelUrl' => route('admin.students.index'),
            ])
        </form>
    </div>
@endsection
