{{ __('SIKDS — Un document vous a été partagé') }}
{{ __("Ministère de l'Enseignement Supérieur et de la Recherche Scientifique") }}

{{ __('Bonjour :name,', ['name' => $recipient->full_name ?: $recipient->email]) }}

{{ __(':sender vous a transféré un document dans SIKDS.', ['sender' => $sender->full_name ?: $sender->email]) }}

{{ __('Référence') }}       : {{ $document->reference_number }}
{{ __('Titre') }}           : {{ $document->title }}
{{ __("Date d'émission") }} : {{ optional($document->issue_date)->format('Y-m-d') }}
@if (!empty($document->description))

{{ __('Description :') }}
{{ $document->description }}
@endif

{{ __('Voir le document :') }} {{ url('/documents/'.$document->id) }}

---
{{ __('Email envoyé automatiquement par SIKDS.') }}
{{ __('Support :') }} {{ $supportContact }}
