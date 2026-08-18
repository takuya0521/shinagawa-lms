@extends('layouts.app')

@section('page-class', 'page-pattern-form page-admin-courses-edit')

@section('title', '授業編集')
@section('header-title', '授業編集')

@section('content')
    <section class="mx-auto max-w-5xl space-y-6">
        <div class="p-6 lms-panel">

            <form
                method="POST"
                action="{{ route(
                    'admin.courses.update',
                    $course,
                ) }}"
            >
                @csrf
                @method('PUT')

                @include('admin.courses._form')
            </form>
        </div>
    </section>
@endsection
