@extends('layouts.app')

@section('page-class', 'page-pattern-form page-admin-external-links-edit')

@section('title', '外部リンク編集')
@section('header-title', '外部リンク編集')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <section class="p-6 lms-panel">
            <form method="POST" action="{{ route('admin.external-links.update', $externalLink) }}" class="">
                @csrf
                @method('PUT')
                @include('admin.external-links._form')
            </form>
        </section>
    </div>
@endsection
