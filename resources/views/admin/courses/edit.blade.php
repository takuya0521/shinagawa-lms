@extends('layouts.app')

@section('page-style', 'resources/css/pages/admin/courses/edit.css')
@section('page-class', 'page-pattern-form page-admin-courses-edit')

@section('title', '授業編集')
@section('header-title', '授業編集')

@section('content')
    <section class="mx-auto max-w-5xl space-y-6">
        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <div class="border-b border-slate-200 pb-5">
                <h1 class="text-2xl font-bold text-slate-900">
                    授業編集
                </h1>

                <p class="mt-2 text-sm text-slate-600">
                    授業の対象年度、クラス、科目、担当教員などを変更します。
                </p>
            </div>

            <form
                method="POST"
                action="{{ route(
                    'admin.courses.update',
                    $course,
                ) }}"
                class="mt-6"
            >
                @csrf
                @method('PUT')

                @include('admin.courses._form')
            </form>
        </div>
    </section>
@endsection
