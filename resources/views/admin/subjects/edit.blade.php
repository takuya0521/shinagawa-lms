@extends('layouts.app')

@section('page-class', 'page-pattern-form page-admin-subjects-edit')

@section('title', '科目編集')
@section('header-title', '科目編集')

@section('content')
    <section class="mx-auto max-w-4xl space-y-6">
        <div class="p-6 lms-panel">

            <form
                method="POST"
                action="{{ route(
                    'admin.subjects.update',
                    $subject,
                ) }}"
            >
                @csrf
                @method('PUT')

                @include('admin.subjects._form')
            </form>
        </div>
    </section>
@endsection
