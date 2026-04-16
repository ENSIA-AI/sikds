@extends('errors.minimal')

@section('icon', 'fa-regular fa-compass')
@section('title', __('Page introuvable'))
@section('code', '404')
@section('message', __('La page demandee n\'existe pas ou a ete deplacee. Verifiez l\'URL puis reessayez.'))

