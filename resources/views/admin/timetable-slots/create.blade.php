@extends('layouts.app')

@section('page-class', 'page-pattern-form page-admin-timetable-slots-create')

@section('title', '時間割登録')
@section('header-title', '時間割登録')

@section('content')
    <section class="mx-auto max-w-5xl space-y-6">
        <div class="p-6 lms-panel">

            <form
                method="POST"
                action="{{ route('admin.timetable-slots.store') }}"
            >
                @csrf

                @include('admin.timetable-slots._form')
            </form>
        </div>
    </section>
@endsection
