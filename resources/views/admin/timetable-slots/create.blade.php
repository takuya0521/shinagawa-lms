@extends('layouts.app')

@section('page-style', 'resources/css/pages/admin/timetable-slots/create.css')
@section('page-class', 'page-pattern-form page-admin-timetable-slots-create')

@section('title', '時間割登録')
@section('header-title', '時間割登録')

@section('content')
    <section class="mx-auto max-w-5xl space-y-6">
        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <div class="border-b border-slate-200 pb-5">
                <h1 class="text-2xl font-bold text-slate-900">
                    時間割登録
                </h1>

                <p class="mt-2 text-sm text-slate-600">
                    授業を曜日・時限へ割り当て、必要に応じて開始時刻と終了時刻を設定します。
                </p>
            </div>

            <form
                method="POST"
                action="{{ route('admin.timetable-slots.store') }}"
                class="mt-6"
            >
                @csrf

                @include('admin.timetable-slots._form')
            </form>
        </div>
    </section>
@endsection
