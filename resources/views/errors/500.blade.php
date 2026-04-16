@extends('errors.minimal')

@section('icon', 'fa-solid fa-triangle-exclamation')
@section('title', __('Erreur serveur'))
@section('code', '500')
@section('message', __('Une erreur interne est survenue. Notre equipe a ete notifiee. Veuillez reessayer plus tard.'))

