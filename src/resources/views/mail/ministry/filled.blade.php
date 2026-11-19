{{-- Markdown mail content must stay at column zero; indentation creates code blocks. --}}
{{-- blade-formatter-disable --}}
@component('mail::message')
# Zusage


Hallo {{ $sender->fullName() }},

{{ $user->fullName() }} hat zugesagt, bei folgenden Gottesdiensten den Dienst "{{ $ministry }}" zu übernehmen:

@foreach ($services as $service)
- {{ $service->date->isoFormat('dddd, DD. MMMM YYYY') }}, {{ $service->timeText() }}, {{ $service->locationText() }}
@endforeach
@endcomponent
{{-- blade-formatter-enable --}}
