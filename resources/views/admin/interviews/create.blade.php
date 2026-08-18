@extends('layouts.app')

@section('page-class', 'page-pattern-form page-admin-interviews-create')

@section('title', '面談記録登録')
@section('header-title', '面談管理')

@section('content')
    <section class="mx-auto max-w-5xl space-y-6">
        <div class="p-6 lms-panel">
            <div class="border-b lms-border-neutral-subtle pb-5">
                <h1 class="text-2xl font-bold lms-text-neutral-strong">面談記録登録</h1>
            </div>

            <form method="POST" action="{{ route('admin.interviews.store') }}" class="mt-6">
                @csrf
                @include('admin.interviews._form')
            </form>
        </div>
    </section>
@endsection
