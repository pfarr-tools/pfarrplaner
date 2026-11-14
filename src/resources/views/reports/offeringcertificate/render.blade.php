<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8" />
    <style>
        body {
            color: #111;
            font-family: Arial, sans-serif;
            font-size: 13pt;
        }

        .header {
            border: 2px solid #111;
            padding: 16px;
        }

        .header h1 {
            display: inline-block;
            font-size: 22pt;
            margin: 0 0 28px;
        }

        .date {
            float: right;
        }

        .header p {
            margin: 10px 0;
        }

        .field {
            border-bottom: 1px solid #111;
            display: inline-block;
            min-width: 55%;
        }

        .section {
            margin: 26px 0;
        }

        .amount {
            font-size: 18pt;
            font-weight: bold;
            text-align: center;
        }

        table {
            border-collapse: collapse;
            margin-top: 12px;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #555;
            padding: 6px 8px;
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
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <div>{{ $service->city->official_name ?: 'Evangelische Kirchengemeinde ' . $service->city->name }}</div>
    <div class="header">
        <h1>OPFERURKUNDE</h1>
        <span class="date">Datum: {{ $service->date->setTimezone('Europe/Berlin')->format('d.m.Y') }}</span>
        <p>Art des Gottesdienstes: <span class="field">{{ $service->titleText }}</span></p>
        <p>Organist:in: <span class="field">{{ $service->organists->pluck('name')->join(', ') ?: '—' }}</span></p>
    </div>

    <div class="section">Opferzweck <span class="field">{{ $service->offeringGoal() ?: 'Eigene Gemeinde' }}</span></div>
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
        <p><strong>Für die Richtigkeit!</strong></p>
        <p>{{ $service->city->name }}, {{ $service->date->setTimezone('Europe/Berlin')->format('d.m.Y') }},
            {{ $service->offerings_counter1 ?: '—' }}, {{ $service->offerings_counter2 ?: '—' }}</p>
        <small class="machine-note">Dieses Dokument wurde maschinell erstellt und ist ohne Unterschrift gültig.</small>
    </div>
</body>
</html>
