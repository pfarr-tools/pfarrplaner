@component('mail::message')
Abwesenheitsantrag: Bitte um Genehmigung
========================================

{{ $absence->user->name }} bittet um Genehmigung eines Abwesenheitsantrags:

@component('mail::panel')
**{{ $absence->reason }}**

{{ $absence->from->isoFormat('dddd, DD.MM.YYYY') }} bis {{ $absence->to->isoFormat('dddd, DD.MM.YYYY') }}
@endcomponent

Der Antrag wurde am {{ \Carbon\Carbon::now()->isoFormat('dddd, DD.MM.YYYY, \u\m HH:mm \U\h\r') }} von
{{ $absence->checkedBy->name }} überprüft und zur Genehmigung weitergeleitet.

@component('mail::button', ['url' => route('absence.edit', $absence->id)])
    Antrag im Pfarrplaner öffnen
@endcomponent

@if($absence->admin_notes)
Der Überprüfung wurde folgende Notiz beigefügt:

@component('mail::panel')
    {!! $absence->admin_notes !!}
@endcomponent

@endif

@endcomponent
