@extends('errors.minimal')

@section('icon', 'fa-solid fa-gauge-high')
@section('title', __('Trop de requetes'))
@section('code', '429')
@section('message', __('Trop de requetes ont ete envoyees en peu de temps. Veuillez patienter avant de reessayer.'))

