@extends('layouts.app')

@section('page-style', 'resources/css/pages/admin/external-links/index.css')
@section('page-class', 'page-pattern-list page-admin-external-links-index')

@section('title', '外部リンク管理')
@section('header-title', '外部リンク管理')

@section('content')
    <div class="space-y-6">
        <section class="flex flex-col gap-4 rounded-2xl bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold">外部リンク管理</h1>
                <p class="mt-2 text-sm text-slate-600">生徒マイページなどに表示するGoogleサービスへのリンクを管理します。</p>
            </div>
            <a href="{{ route('admin.external-links.create') }}" class="rounded-lg bg-slate-900 px-5 py-3 text-center font-semibold text-white hover:bg-slate-700">外部リンクを登録</a>
        </section>


        <section class="rounded-2xl bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('admin.external-links.index') }}" class="grid gap-4 lg:grid-cols-5">
                <div class="lg:col-span-2">
                    <label for="keyword" class="block text-sm font-semibold text-slate-700">キーワード</label>
                    <input id="keyword" name="keyword" type="search" value="{{ $keyword }}" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="リンク名・URL">
                </div>
                <div>
                    <label for="link_type" class="block text-sm font-semibold text-slate-700">リンク種別</label>
                    <select id="link_type" name="link_type" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="">すべて</option>
                        @foreach ($linkTypes as $linkType)
                            <option value="{{ $linkType->value }}" @selected($selectedLinkType === $linkType->value)>{{ $linkType->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="scope_type" class="block text-sm font-semibold text-slate-700">公開範囲</label>
                    <select id="scope_type" name="scope_type" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="">すべて</option>
                        @foreach ($scopeTypes as $scopeType)
                            <option value="{{ $scopeType->value }}" @selected($selectedScopeType === $scopeType->value)>{{ $scopeType->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="status" class="block text-sm font-semibold text-slate-700">状態</label>
                    <select id="status" name="status" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="">すべて</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected($selectedStatus === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-5 flex justify-end gap-3">
                    <a href="{{ route('admin.external-links.index') }}" class="rounded-lg border border-slate-300 px-5 py-2.5 font-semibold">クリア</a>
                    <button type="submit" class="rounded-lg bg-slate-900 px-5 py-2.5 font-semibold text-white">検索</button>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50"><tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">リンク</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">公開範囲</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-slate-500">表示順</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500">状態</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-slate-500">操作</th>
                    </tr></thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($externalLinks as $externalLink)
                            @php
                                $scopeLabel = $externalLink->scope_type === \App\Enums\ExternalLinkScopeType::Global
                                    ? '全体'
                                    : ($scopeLabels[$externalLink->scope_type->value][$externalLink->scope_id] ?? '対象不明');
                            @endphp
                            <tr>
                                <td class="px-5 py-4 text-sm">
                                    <p class="font-semibold text-slate-900">{{ $externalLink->link_name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $externalLink->link_type->label() }}</p>
                                    <a href="{{ $externalLink->url }}" target="_blank" rel="noopener noreferrer" class="mt-1 block max-w-md truncate text-xs font-semibold text-blue-700">{{ $externalLink->url }}</a>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-700">
                                    <p>{{ $externalLink->scope_type->label() }}</p>
                                    @if ($externalLink->scope_type !== \App\Enums\ExternalLinkScopeType::Global)
                                        <p class="mt-1 text-xs text-slate-500">{{ $scopeLabel }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-center text-sm">{{ $externalLink->display_order }}</td>
                                <td class="px-5 py-4 text-sm">
                                    <span @class([
                                        'rounded-full px-3 py-1 text-xs font-semibold',
                                        'bg-emerald-100 text-emerald-800' => $externalLink->status === \App\Enums\MasterStatus::Active,
                                        'bg-slate-100 text-slate-700' => $externalLink->status === \App\Enums\MasterStatus::Inactive,
                                    ])>{{ $externalLink->status->label() }}</span>
                                </td>
                                <td class="px-5 py-4 text-right text-sm"><a href="{{ route('admin.external-links.edit', $externalLink) }}" class="font-semibold text-blue-700">編集</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-12 text-center text-sm text-slate-500">条件に一致する外部リンクはありません。</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($externalLinks->hasPages())<div class="border-t border-slate-200 px-6 py-4">{{ $externalLinks->links() }}</div>@endif
        </section>
    </div>
@endsection
