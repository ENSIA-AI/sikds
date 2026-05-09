{{ __('SIKDS — Document mis à jour') }}
{{ __("Ministère de l'Enseignement Supérieur et de la Recherche Scientifique") }}

{{ __('Bonjour :name,', ['name' => $recipient->full_name ?: $recipient->email]) }}

{{ __("Une nouvelle version d'un document existant est disponible.") }}

{{ __('Référence') }} : {{ $document->reference_number }}
{{ __('Titre') }}     : {{ $document->title }}
{{ __('Version') }}   : v{{ $previousVersion }} -> v{{ $newVersion }}
@if (!empty($changeSummary))

{{ __('Résumé des modifications :') }}
{{ $changeSummary }}
@endif
@if (!empty($documentTags))

{{ __('Tags :') }}
@foreach ($documentTags as $tag)
- @if (!empty($tag['category'])){{ $tag['category'] }}: @endif{{ $tag['name'] }}
@endforeach
@endif

{{ __('Voir la nouvelle version :') }} {{ url('/documents/'.$document->id) }}

---
{{ __('Email envoyé automatiquement par SIKDS.') }}
{{ __('Support :') }} {{ $supportContact }}
