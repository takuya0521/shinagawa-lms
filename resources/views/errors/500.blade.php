@extends('errors.layout')

@section('page-style', 'resources/css/pages/errors/500.css')
@section('page-class', 'error-page-500')

@section('title', 'システムエラー')
@section('code', '500')
@section('heading', '処理中にエラーが発生しました')
@section('message', '時間を置いて再度お試しください。解消しない場合は、発生時刻と操作内容を管理者へ連絡してください。')
