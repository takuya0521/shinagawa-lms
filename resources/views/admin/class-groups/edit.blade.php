@extends('layouts.app')

@section('page-style', 'resources/css/pages/admin/class-groups/edit.css')
@section('page-class', 'page-pattern-form page-admin-class-groups-edit')

@section('title', 'クラス編集')
@section('header-title', 'クラス編集')

@section('content')
    <div class="mx-auto max-w-4xl">
        <header>
            <p class="text-sm font-semibold text-slate-500">
                CLASS GROUP EDIT
            </p>

            <h1 class="mt-1 text-2xl font-bold text-slate-900">
                クラス編集
            </h1>
        </header>

        <form
            method="POST"
            action="{{ route(
                'admin.class-groups.update',
                $classGroup,
            ) }}"
            class="mt-6 rounded-2xl bg-white p-6 shadow-sm"
        >
            @csrf
            @method('PUT')

            @include('admin.class-groups._form', [
                'submitLabel' => '更新する',
            ])
        </form>
    </div>
@endsection
