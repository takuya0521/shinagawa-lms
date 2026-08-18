@extends('layouts.app')

@section('page-class', 'page-pattern-form page-admin-users-create')

@section('title', 'ユーザー登録')
@section('header-title', 'ユーザー登録')

@section('content')
    <div class="mx-auto max-w-4xl">

        <form
            method="POST"
            action="{{ route('admin.users.store') }}"
            class=" p-6 lms-panel"
        >
            @csrf

            @include('admin.users._form', [
                'submitLabel' => '登録する',
            ])
        </form>
    </div>
@endsection
