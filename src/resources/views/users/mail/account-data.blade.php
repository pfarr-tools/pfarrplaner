{{-- Keep Markdown at column zero; indentation renders the email body as code. --}}
{{-- blade-formatter-disable --}}
@component('mail::message')
# Deine Zugangsdaten für den Pfarrplaner

Hallo {{ $user->first_name }},

Mit den folgenden Anmeldedaten kannst du dich am **Pfarrplaner** anmelden:

**Anmeldeseite**: {{ config('app.url') }}

**Benutzername**: `{{ $user->email }}`

**Passwort**: `{{ $password }}`

@component('mail::button', ['url' => config('app.url')])
Jetzt anmelden
@endcomponent

*Bitte beachte*: Das Passwort muss bei der nächsten Anmeldung geändert werden.
@endcomponent
{{-- blade-formatter-enable --}}
