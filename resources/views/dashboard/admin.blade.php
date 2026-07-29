@extends('layouts.app')

@section('title', '管理者ダッシュボード')
@section('header-title', '管理者ダッシュボード')

@section('content')
    <section class="rounded-2xl bg-white p-8 shadow-sm">
        <h1 class="text-2xl font-bold">
            管理者ダッシュボード
        </h1>

        <p class="mt-3 text-slate-600">
            ユーザー、生徒、教員、クラス、科目、授業、時間割、出欠、評価を管理します。
        </p>

        <div class="mt-6 flex flex-wrap gap-3">
            <a
                href="{{ route('admin.users.index') }}"
                class="rounded-lg bg-slate-900 px-5 py-3 font-semibold text-white hover:bg-slate-700"
            >
                ユーザー管理
            </a>

            <a
                href="{{ route('admin.students.index') }}"
                class="rounded-lg border border-slate-300 px-5 py-3 font-semibold hover:bg-slate-50"
            >
                生徒管理
            </a>

            <a
                href="{{ route('admin.teachers.index') }}"
                class="rounded-lg border border-slate-300 px-5 py-3 font-semibold hover:bg-slate-50"
            >
                教員管理
            </a>

            <a
                href="{{ route('admin.class-groups.index') }}"
                class="rounded-lg border border-slate-300 px-5 py-3 font-semibold hover:bg-slate-50"
            >
                クラス管理
            </a>

            <a
                href="{{ route('admin.subjects.index') }}"
                class="rounded-lg border border-slate-300 px-5 py-3 font-semibold hover:bg-slate-50"
            >
                科目管理
            </a>
        </div>
    </section>
@endsection