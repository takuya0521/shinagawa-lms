@extends('layouts.app')

@section('page-class', 'page-pattern-form page-admin-users-edit')

@section('title', 'ユーザー編集')
@section('header-title', 'ユーザー編集')

@section('content')
    <div class="mx-auto max-w-4xl">

        <form
            method="POST"
            action="{{ route('admin.users.update', $user) }}"
            class=" p-6 lms-panel"
        >
            @csrf
            @method('PUT')

            @include('admin.users._form', [
                'submitLabel' => '更新する',
            ])
        </form>
    </div>
@endsection
