<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>{{ $isFinalReport ? 'Abschließende Wertermittlung' : 'Immobilien-Ersteinschätzung' }} - 2 COME HOME</title>
    <style>
        @page { margin: 22mm 18mm 24mm 18mm; }

        * { box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #666666;
            font-size: 10.5pt;
            line-height: 1.35;
            margin: 0;
        }

        .navy { color: #0B2D48; }
        .muted { color: #777777; }

        .header {
            text-align: center;
            margin: 0 0 24px;
        }

        .logo {
            width: 116px;
            height: auto;
        }

        h1 {
            color: #0B2D48;
            font-size: 23pt;
            font-weight: 400;
            margin: 18px 0 4px;
            letter-spacing: .1px;
        }

        h2 {
            color: #0B2D48;
            font-size: 16pt;
            font-weight: 400;
            margin: 24px 0 10px;
        }

        .intro {
            text-align: center;
            color: #777777;
            margin: 0 0 20px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin: 0;
        }

        .data-table tr {
            border-bottom: .7px solid #0B2D48;
        }

        .data-table tr:last-child {
            border-bottom: 0;
        }

        .data-table td {
            padding: 5px 0 4px;
            vertical-align: top;
        }

        .data-table .label {
            width: 46%;
            color: #0B2D48;
            font-weight: 700;
            padding-right: 14px;
        }

        .data-table .value {
            width: 54%;
            color: #666666;
        }

        .valuation {
            margin-top: 22px;
        }

        .valuation-value {
            color: #0B2D48;
            font-size: 22pt;
            font-weight: 700;
            margin: 4px 0 3px;
        }

        .valuation-range {
            color: #666666;
            font-size: 12pt;
            margin: 0 0 8px;
        }

        .valuation-meta {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 8px;
        }

        .valuation-meta td {
            padding: 5px 0 4px;
            border-bottom: .7px solid #0B2D48;
        }

        .valuation-meta .label {
            width: 46%;
            color: #0B2D48;
            font-weight: 700;
        }

        .page-break {
            page-break-before: always;
        }

        .map-wrap {
            width: 100%;
            margin-top: 12px;
        }

        .map {
            width: 100%;
            height: auto;
            display: block;
        }

        .address {
            color: #666666;
            margin: 0 0 10px;
        }

        .notice {
            margin-top: 22px;
            font-size: 9.5pt;
            color: #777777;
            line-height: 1.45;
        }

        .accent-line {
            width: 46px;
            border-top: 2px solid #B66E55;
            margin: 12px auto 0;
        }

        .footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: -15mm;
            text-align: center;
            color: #666666;
            font-size: 8.8pt;
            line-height: 1.45;
        }

        .footer strong {
            color: #0B2D48;
            font-weight: 400;
        }
    </style>
</head>
<body>
    <div class="footer">
        <strong>2 COME HOME Immobilien</strong> | Markt 14-16 | 40721 Hilden<br>
        Telefon: +49 176 64845045 | Mobil: +49 176 64845045<br>
        c.glasmacher@2comehome.de
    </div>

    <div class="header">
        <img src="{{ public_path('images/logo-2comehome.png') }}" alt="2 COME HOME Immobilien" class="logo">
        <h1>{{ $isFinalReport ? 'Abschließende Wertermittlung' : 'Immobilien-Ersteinschätzung' }}</h1>
        <div class="accent-line"></div>
    </div>

    <p class="intro">
        Erstellt für {{ trim($lead->first_name . ' ' . $lead->last_name) ?: 'Interessent' }}
    </p>

    <h2>Objektdaten</h2>
    <table class="data-table">
        <tr>
            <td class="label">Straße</td>
            <td class="value">{{ $property?->street ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">Hausnummer</td>
            <td class="value">{{ $property?->house_number ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">PLZ</td>
            <td class="value">{{ $property?->zip ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">Ort</td>
            <td class="value">{{ $property?->city ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">Objektart</td>
            <td class="value">{{ $property?->property_type_label ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">Baujahr</td>
            <td class="value">{{ $property?->construction_year ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">Wohnfläche</td>
            <td class="value">
                {{ $property?->living_area ? number_format((float) $property->living_area, 0, ',', '.') . ' m²' : '-' }}
            </td>
        </tr>
        <tr>
            <td class="label">Grundstücksgröße</td>
            <td class="value">
                {{ $property?->plot_area ? number_format((float) $property->plot_area, 0, ',', '.') . ' m²' : '-' }}
            </td>
        </tr>
        <tr>
            <td class="label">Anzahl Zimmer</td>
            <td class="value">{{ $property?->rooms ?: '-' }}</td>
        </tr>
    </table>

    <div class="valuation">
        <h2>{{ $isFinalReport ? 'Wertermittlung' : 'Ersteinschätzung' }}</h2>

        @if($valuation?->range_low && $valuation?->range_high)
            @if($valuation?->estimated_value)
                <div class="valuation-value">
                    {{ number_format((float) $valuation->estimated_value, 0, ',', '.') }} €
                </div>
                <p class="valuation-range">
                    {{ $isFinalReport ? 'Ermittelter Immobilienwert' : 'Orientierungswert' }}
                </p>
            @endif

            <table class="valuation-meta">
                <tr>
                    <td class="label">Bewertungsspanne</td>
                    <td>
                        {{ number_format((float) $valuation->range_low, 0, ',', '.') }} €
                        bis
                        {{ number_format((float) $valuation->range_high, 0, ',', '.') }} €
                    </td>
                </tr>
            </table>
        @else
            <p>Eine Bewertung konnte noch nicht berechnet werden.</p>
        @endif
    </div>

    @if($mapImage)
        <div class="page-break"></div>

        <div class="header">
            <img src="{{ public_path('images/logo-2comehome.png') }}" alt="2 COME HOME Immobilien" class="logo">
        </div>

        <h2>Lage Ihrer Immobilie</h2>
        <p class="address">
            {{ $property?->street }} {{ $property?->house_number }}, {{ $property?->zip }} {{ $property?->city }}
        </p>

        <div class="map-wrap">
            <img src="{{ $mapImage }}" alt="Lage der Immobilie" class="map">
        </div>

        <h2>Hinweis zur Bewertung</h2>
        <p class="notice">
            @if($isFinalReport)
                Diese abschließende Wertermittlung basiert auf den im persönlichen Austausch konkretisierten
                Objektdaten sowie der anschließenden fachlichen Einwertung durch 2 COME HOME Immobilien.
                Berücksichtigt wurden insbesondere Lage, Zustand, Ausstattungsqualität, Modernisierungen,
                objektspezifische Besonderheiten und die aktuelle Marktsituation. Die Wertermittlung stellt
                kein Verkehrswertgutachten im Sinne einer förmlichen Gutachtenerstellung dar.
            @else
                Diese Immobilien-Ersteinschätzung wurde automatisiert auf Grundlage der von Ihnen übermittelten
                Objektdaten erstellt. Sie dient als erste Orientierung und stellt weder ein Verkehrswertgutachten
                noch eine verbindliche Kaufpreis- oder Verkaufspreisempfehlung dar. Für eine belastbare Einwertung
                berücksichtigen wir zusätzlich insbesondere Mikrolage, Zustand, Ausstattungsqualität,
                Modernisierungen, rechtliche Besonderheiten und die aktuelle Marktsituation.
            @endif
        </p>
    @else
        <p class="notice">
            @if($isFinalReport)
                Diese abschließende Wertermittlung basiert auf den im persönlichen Austausch konkretisierten
                Objektdaten sowie der anschließenden fachlichen Einwertung durch 2 COME HOME Immobilien.
                Sie stellt kein Verkehrswertgutachten im Sinne einer förmlichen Gutachtenerstellung dar.
            @else
                Diese Immobilien-Ersteinschätzung wurde automatisiert auf Grundlage der von Ihnen übermittelten
                Objektdaten erstellt. Sie dient als erste Orientierung und stellt weder ein Verkehrswertgutachten
                noch eine verbindliche Kaufpreis- oder Verkaufspreisempfehlung dar.
            @endif
        </p>
    @endif
</body>
</html>
