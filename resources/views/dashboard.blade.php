@extends('layouts.app')
@php($activeNav = 'dashboard')
@section('page_title', __('Tableau de Bord'))
@section('page_subtitle', __("Aperçu de l'activité du système SIKDS"))
@section('content')

    {{-- KPI cards --}}
    <section class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
        @foreach ($kpis as $kpi)
            <x-stat-card :icon="$kpi['icon']" :value="$kpi['value']" :label="$kpi['label']" :trend="$kpi['trend']" />
        @endforeach
    </section>

    {{-- Activity feed + Alerts --}}
    <section class="sikds-grid-panels xl:gap-6">

        <x-dashboard-panel
            :title="__('Activité Récente')"
            :subtitle="__('Actions des utilisateurs en temps réel')"
        >
            <div class="min-h-0 flex-1 divide-y divide-black/10 overflow-y-auto sikds-scroll">
                @forelse ($activities as $activity)
                    <x-activity-item
                        :icon="$activity['icon']"
                        :user="$activity['user']"
                        :action="$activity['action']"
                        :document="$activity['document']"
                        :time="$activity['time']"
                    />
                @empty
                    <p class="px-4 py-6 text-sm sikds-muted-text">{{ __('Aucune activité récente.') }}</p>
                @endforelse
            </div>
            <x-slot:footer>
                <a href="{{ route('audits.index') }}" class="inline-flex items-center hover:underline">
                    {{ __('Voir toute l\'activité →') }}
                </a>
            </x-slot:footer>
        </x-dashboard-panel>

        <x-dashboard-panel
            :title="__('Alertes Système')"
            :subtitle="__('Notifications importantes')"
        >
            <div class="min-h-0 flex-1 space-y-3 overflow-y-hidden px-4 py-4">
                @forelse ($alerts as $alert)
                    <x-alert-item :type="$alert['type']" :message="$alert['message']" :timestamp="$alert['timestamp']" />
                @empty
                    <p class="text-sm sikds-muted-text">{{ __('Aucune alerte active.') }}</p>
                @endforelse
            </div>
            <x-slot:footer>
                @if (auth()->user()?->can('indexing.manage'))
                    <a href="{{ route('indexing.index') }}" class="inline-flex items-center hover:underline">
                        {{ __('Voir toutes les alertes →') }}
                    </a>
                @endif
            </x-slot:footer>
        </x-dashboard-panel>

    </section>

    {{-- Bottom summary cards --}}
    <section class="mt-5 grid gap-5 md:grid-cols-2 xl:grid-cols-3">

        <x-summary-card :title="__('Documents par Statut')">
            <div class="mt-4 space-y-3 text-sm">
                @foreach ($statusStats as $stat)
                    <div class="flex items-center justify-between">
                        <span class="sikds-muted-text">{{ $stat['label'] }}</span>
                        <span class="text-base font-semibold sikds-ink">{{ $stat['value'] }}</span>
                    </div>
                @endforeach
            </div>
        </x-summary-card>

        <x-summary-card :title="__('Tags Populaires')">
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($popularTags as $tag)
                    <span class="sikds-tag" style="{{ $tag['style'] }}">{{ $tag['label'] }}</span>
                @endforeach
            </div>
        </x-summary-card>

        <x-summary-card :title="__('Institutions Actives')">
            <div class="mt-4 space-y-3 text-sm">
                @forelse ($activeInstitutions as $institution)
                    <div class="flex items-center justify-between">
                        <span class="sikds-muted-text">{{ $institution['label'] }}</span>
                        <span class="text-base font-semibold sikds-ink">{{ $institution['value'] }}</span>
                    </div>
                @empty
                    <p class="sikds-muted-text">{{ __('Aucune institution active.') }}</p>
                @endforelse
            </div>
        </x-summary-card>

    </section>

@endsection
