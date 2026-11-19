{{-- Markdown mail content must stay at column zero; indentation creates code blocks. --}}
{{-- blade-formatter-disable --}}
@component('mail::message')
# Eine neue Nachricht wurde über das Kontaktformular gesandt.

* Absender: {{ $name }} ({{ $email }})

@component('mail::panel')
{!! $message !!}
@endcomponent

{{ config('app.name') }}
@endcomponent
{{-- blade-formatter-enable --}}
