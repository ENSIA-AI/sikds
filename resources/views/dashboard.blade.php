@extends('layouts.app')

@section('content')
    <h1 class="text-2xl font-semibold text-slate-800">Tableau de bord</h1>
    <p class="mt-2 text-slate-600">Connecté en tant que {{ auth()->user()->full_name ?? auth()->user()->email }}</p>
@endsection
