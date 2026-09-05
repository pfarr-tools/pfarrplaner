@component('mail::message')
Antrag abgelehnt
================

Hallo {{ $absence->user->first_name }},

Der folgende Abwesenheitsantrag wurde am {{ \Carbon\Carbon::now()->isoFormat('dddd, DD.MM.YYYY, \u\m HH:mm \U\h\r') }} von
{{ $author->name }} abgelehnt.

@component('mail::panel')
**{{ $absence->reason }}**

{{ $absence->from->isoFormat('dddd, DD.MM.YYYY') }} bis {{ $absence->to->isoFormat('dddd, DD.MM.YYYY') }}
@endcomponent

@if($absence->admin_notes)
Der Überprüfung wurde folgende Notiz beigefügt:

@component('mail::panel')
{!! $absence->admin_notes !!}
@endcomponent

@endif
@if($absence->approver_notes)
Der Bitte um Genehmigung wurde folgende Notiz hinzugefügt:

@component('mail::panel')
{!! $absence->approver_notes !!}
@endcomponent

@endif

@endcomponent
