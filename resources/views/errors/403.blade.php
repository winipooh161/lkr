@extends('errors.layout')

@section('title', 'Доступ запрещен')
@section('code', '403')
@section('icon', '🚫')
@section('message')
    У вас нет прав доступа к запрошенной странице.
@endsection
