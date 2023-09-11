<html>
<head>
    <style>
        body, * {
            font-family: 'helveticacondensed', sans-serif;
            font-size: 1.2em;
        }
        td {
            padding: 5px;
        }
        tr.even {
            background-color: lightgray;
        }
    </style>
</head>
<body>
<h1>Zu vertretende Dienste für <br />{{ $user->fullName(true) }}</h1>
<b>Von {{ $start }} bis {{ $end }}</b>
<hr/>
<table style="width: 100%;">
    <thead></thead>
    <tbody>
    @foreach ($services as $service)
        <tr @if($loop->index % 2 == 0)class="even" @endif>
            <td valign="top" style="font-size: 1.5em;"><input type="checkbox" /></td>
            <td valign="top">{{ $service->date->format('d.m.Y') }}</td>
            <td valign="top">{{ $service->timeText() }}</td>
            <td valign="top">{{ $service->locationText() }}</td>
            <td valign="top">{{ $service->titleText(false) }}</td>
            <td valign="top"><a href="{{ route('service.edit', $service) }}">Zum Gottesdienst</a></td>
        </tr>
    @endforeach
    </tbody>
</table>

@if(count($baptisms))
    <h2>Taufen ({{ count($baptisms) }})</h2>
    <table style="width: 100%;">
        <thead></thead>
        <tbody>
        @foreach ($baptisms as $baptism)
            <tr @if($loop->index % 2 == 0)class="even" @endif>
                <td valign="top" style="font-size: 1.5em;"><input type="checkbox" /></td>
                <td valign="top">{{ $baptism->service->date->format('d.m.Y') }}, {{ $baptism->service->timeText() }}, {{ $baptism->service->locationText() }}<br />
                    <b>{{ $baptism->candidate_name }}</b><br />
                    <div style="padding-top: 1em; font-size: .8em;">
                        @if($baptism->appointment && ($baptism->appointment <= \Carbon\Carbon::now()))
                            <div>Das Taufgespräch hat bereits am {{ $baptism->appointment->format('d.m.Y') }} stattgefunden.</div>
                            @if(trim($baptism->candidate_phone.$baptism->candidate_email))<div>Kontakt zur Familie: {{join(', ', [$baptism->candidate_phone, $baptism->candidate_email ])}}</div> @endif
                        @elseif($baptism->appointment && ($baptism->appointment > \Carbon\Carbon::now()))
                            <div>Das Taufgespräch ist für den {{ $baptism->appointment->format('d.m.Y') }} um {{ $baptism->appointment->setTimeZone('Europe/Berlin')->format('H:i') }} Uhr vereinbart.</div>
                            @if(trim($baptism->candidate_phone.$baptism->candidate_email))<div>Kontakt zur Familie: {{join(', ', [$baptism->candidate_phone, $baptism->candidate_email ])}}</div> @endif
                        @else
                            Es hat noch kein Taufgespräch stattgefunden.
                        @endif
                    </div>
                </td>
                <td valign="top">
                    <a href="{{ route('service.edit', $baptism->service) }}">Zum Gottesdienst</a><br />
                    <a href="{{ route('baptisms.edit', $baptism) }}">Zur Taufe</a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@else
    <div style="padding: 2em 0;">
        Im zu vertretenden Zeitraum sind aktuell keine Taufen geplant.
    </div>
@endif



@if(count($weddings))
    <h2>Trauungen ({{ count($weddings) }})</h2>
    <table style="width: 100%;">
        <thead></thead>
        <tbody>
        @foreach ($weddings as $wedding)
            <tr @if($loop->index % 2 == 0)class="even" @endif>
                <td valign="top" style="font-size: 1.5em;"><input type="checkbox" /></td>
                <td valign="top">{{ $wedding->service->date->format('d.m.Y') }}, {{ $wedding->service->timeText() }}, {{ $wedding->service->locationText() }}<br />
                    <b>{{ $wedding->spouse1_name }} &amp; {{ $wedding->spouse2_name }}</b><br />
                    <div style="padding-top: 1em; font-size: .8em">
                        @if($wedding->appointment && ($wedding->appointment <= \Carbon\Carbon::now()))
                            <div>Das Traugespräch hat bereits am {{ $wedding->appointment->format('d.m.Y') }} stattgefunden.</div>
                            @if(trim($wedding->spouse1_phone.$wedding->spouse1_email))<div>Kontakt zu {{ $wedding->spouse1_name }}: {{join(', ', [$wedding->spouse1_phone, $wedding->spouse1_email ])}}</div> @endif
                            @if(trim($wedding->spouse2_phone.$wedding->spouse2_email))<div>Kontakt zu {{ $wedding->spouse2_name }}: {{join(', ', [$wedding->spouse2_phone, $wedding->spouse2_email ])}}</div> @endif
                        @elseif($wedding->appointment && ($wedding->appointment > \Carbon\Carbon::now()))
                            <div>Das Traugespräch ist für den {{ $wedding->appointment->format('d.m.Y') }} um {{ $wedding->appointment->setTimeZone('Europe/Berlin')->format('H:i') }} Uhr vereinbart.</div>
                            @if(trim($wedding->spouse1_phone.$wedding->spouse1_email))<div>Kontakt zu {{ $wedding->spouse1_name }}: {{join(', ', [$wedding->spouse1_phone, $wedding->spouse1_email ])}}</div> @endif
                            @if(trim($wedding->spouse2_phone.$wedding->spouse2_email))<div>Kontakt zu {{ $wedding->spouse2_name }}: {{join(', ', [$wedding->spouse2_phone, $wedding->spouse2_email ])}}</div> @endif
                        @else
                            Es hat noch kein Traugespräch stattgefunden.
                        @endif
                    </div>
                </td>
                <td valign="top">
                    <a href="{{ route('service.edit', $wedding->service) }}">Zum Gottesdienst</a><br />
                    <a href="{{ route('weddings.edit', $wedding) }}">Zur Trauung</a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@else
    <div style="padding: 2em 0;">
        Im zu vertretenden Zeitraum sind aktuell keine Trauungen geplant.
    </div>
@endif

@if(count($funerals))
    <h2>Beerdigungen ({{ count($funerals) }})</h2>
    <table style="width: 100%;">
        <thead></thead>
        <tbody>
        @foreach ($funerals as $funeral)
            <tr @if($loop->index % 2 == 0)class="even" @endif>
                <td valign="top" style="font-size: 1.5em;"><input type="checkbox" /></td>
                <td valign="top">{{ $funeral->service->date->format('d.m.Y') }}, {{ $funeral->service->timeText() }}, {{ $funeral->service->locationText() }}<br />
                    <b>{{ $funeral->buried_name }}</b><br />
                    <div style="padding-top: 1em; font-size: .8em">
                        @if($funeral->appointment && ($funeral->appointment <= \Carbon\Carbon::now()))
                            <div>Das Trauergespräch hat bereits am {{ $funeral->appointment->format('d.m.Y') }} stattgefunden.</div>
                            @if(trim($funeral->relative_name.$funeral->relative_contact_data))<div>Kontakt zur Familie: {{ $funeral->relative_name }}, {!! nl2br($funeral->relative_contact_data) !!}</div> @endif
                        @elseif($funeral->appointment && ($funeral->appointment > \Carbon\Carbon::now()))
                            <div>Das Trauergespräch ist für den {{ $funeral->appointment->format('d.m.Y') }} um {{ $funeral->appointment->setTimeZone('Europe/Berlin')->format('H:i') }} Uhr vereinbart.</div>
                            @if(trim($funeral->relative_name.$funeral->relative_contact_data))<div>Kontakt zur Familie: {{ $funeral->relative_name }}, {!! nl2br($funeral->relative_contact_data) !!}</div> @endif
                        @else
                            Es hat noch kein Trauergespräch stattgefunden.
                        @endif
                    </div>
                </td>
                <td valign="top">
                    <a href="{{ route('service.edit', $funeral->service) }}">Zum Gottesdienst</a><br />
                    <a href="{{ route('funerals.edit', $funeral) }}">Zur Beerdigung</a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@else
    <div style="padding: 2em 0;">
        Im zu vertretenden Zeitraum sind aktuell keine Beerdigungen geplant.
    </div>
@endif

<div style="padding: 2em 0; font-size: .8em; font-style:italic;"><u>Hinweis</u><br />Alle oben aufgeführten Dienste können
    <a href="{{ route('report.step', ['report' => 'replaceableServices', 'step' => 'wizard', 'person' => $user->id, 'start' => $start, 'end' => $end]) }}">
    im Pfarrplaner auf einer übersichtlichen Seite gemeinsam bearbeitet werden</a>.
</div>

</body>
</html>
