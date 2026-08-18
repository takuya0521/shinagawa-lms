@extends('layouts.app')

@section('page-class', 'page-pattern-form page-admin-timetable-slots-edit')

@section('title', '時間割編集')
@section('header-title', '時間割編集')

@section('content')
    <section class="mx-auto max-w-5xl space-y-6">
        <div class="p-6 lms-panel">

            <form
                method="POST"
                action="{{ route(
                    'admin.timetable-slots.update',
                    $timetableSlot,
                ) }}"
            >
                @csrf
                @method('PUT')

                @include('admin.timetable-slots._form')
            </form>
        </div>
    </section>
@endsection
