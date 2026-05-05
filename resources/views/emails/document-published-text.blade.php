SIKDS — Nouveau document publié
Ministère de l'Enseignement Supérieur et de la Recherche Scientifique

Bonjour {{ $recipient->full_name ?: $recipient->email }},

Un nouveau document vient d'être publié et est disponible dans SIKDS.

Référence    : {{ $document->reference_number }}
Titre        : {{ $document->title }}
Date d'émission : {{ optional($document->issue_date)->format('Y-m-d') }}
@if (!empty($document->description))

Description :
{{ $document->description }}
@endif
@if (!empty($documentTags))

Tags :
@foreach ($documentTags as $tag)
- @if (!empty($tag['category'])){{ $tag['category'] }}: @endif{{ $tag['name'] }}
@endforeach
@endif

Voir le document : {{ url('/documents/'.$document->id) }}

---
Email envoyé automatiquement par SIKDS.
Support : {{ $supportContact }}
