@extends('layouts.app')

@section('title', '科目登録')
@section('header-title', '科目登録')

@section('content')
    <section class="mx-auto max-w-4xl space-y-6">
        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <div class="border-b border-slate-200 pb-5">
                <h1 class="text-2xl font-bold text-slate-900">
                    科目登録
                </h1>

                <p class="mt-2 text-sm text-slate-600">
                    時間割で使用する科目を新しく登録します。
                </p>
            </div>

            <form
                method="POST"
                action="{{ route('admin.subjects.store') }}"
                class="mt-6"
            >
                @csrf

                @include('admin.subjects._form')
            </form>
        </div>
    </section>
@endsection