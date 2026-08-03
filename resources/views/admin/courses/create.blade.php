@extends('layouts.app')

@section('page-style', 'resources/css/pages/admin/courses/create.css')
@section('page-class', 'page-pattern-form page-admin-courses-create')

@section('title', '授業登録')
@section('header-title', '授業登録')

@section('content')
    <section class="mx-auto max-w-5xl space-y-6">
        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <div class="border-b border-slate-200 pb-5">
                <h1 class="text-2xl font-bold text-slate-900">
                    授業登録
                </h1>

                <p class="mt-2 text-sm text-slate-600">
                    年度、学年、クラス、科目、担当教員などを設定して授業を登録します。
                </p>
            </div>

            <form
                method="POST"
                action="{{ route('admin.courses.store') }}"
                class="mt-6"
            >
                @csrf

                @include('admin.courses._form')
            </form>
        </div>
    </section>
@endsection
