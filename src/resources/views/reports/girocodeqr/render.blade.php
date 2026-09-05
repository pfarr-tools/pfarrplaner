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

        img.qr-code {
            height: auto;
        }

        p {
            margin: 0 0 4mm 0;
        }

        p:last-child {
            margin-bottom: 0;
        }

        p.offering-goal {
            line-height: 1.2;
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
            @php
                $offeringGoal = $service->offeringGoal() ?: 'Unsere Kirchengemeinde';
                $offeringPurpose = 'Spende: '.($service->offeringGoal() ?: 'Allgemeine Gemeindearbeit');
                $offeringGoalLength = mb_strlen($offeringGoal);
                $offeringGoalFontSize = match (true) {
                    $offeringGoalLength > 90 => '.82em',
                    $offeringGoalLength > 65 => '.9em',
                    default => '1em',
                };
                $qrCodeSize = match (true) {
                    $offeringGoalLength > 90 => '32mm',
                    $offeringGoalLength > 75 => '36mm',
                    $offeringGoalLength > 60 => '40mm',
                    $offeringGoalLength > 45 => '46mm',
                    default => '56mm',
                };
            @endphp
            <div class="container" @if($loop->last) style="page-break-after: always;" @endif>
                <p style="font-weight: bold">{{ $service->title ?: 'Gottesdienst' }}<br/>
                    <span style="font-size: .8em;">am {{ $service->date->format('d.m.Y') }} um {{ $service->timeText() }}</span></p>
                <p class="offering-goal" style="font-size: {{ $offeringGoalFontSize }};">
                    Alle Spenden zum heutigen Gottesdienst sind für:<br />
                    <b>{{ $offeringGoal }}</b>
                </p>
                <p style="font-size: .8em;">Scanne den folgenden Code mit deiner Online-Banking-App, um per Überweisung zu spenden:</p>
                <p>
                    <img src="{{ route('qrcode', \App\Services\GiroCodeService::codeValue(
                        $service->city->official_name ?: 'Evangelische Kirchengemeinde '.$service->city->name,
                        $service->city->iban,
                        null,
                        $offeringPurpose,
                        $service->city->bic ?? null,
)                   ) }}" class="qr-code" style="width: {{ $qrCodeSize }};" />
                    <br/>
                </p>

            </div>
        @endfor
    @endforeach
@endforeach
</body>
</html>
