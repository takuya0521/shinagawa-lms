@extends('errors.layout')

@section('page-style', 'resources/css/pages/errors/404.css')
@section('page-class', 'error-page-404')

@section('title', 'ページが見つかりません')
@section('code', '404')
@section('heading', 'ページが見つかりません')
@section('message', 'URLが変更されたか、対象データが削除された可能性があります。メニューから目的の画面を開き直してください。')
