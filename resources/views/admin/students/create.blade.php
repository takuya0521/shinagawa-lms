@extends('layouts.app')

@section('page-class', 'page-pattern-form page-admin-students-create')

@section('title', '生徒登録')
@section('header-title', '生徒登録')

@section('content')
    <div class="mx-auto max-w-5xl">

        <form
            method="POST"
            action="{{ route('admin.students.store') }}"
            class=" p-6 lms-panel"
        >
            @csrf

            @include('admin.students._form', [
                'submitLabel' => '登録する',
                'cancelUrl' => route('admin.students.index'),
            ])
        </form>
    </div>
@endsection
