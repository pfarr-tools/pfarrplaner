@component('mail::message')
    Eine neue Nachricht wurde über das Kontaktformular gesandt.
    ===========================================================

    * Absender: {{ $name }} ({{ $email }})

    @component('mail::panel')
        {!! $message !!}
    @endcomponent

    {{ config('app.name') }}
@endcomponent
