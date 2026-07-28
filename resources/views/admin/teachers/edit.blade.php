@extends('layouts.app')

@section('title', '教員編集')
@section('header-title', '教員編集')

@section('content')
    <div class="mx-auto max-w-5xl">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-slate-500">
                    TEACHER EDIT
                </p>

                <h1 class="mt-1 text-2xl font-bold text-slate-900">
                    教員編集
                </h1>

                <p class="mt-2 text-sm text-slate-600">
                    教員情報とログインアカウントを更新します。
                </p>
            </div>

            <a
                href="{{ route('admin.teachers.show', $teacher) }}"
                class="rounded-lg border border-slate-300 px-5 py-3 font-semibold hover:bg-slate-50"
            >
                詳細へ戻る
            </a>
        </header>

        <form
            method="POST"
            action="{{ route('admin.teachers.update', $teacher) }}"
            class="mt-6 rounded-2xl bg-white p-6 shadow-sm"
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