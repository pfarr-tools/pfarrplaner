@component('mail::message')
Abwesenheitsantrag: Bitte überprüfen
====================================

{{ $absence->user->name }} bittet um Überprüfung eines Abwesenheitsantrags:

@component('mail::panel')
**{{ $absence->reason }}**

{{ $absence->from->isoFormat('dddd, DD.MM.YYYY') }} bis {{ $absence->to->isoFormat('dddd, DD.MM.YYYY') }}
@endcomponent

@component('mail::button', ['url' => route('absence.edit', $absence->id)])
    Antrag prüfen
@endcomponent


@endcomponent
