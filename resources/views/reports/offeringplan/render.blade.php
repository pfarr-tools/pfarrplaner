<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <title>Opferplan {{ $year }} für {{ $cities->pluck('name')->join(', ') }}</title>
    <style>
        body, * {
            font-family: sarabunlight;
        }

        .table {
            border-collapse: collapse;
        }

        .table td, table th {
            margin: 0;
            border: 0;
            padding: 3px;
            text-align: left;
        }
    </style>
</head>
<body>
<h1 style="margin-bottom: 0cm; padding-bottom: 0;">Opferplan {{ $year }}
    für {{ $cities->pluck('name')->join(', ') }}</h1>
<p style="font-size: 0.6em; color: gray; margin-top: 0;">Stand: {{strftime('%A, %d. %B %Y, %H:%M Uhr')}}</p>

<htmlpagefooter name="myFooter2">
    <div style="width: 100%; font-size: 8pt; text-align: right;">Seite {PAGENO}/{nbpg}</div>
</htmlpagefooter>
<sethtmlpagefooter name="myFooter2" value="on"/>

@if(count($occurences))

    <table class="table table-fluid table-striped" cellspacing="0" border="0">
        <thead>
        <tr>
            <th>Gottesdienst</th>
            <th>Opferzweck</th>
            <th>Typ</th>
            <th>Anmerkungen</th>
            @if($includeOfferingCounters)
                <th>Zähler 1</th>
                <th>Zähler 2</th>
            @endif
        </tr>
        </thead>

        <tbody>
            <?php $ct = 0 ?>
        @foreach ($occurences as $occurence)
                <?php $ct++; ?>
            <tr style="background-color: {{ ($highlightEmpty && empty(trim($occurence->service->offering_goal))) ? ($ct %2 == 1 ? 'orange' : 'yellow') : ($ct %2 == 1 ? 'lightgray' : 'white')}};">
                <td valign="top" style="max-width: 3.5cm; width: 3.5cm;">
                    <small><b>{{ $occurence->start->format('d.m.Y') }}, {{ $occurence->event->timeText(true) }}
                            <br/>{{ $occurence->event->locationText() }}</b>
                        @if ($occurence->event->descriptionText() != '')<br/>{{ $occurence->event->descriptionText }}
                    </small>@endif
                </td>
                <td valign="top">{{ trim($occurence->event->offering_goal) ?: ($emptyAsOwn ? 'Eigene Gemeinde' : '') }}</td>
                <td valign="top" style="width: 1.3cm;"><small>@if((trim($occurence->event->offering_goal) == '') && $emptyAsOwn)
                            eig.
                        @elseif($occurence->event->offering_type == 'PO')
                            Pflicht
                        @else
                            @if($occurence->event->offering_type == 'eO')
                                empf.
                            @else
                                eig.
                            @endif
                        @endif</small>
                </td>
                <td valign="top">{{ $occurence->event->offering_description }}</td>
                @if($includeOfferingCounters)
                    <td valign="top"><small>{{ $occurence->event->offerings_counter1 }}</small></td>
                    <td valign="top"><small>{{ $occurence->event->offerings_counter2 }}</small></td>
                @endif
            </tr>
        @endforeach
        </tbody>
    </table>
    <br/>

    <h3>Dauerhaft festgelegte Opferzwecke</h3>
    <table class="table table-fluid table-striped" cellspacing="0" border="0" width="100%">
        <thead>
        <tr>
            <th style="align: left"></th>
            @foreach($cities as $city)
                <th style="align: left">{{ count($cities) > 1 ? $city->name : '' }}</th>
            @endforeach
        </tr>
        </thead>
        <tbody>
        <tr>
            <th style="text-align: left">Alle Anlässe</th>
            @foreach ($cities as $city)
                <td>{{ $city->default_offering_goal ?? '---'}} @if($city->default_offering_description)
                        ({{ $city->default_offering_description }})@endif
                </td>
            @endforeach
        </tr>
        <tr>
            <th style="text-align: left">Beerdigung</th>
            @foreach ($cities as $city)
                <td>{{ $city->default_funeral_offering_goal  ?? '---'}} @if($city->default_funeral_offering_description)
                        ({{ $city->default_funeral_offering_description }})@endif
                </td>
            @endforeach
        <tr>
            <th style="align: left">Trauung</th>
            @foreach ($cities as $city)
                <td>{{ $city->default_wedding_offering_goal ?? '---' }} @if($city->default_wedding_offering_description)
                        ({{ $city->default_wedding_offering_description }})@endif
                </td>
            @endforeach
        </tr>
        </tbody>
    </table>
    <p style="margin-top: 1cm; font-size: .7em">
        Erklärungen: Pflicht - durch die Landeskirche festgelegtes Pflichtopfer; empf. - von der Landeskirche empfohlener Opferzweck; eig. - eigene Festlegung des Opferzwecks.
        @if($emptyAsOwn) Nicht ausdrücklich festgelegte Opferzwecke wurden automatisch als "Eigene Gemeinde" (eig.) angegeben.@endif
    </p>

@else
    <p>In dem angegebenen Zeitraum wurden keine Gottesdienste gefunden.</p>
@endif

</body>
</html>
