@extends('errors.layout')

@section('title', 'Ошибка сервера')
@section('code', '500')
@section('icon', '⚠️')
@section('message')
    Произошла внутренняя ошибка сервера.
    Наша команда уже получила уведомление и работает над устранением проблемы.
@endsection
