@extends('errors.minimal')

@section('icon', 'fa-solid fa-lock')
@section('title', __('Acces refuse'))
@section('code', '403')
@section('message', __($exception->getMessage() ?: 'Vous ne disposez pas des permissions necessaires pour acceder a cette ressource.'))

