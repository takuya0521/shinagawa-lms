@extends('errors.layout')

@section('page-style', 'resources/css/pages/errors/403.css')
@section('page-class', 'error-page-403')

@section('title', 'アクセスできません')
@section('code', '403')
@section('heading', 'この画面を表示する権限がありません')
@section('message', 'ログイン中のロールでは、この機能を利用できません。必要な場合は管理者へ確認してください。')
