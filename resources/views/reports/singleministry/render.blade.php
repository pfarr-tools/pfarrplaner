<html>
<head>
    <style>
        body, * {
            font-family: 'helveticacondensed', sans-serif;
        }
        tr.even {
            background-color: lightgray;
        }
        th {
            text-align: left;
        }
    </style>
</head>
<body>
@if($includeHeader)
<h1>Dienstplan für {{ join(', ', $ministries) }}</h1>
<b>Von {{ $start }} bis {{ $end }}</b>
<p><small>Stand: {{ \Carbon\Carbon::now()->setTimezone('Europe/Berlin')->format('d.m.Y H:i') }} Uhr. Immer aktuell auf <a href="https://www.pfarrplaner.de">www.pfarrplaner.de</a></small></p>
<hr/>
@endif
<table style="width: 100%">
    <thead>
    <tr>
        <th>Datum</th>
        <th>Uhrzeit</th>
        <th @if(count($cities)>1) colspan="2" @endif>Ort</th>
        @foreach($ministries as $ministry)<th>{{ $ministry }}</th>@endforeach
    </tr>
    </thead>
    <tbody>
    @foreach ($services as $service)
        <tr @if($loop->index % 2 == 0)class="even" @endif>
            <td>{{ $service->date->format('d.m.Y') }}</td>
            <td>{{ $service->timeText() }}</td>
            @if(count($cities)>1)<td>{{ $service->city->name }}</td>@endif
            <td>{{ $service->locationText() }}</td>
            @foreach($ministries as $ministryKey => $ministry)<td valign="top"> {{ $service->participantsText($ministryKey) }} </td>@endforeach

        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
