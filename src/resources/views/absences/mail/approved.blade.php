@component('mail::message')
Antrag genehmigt
================

Hallo {{ $absence->user->first_name }},

Der folgende Abwesenheitsantrag wurde am {{ \Carbon\Carbon::now()->isoFormat('dddd, DD.MM.YYYY, \u\m HH:mm \U\h\r') }} von
{{ $absence->approvedBy->name }} genehmigt.

@component('mail::panel')
**{{ $absence->reason }}**

{{ $absence->from->isoFormat('dddd, DD.MM.YYYY') }} bis {{ $absence->to->isoFormat('dddd, DD.MM.YYYY') }}
@endcomponent

@if($absence->approver_notes)
Der Genehmigung wurde folgende Notiz beigefügt:

@component('mail::panel')
{!! $absence->approver_notes !!}
@endcomponent

@endif

@endcomponent
