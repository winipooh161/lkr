@extends('errors.layout')

@section('title', 'Ошибка запроса')
@section('code', '422')
@section('icon', '✗')
@section('message')
    Предоставленные данные не прошли валидацию.
    Пожалуйста, проверьте правильность введенной информации и повторите попытку.
@endsection
