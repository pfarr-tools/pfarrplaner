<html>
<head>
    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }

        html, body {
            margin: 0;
            padding: 0;
        }

        body, * {
            font-family: 'sarabunlight', sans-serif;
            text-align: center;
            box-sizing: border-box;
        }

        .page {
            width: 297mm;
            height: 210mm;
            padding: 8mm;
            page-break-after: always;
        }

        .page:last-child {
            page-break-after: auto;
        }

        .grid {
            width: 100%;
            height: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .grid-cell {
            width: 25%;
            height: 50%;
            padding: 0;
            vertical-align: top;
        }

        .card {
            width: 100%;
            height: 100%;
            padding: 6mm;
            overflow: hidden;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .title {
            margin: 0 0 4mm 0;
            font-size: 15px;
            font-weight: bold;
            line-height: 1.25;
        }

        .qr {
            margin: 0 0 3mm 0;
        }

        .qr img {
            width: 70%;
            max-width: 44mm;
            max-height: 44mm;
            object-fit: contain;
        }

        .meta {
            margin: 0;
            font-size: 12px;
            line-height: 1.25;
        }

        .validity {
            display: block;
            margin-top: 2mm;
            font-size: 9px;
            font-style: italic;
        }

        .print-info {
            margin: 3mm 0 0 0;
            font-size: 7px;
            line-height: 1.2;
        }
    </style>
</head>
<body>
@php
    $cards = [];

    foreach ($services as $location => $localServices) {
        foreach ($localServices as $service) {
            for ($i = 0; $i < $copies; $i++) {
                $cards[] = $service;
            }
        }
    }

    $pages = array_chunk($cards, 8);
@endphp

@foreach ($pages as $page)
    <div class="page">
        <table class="grid">
            @for ($row = 0; $row < 2; $row++)
                <tr>
                    @for ($column = 0; $column < 4; $column++)
                        @php
                            $service = $page[($row * 4) + $column] ?? null;
                        @endphp
                        <td class="grid-cell">
                            @if ($service)
                                <div class="card">
                                    <p class="title">
                                        {{ $service->title ?: 'Gottesdienst' }}<br />
                                        am {{ $service->date->format('d.m.Y') }} um {{ $service->timeText() }}<br />
                                        {{ $service->locationText() }}
                                    </p>
                                    <p class="qr">
                                        <img src="{{ route('qrcode', $service->konfiapp_event_qr) }}" alt="QR-Code" />
                                    </p>
                                    <p class="meta">
                                        @foreach ($types as $type)
                                            @if ($type->id == $service->konfiapp_event_type)
                                                {{ $type->punktzahl }} {{ $type->punktzahl == 1 ? 'Punkt' : 'Punkte' }} in der Kategorie
                                                "{{ $type->name }}"
                                            @endif
                                        @endforeach
                                        <span class="validity">gültig am {{ $service->date->format('d.m.Y') }} ab {{ $service->timeText() }} für 3 Stunden.</span>
                                    </p>

                                    <p class="print-info">
                                        {{ $service->konfiapp_event_qr }} | Gedruckt
                                        am {{ \Carbon\Carbon::now()->setTimezone('Europe/Berlin')->isoFormat('DD.MM.YYYY \u\m HH:mm \U\h\r') }}
                                        von {{ \Illuminate\Support\Facades\Auth::user()->name }}.
                                    </p>
                                </div>
                            @endif
                        </td>
                    @endfor
                </tr>
            @endfor
        </table>
    </div>
@endforeach
</body>
</html>
