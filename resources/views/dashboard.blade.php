@extends('layouts.app')
@php($activeNav = 'dashboard')
@section('page_title', 'Tableau de Bord')
@section('page_subtitle', "Aperçu de l'activité du système SIKDS")
@section('content')

    <section class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
        @foreach ($kpis as $kpi)
            <x-stat-card :icon="$kpi['icon']" :value="$kpi['value']" :label="$kpi['label']" :trend="$kpi['trend']" />
        @endforeach
    </section>

    <section class="sikds-grid-panels xl:gap-6">

        <x-dashboard-panel
            title="Activité Récente"
            subtitle="Actions des utilisateurs en temps réel"
        >
            <div class="min-h-0 flex-1 divide-y divide-black/10 overflow-y-auto">
                @foreach ($activities as $activity)
                    <x-activity-item
                        :icon="$activity['icon']"
                        :user="$activity['user']"
                        :action="$activity['action']"
                        :document="$activity['document']"
                        :time="$activity['time']"
                    />
                @endforeach
            </div>
            <x-slot:footer>Voir toute l'activité →</x-slot:footer>
        </x-dashboard-panel>

        <x-dashboard-panel
            title="Alertes Système"
            subtitle="Notifications importantes"
        >
            <div class="min-h-0 flex-1 space-y-3 overflow-y-hidden px-4 py-4">
                @foreach ($alerts as $alert)
                    <x-alert-item :type="$alert['type']" :message="$alert['message']" :timestamp="$alert['timestamp']" />
                @endforeach
            </div>
            <x-slot:footer>Voir toutes les alertes →</x-slot:footer>
        </x-dashboard-panel>

    </section>

    <section class="mt-5 grid gap-5 md:grid-cols-2 xl:grid-cols-3">

        <x-summary-card title="Documents par Statut">
            <div class="mt-4 space-y-3 text-sm">
                @foreach ($statusStats as $stat)
                    <div class="flex items-center justify-between">
                        <span class="sikds-muted-text">{{ $stat['label'] }}</span>
                        <span class="text-base font-semibold sikds-ink">{{ $stat['value'] }}</span>
                    </div>
                @endforeach
            </div>
        </x-summary-card>

        <x-summary-card title="Tags Populaires">
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($popularTags as $tag)
                    <span class="sikds-tag {{ $tag['class'] }}">{{ $tag['label'] }}</span>
                @endforeach
            </div>
        </x-summary-card>

        <x-summary-card title="Institutions Actives">
            <div class="mt-4 space-y-3 text-sm">
                @foreach ($activeInstitutions as $institution)
                    <div class="flex items-center justify-between">
                        <span class="sikds-muted-text">{{ $institution['label'] }}</span>
                        <span class="text-base font-semibold sikds-ink">{{ $institution['value'] }}</span>
                    </div>
                @endforeach
            </div>
        </x-summary-card>

    </section>

@endsection
