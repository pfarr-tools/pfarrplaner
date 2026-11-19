{{-- Markdown mail content must stay at column zero; indentation creates code blocks. --}}
{{-- blade-formatter-disable --}}
@component('mail::message')
# Abwesenheitsantrag: Bitte überprüfen

{{ $absence->user->fullName() }} bittet um Überprüfung eines Abwesenheitsantrags:

@component('mail::panel')
**{{ $absence->reason }}**

{{ $absence->from->isoFormat('dddd, DD.MM.YYYY') }} bis {{ $absence->to->isoFormat('dddd, DD.MM.YYYY') }}
@endcomponent

@component('mail::button', ['url' => route('absence.edit', $absence->id)])
Antrag prüfen
@endcomponent


@endcomponent
{{-- blade-formatter-enable --}}
