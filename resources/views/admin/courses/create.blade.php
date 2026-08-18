@extends('layouts.app')

@section('page-class', 'page-pattern-form page-admin-courses-create')

@section('title', '授業登録')
@section('header-title', '授業登録')

@section('content')
    <section class="mx-auto max-w-5xl space-y-6">
        <div class="p-6 lms-panel">

            <form
                method="POST"
                action="{{ route('admin.courses.store') }}"
            >
                @csrf

                @include('admin.courses._form')
            </form>
        </div>
    </section>
@endsection
