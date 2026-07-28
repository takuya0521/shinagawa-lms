@extends('layouts.app')

@section('title', '管理者ダッシュボード')
@section('header-title', '管理者ダッシュボード')

@section('content')
    <section class="rounded-2xl bg-white p-8 shadow-sm">
        <h1 class="text-2xl font-bold">
            管理者ダッシュボード
        </h1>

        <p class="mt-3 text-slate-600">
            ユーザー、授業、時間割、出欠、評価を管理します。
        </p>
    </section>
@endsection