@extends('layouts.app')

@section('page-class', 'page-pattern-form page-admin-students-edit')

@section('title', '生徒編集')
@section('header-title', '生徒編集')

@section('content')
    <div class="mx-auto max-w-5xl">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-end">

            <a
                href="{{ route('admin.students.show', $student) }}"
                class="rounded-lg border lms-border-neutral-default px-5 py-3 font-semibold lms-hover-bg-neutral-subtle"
            >
                詳細へ戻る
            </a>
        </header>

        <form
            method="POST"
            action="{{ route('admin.students.update', $student) }}"
            class="mt-6 p-6 lms-panel"
        >
            @csrf
            @method('PUT')

            @include('admin.students._form', [
                'submitLabel' => '更新する',
                'cancelUrl' => route('admin.students.show', $student),
            ])
        </form>
    </div>
@endsection
