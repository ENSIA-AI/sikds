{{ __('SIKDS — Nouveau document publié') }}
{{ __("Ministère de l'Enseignement Supérieur et de la Recherche Scientifique") }}

{{ __('Bonjour :name,', ['name' => $recipient->full_name ?: $recipient->email]) }}

{{ __("Un nouveau document vient d'être publié et est disponible dans SIKDS.") }}

{{ __('Référence') }}    : {{ $document->reference_number }}
{{ __('Titre') }}        : {{ $document->title }}
{{ __("Date d'émission") }} : {{ optional($document->issue_date)->format('Y-m-d') }}
@if (!empty($document->description))

{{ __('Description :') }}
{{ $document->description }}
@endif
@if (!empty($documentTags))

{{ __('Tags :') }}
@foreach ($documentTags as $tag)
- @if (!empty($tag['category'])){{ $tag['category'] }}: @endif{{ $tag['name'] }}
@endforeach
@endif

{{ __('Voir le document :') }} {{ url('/documents/'.$document->id) }}

---
{{ __('Email envoyé automatiquement par SIKDS.') }}
{{ __('Support :') }} {{ $supportContact }}
