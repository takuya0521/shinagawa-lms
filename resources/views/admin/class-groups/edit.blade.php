@extends('layouts.app')

@section('page-class', 'page-pattern-form page-admin-class-groups-edit')

@section('title', 'クラス編集')
@section('header-title', 'クラス編集')

@section('content')
    <div class="mx-auto max-w-4xl">

        <form
            method="POST"
            action="{{ route(
                'admin.class-groups.update',
                $classGroup,
            ) }}"
            class=" p-6 lms-panel"
        >
            @csrf
            @method('PUT')

            @include('admin.class-groups._form', [
                'submitLabel' => '更新する',
            ])
        </form>
    </div>
@endsection
