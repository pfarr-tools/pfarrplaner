<html>
<head>
    <style>
        @page {
            margin: 0;
        }

        body, * {
            font-family: 'sarabunlight', sans-serif;
            font-size: 1.1em;
            text-align: center;
        }

        div.container {
            float: left;
            border: solid 1px white;
            width: 90mm !important;
            max-width: 90mm !important;
            height: 133.5mm;
            max-height: 133.5mm;
            overflow: hidden;
            padding: 6mm;
        }

        tr.even {
            background-color: lightgray;
        }
    </style>
</head>
<body>
@foreach ($services as $location => $localServices)
    @foreach($localServices as $service)
        @for($i=0; $i<$copies; $i++)
            <div class="container" @if($loop->last) style="page-break-after: always;" @endif>
                <p style="font-weight: bold">{{ $service->title ?: 'Gottesdienst' }}<br/>
                    <span style="font-size: .8em;">am {{ $service->date->format('d.m.Y') }} um {{ $service->timeText() }}</span></p>
                <p>
                    Alle Spenden zum heutigen Gottesdienst sind für:<br />
                    <b>{{ $service->offeringGoal() ?: 'Unsere Kirchengemeinde'}}</b>
                </p>
                <p style="font-size: .8em;">Scanne den folgenden Code mit deiner Online-Banking-App, um per Überweisung zu spenden:</p>
                <p>
                    <img src="{{ route('qrcode', \App\Services\GiroCodeService::codeValue(
                        $service->city->official_name ?: 'Evangelische Kirchengemeinde '.$service->city->name,
                        $service->city->iban,
                        null,
                        'Spende: '.($service->offeringGoal() ?: 'Allgemeine Gemeindearbeit'),
                        $service->city->bic ?? null,
)                   ) }}" />
                    <br/>
                </p>

            </div>
        @endfor
    @endforeach
@endforeach
</body>
</html>
