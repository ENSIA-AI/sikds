SIKDS — Un document vous a été partagé
Ministère de l'Enseignement Supérieur et de la Recherche Scientifique

Bonjour {{ $recipient->full_name ?: $recipient->email }},

{{ $sender->full_name ?: $sender->email }} vous a transféré un document dans SIKDS.

Référence       : {{ $document->reference_number }}
Titre           : {{ $document->title }}
Date d'émission : {{ optional($document->issue_date)->format('Y-m-d') }}
@if (!empty($document->description))

Description :
{{ $document->description }}
@endif

Voir le document : {{ url('/documents/'.$document->id) }}

---
Email envoyé automatiquement par SIKDS.
Support : {{ $supportContact }}
