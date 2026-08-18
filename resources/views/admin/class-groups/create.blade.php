@extends('layouts.app')

@section('page-class', 'page-pattern-form page-admin-class-groups-create')

@section('title', 'クラス登録')
@section('header-title', 'クラス登録')

@section('content')
    <div class="mx-auto max-w-4xl">

        <form
            method="POST"
            action="{{ route('admin.class-groups.store') }}"
            class=" p-6 lms-panel"
        >
            @csrf

            @include('admin.class-groups._form', [
                'submitLabel' => '登録する',
            ])
        </form>
    </div>
@endsection
