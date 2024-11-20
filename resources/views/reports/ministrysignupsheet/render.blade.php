<html>
<head>
    <style>
        body, * {
            font-family: 'sarabunlight', sans-serif;
        }

        tr.even {
            background-color: lightgray;
        }

        h1 {
            margin-bottom: 3px;
            padding-bottom: 0;
        }

        th {
            text-align: left;
        }
    </style>
</head>
<body>
<h1>Dienstplan
    für {{ collect($ministries)->map(function($item) { return \App\Services\MinistryService::title($item ); })->join(', ') }}</h1>
<b>Von {{ $start->format('d.m.Y') }} bis {{ $end->format('d.m.Y') }}@if(count($cities)  > 1)
        für  {{ $cities->pluck('name')->join(', ') }}
    @endif</b>
<hr/>
<table>
    <thead>
    <tr>
        <th>
            Gottesdienst
        </th>
        @foreach($ministries as $ministry)
            <th>{{ \App\Services\MinistryService::title($ministry) }}</th>
        @endforeach
    </tr>
    </thead>
    <tbody>
    @foreach ($services as $service)
        <tr @if($loop->index % 2 == 0)class="even" @endif>
            <td valign="top" width="20%"><small>
                    <b>{{ $service->date->format('d.m.Y') }}, {{ $service->timeText() }}</b><br/>
                    @if(count($cities) > 1)
                        <b>{{ $service->city->name }}</b><br/>
                    @endif
                    {{ $service->locationText() }}<br/>
                    {{ $service->titleText(false) }}<br/>
                    config('labels.code_pastor') {{ $service->participantsText('P') }}
                    config('labels.code_organist'): {{ $service->participantsText('O') }}
                    config('labels.code_sacristan'): {{ $service->participantsText('M') }}
                </small>
            </td>
            @foreach($ministries as $ministry)
                @php
                    if ($ministry == config('labels.pastor')) $ministry = 'P';
                    if ($ministry == config('labels.organist')) $ministry = 'O';
                    if ($ministry == config('labels.sacristan')) $ministry = 'M';
                @endphp
                <td valign="top"><small>{{ $service->participantsText($ministry)}}</small></td>
            @endforeach
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
