@extends('layouts.app')

@section('page-class', 'page-pattern-form page-admin-teachers-edit')

@section('title', '教員編集')
@section('header-title', '教員編集')

@section('content')
    <div class="mx-auto max-w-5xl">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-end">

            <a
                href="{{ route('admin.teachers.show', $teacher) }}"
                class="rounded-lg border lms-border-neutral-default px-5 py-3 font-semibold lms-hover-bg-neutral-subtle"
            >
                詳細へ戻る
            </a>
        </header>

        <form
            method="POST"
            action="{{ route('admin.teachers.update', $teacher) }}"
            class="mt-6 p-6 lms-panel"
        >
            @csrf
            @method('PUT')

            @include('admin.teachers._form', [
                'submitLabel' => '更新する',
                'cancelUrl' => route('admin.teachers.show', $teacher),
            ])
        </form>
    </div>
@endsection
