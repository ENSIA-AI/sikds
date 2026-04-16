@extends('errors.minimal')

@section('icon', 'fa-solid fa-screwdriver-wrench')
@section('title', __('Maintenance en cours'))
@section('code', '503')
@section('message', __('Le service est temporairement indisponible pour maintenance. Veuillez revenir dans quelques instants.'))

