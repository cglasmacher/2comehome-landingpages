<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Immobilienbewertung</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111827; }
        .muted { color: #6b7280; }
        .box { border: 1px solid #e5e7eb; padding: 18px; margin-top: 18px; }
        h1 { font-size: 26px; }
        h2 { font-size: 18px; margin-bottom: 6px; }
    </style>
</head>
<body>
    <h1>Ihre Immobilien-Ersteinschätzung</h1>
    <p class="muted">Erstellt für {{ trim($lead->first_name . ' ' . $lead->last_name) ?: 'Interessent' }}</p>

    <div class="box">
        <h2>Objekt</h2>
        <p>{{ $property?->street }} {{ $property?->house_number }}, {{ $property?->zip }} {{ $property?->city }}</p>
        <p>Wohnfläche: {{ $property?->living_area ?? '-' }} m²</p>
        <p>Grundstück: {{ $property?->plot_area ?? '-' }} m²</p>
        <p>Baujahr: {{ $property?->construction_year ?? '-' }}</p>
    </div>

    <div class="box">
        <h2>Bewertungsrange</h2>
        @if($valuation?->range_low && $valuation?->range_high)
            <p><strong>{{ number_format((float) $valuation->range_low, 0, ',', '.') }} € bis {{ number_format((float) $valuation->range_high, 0, ',', '.') }} €</strong></p>
            <p class="muted">Automatisierte Ersteinschätzung mit +/- {{ $valuation->range_percent }} % Range.</p>
        @else
            <p>Eine Bewertung konnte noch nicht berechnet werden.</p>
        @endif
    </div>

    <p class="muted">Hinweis: Diese Bewertung ist eine automatisierte Ersteinschätzung und keine verbindliche Verkehrswertermittlung.</p>
</body>
</html>
