{{-- Keep Markdown at column zero; indentation renders the email body as code. --}}
{{-- blade-formatter-disable --}}
@component('mail::message')
# Dein Pfarrplaner-Zugang ist eingerichtet

Hallo {{ $user->first_name }},

Dein Zugang zum **Pfarrplaner** wurde eingerichtet. Öffne die Anmeldeseite und fordere dort einen Anmeldelink für diese E-Mail-Adresse an:

**E-Mail-Adresse**: `{{ $user->email }}`

@component('mail::button', ['url' => config('app.url')])
Zur Anmeldung
@endcomponent

@endcomponent
{{-- blade-formatter-enable --}}
