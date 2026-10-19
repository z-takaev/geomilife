@extends('errors.layout')

@section('title', __('Ошибка сервера'))
@section('code', '500')
@section('heading', __('Что-то пошло не так'))
@section('message', __('Попробуйте обновить страницу или вернитесь позже.'))
