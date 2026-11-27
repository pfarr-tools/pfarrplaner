<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8" />
    <style>
        body {
            color: #111;
            font-family: Arial, sans-serif;
            font-size: 11pt;
        }

        .header {
            border: 2px solid #111;
            padding: 12px;
        }

        .header h1 {
            display: inline-block;
            font-size: 20pt;
            margin: 0 0 18px;
        }

        .date {
            float: right;
        }

        .detail-line {
            align-items: end;
            column-gap: 8px;
            display: grid;
            grid-template-columns: 170px minmax(0, 1fr);
            margin: 6px 0;
        }

        .field {
            border-bottom: 1px solid #111;
            display: block;
            min-width: 0;
            width: 100%;
        }

        .section {
            margin: 16px 0;
        }

        .amount {
            font-size: 16pt;
            font-weight: bold;
            text-align: center;
        }

        table {
            border-collapse: collapse;
            font-size: 10pt;
            margin-top: 8px;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #555;
            padding: 4px 6px;
        }

        th {
            background: #e5e5e5;
            text-align: left;
        }

        .total {
            font-weight: bold;
            text-align: right;
        }

        .amount-cell {
            text-align: right;
        }

        .correctness-block {
            margin-top: 0;
        }

        .correctness-block p {
            margin: 0;
        }

        .machine-note {
            display: block;
            font-size: 8pt;
            margin-top: 3px;
        }

        .signature-grid {
            display: flex;
            gap: 12px;
            margin-top: 10px;
        }

        .signature {
            flex: 1;
        }

        .signature img {
            border-bottom: 1px solid #111;
            display: block;
            height: 55px;
            object-fit: contain;
            object-position: left bottom;
            width: 100%;
        }
    </style>
</head>
<body>
    <div>{{ $service->city->official_name ?: 'Evangelische Kirchengemeinde ' . $service->city->name }}</div>
    <div class="header">
        <h1>OPFERURKUNDE</h1>
        <span class="date">Datum: {{ $service->date->setTimezone('Europe/Berlin')->format('d.m.Y') }}</span>
        <div class="detail-line">
            <span>Art des Gottesdienstes:</span>
            <span class="field">{{ $service->titleText }}</span>
        </div>
        <div class="detail-line">
            <span>Organist:in:</span>
            <span class="field">{{ $service->organists->pluck('name')->join(', ') ?: '—' }}</span>
        </div>
        <div class="detail-line">
            <span>Opferzweck:</span>
            <span class="field">{{ $service->offeringGoal() ?: 'Eigene Gemeinde' }}</span>
        </div>
    </div>
    <div class="section amount">Folgendes Opfer wurde eingelegt:
        {{ number_format((float) str_replace(',', '.', $service->offering_amount), 2, ',', '.') }} €</div>

    @if ($service->offering_denominations)
        <div class="section">
            <strong>Einzelaufstellung des Opfers:</strong>
            <table>
                <thead>
                    <tr>
                        <th>Stückelung</th>
                        <th>Anzahl</th>
                        <th>Betrag</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ([10000 => '100 €-Scheine', 5000 => '50 €-Scheine', 2000 => '20 €-Scheine', 1000 => '10 €-Scheine', 500 => '5 €-Scheine', 200 => '2 €-Münzen', 100 => '1 €-Münzen', 50 => '50 Ct.-Münzen', 20 => '20 Ct.-Münzen', 10 => '10 Ct.-Münzen', 5 => '5 Ct.-Münzen', 2 => '2 Ct.-Münzen', 1 => '1 Ct.-Münzen'] as $cents => $label)
                        @if (!empty($service->offering_denominations[(string) $cents]))
                            <tr>
                                <td>{{ $label }}</td>
                                <td>{{ $service->offering_denominations[(string) $cents] }}</td>
                                <td class="amount-cell">
                                    {{ number_format(($cents / 100) * $service->offering_denominations[(string) $cents], 2, ',', '.') }}
                                    €</td>
                            </tr>
                        @endif
                    @endforeach
                    <tr>
                        <td colspan="2" class="total">Summe:</td>
                        <td class="total">
                            {{ number_format((float) str_replace(',', '.', $service->offering_amount), 2, ',', '.') }} €
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif

    <div class="correctness-block">
        @if (isset($settlement) && $settlement->status === 'finalized')
            <p><strong>Wir bestätigen mit unseren Unterschriften die Richtigkeit der vorstehenden
                    Opferabrechnung.</strong></p>
            <div class="signature-grid">
                @foreach ($settlement->signatures as $signature)
                    <div class="signature">
                        <img src="data:image/png;base64,{{ base64_encode(\Illuminate\Support\Facades\Storage::get($signature['path'])) }}"
                            alt="Unterschrift {{ $signature['name'] }}" />
                        <div>{{ $signature['name'] }}</div>
                    </div>
                @endforeach
            </div>
            <p>{{ $service->city->name }},
                {{ $settlement->finalized_at?->setTimezone('Europe/Berlin')->format('d.m.Y H:i') }} Uhr</p>
            <small class="machine-note">Beleg-ID {{ $settlement->id }}, Revision {{ $settlement->revision }}.
                Manifest-Prüfsumme: {{ $settlement->manifest_hash ?: $settlement->snapshot_hash }}</small>
            <small class="machine-note">Pfarrplaner-Version:
                {{ \App\Services\PackageService::info()['buildVersion'] }}</small>
        @else
            <p><strong>Für die Richtigkeit!</strong></p>
        @endif
        @if (!isset($settlement) || $settlement->status !== 'finalized')
            <p>{{ $service->city->name }}, {{ $service->date->setTimezone('Europe/Berlin')->format('d.m.Y') }},
                {{ $service->offerings_counter1 ?: '—' }}, {{ $service->offerings_counter2 ?: '—' }}</p>
        @endif
        @if (!isset($settlement) || $settlement->status !== 'finalized')
            <small class="machine-note">Dieses Dokument wurde maschinell erstellt und ist ohne Unterschrift
                gültig.</small>
        @endif
    </div>
</body>
</html>
