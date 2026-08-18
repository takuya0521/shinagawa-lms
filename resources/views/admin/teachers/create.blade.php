@extends('layouts.app')

@section('page-class', 'page-pattern-form page-admin-teachers-create')

@section('title', '教員登録')
@section('header-title', '教員登録')

@section('content')
    <div class="mx-auto max-w-5xl">

        <form
            method="POST"
            action="{{ route('admin.teachers.store') }}"
            class=" p-6 lms-panel"
        >
            @csrf

            @include('admin.teachers._form', [
                'submitLabel' => '登録する',
                'cancelUrl' => route('admin.teachers.index'),
            ])
        </form>
    </div>
@endsection
