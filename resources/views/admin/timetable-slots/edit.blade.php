@extends('layouts.app')

@section('page-style', 'resources/css/pages/admin/timetable-slots/edit.css')
@section('page-class', 'page-pattern-form page-admin-timetable-slots-edit')

@section('title', '時間割編集')
@section('header-title', '時間割編集')

@section('content')
    <section class="mx-auto max-w-5xl space-y-6">
        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <div class="border-b border-slate-200 pb-5">
                <h1 class="text-2xl font-bold text-slate-900">
                    時間割編集
                </h1>

                <p class="mt-2 text-sm text-slate-600">
                    授業の曜日・時限・時刻・状態を変更します。
                </p>
            </div>

            <form
                method="POST"
                action="{{ route(
                    'admin.timetable-slots.update',
                    $timetableSlot,
                ) }}"
                class="mt-6"
            >
                @csrf
                @method('PUT')

                @include('admin.timetable-slots._form')
            </form>
        </div>
    </section>
@endsection
