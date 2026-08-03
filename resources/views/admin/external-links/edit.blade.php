@extends('layouts.app')

@section('page-style', 'resources/css/pages/admin/external-links/edit.css')
@section('page-class', 'page-pattern-form page-admin-external-links-edit')

@section('title', '外部リンク編集')
@section('header-title', '外部リンク編集')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <section class="rounded-2xl bg-white p-6 shadow-sm">
            <div class="border-b border-slate-200 pb-5">
                <h1 class="text-2xl font-bold">外部リンク編集</h1>
                <p class="mt-2 text-sm text-slate-600">リンク先・公開範囲・表示順を変更します。</p>
            </div>
            <form method="POST" action="{{ route('admin.external-links.update', $externalLink) }}" class="mt-6">
                @csrf
                @method('PUT')
                @include('admin.external-links._form')
            </form>
        </section>
    </div>
@endsection
