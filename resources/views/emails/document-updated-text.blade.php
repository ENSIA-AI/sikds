SIKDS — Document mis à jour
Ministère de l'Enseignement Supérieur et de la Recherche Scientifique

Bonjour {{ $recipient->full_name ?: $recipient->email }},

Une nouvelle version d'un document existant est disponible.

Référence : {{ $document->reference_number }}
Titre     : {{ $document->title }}
Version   : v{{ $previousVersion }} -> v{{ $newVersion }}
@if (!empty($changeSummary))

Résumé des modifications :
{{ $changeSummary }}
@endif
@if (!empty($documentTags))

Tags :
@foreach ($documentTags as $tag)
- @if (!empty($tag['category'])){{ $tag['category'] }}: @endif{{ $tag['name'] }}
@endforeach
@endif

Voir la nouvelle version : {{ url('/documents/'.$document->id) }}

---
Email envoyé automatiquement par SIKDS.
Support : {{ $supportContact }}
