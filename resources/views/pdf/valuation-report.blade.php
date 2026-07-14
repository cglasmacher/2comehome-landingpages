<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Immobilienbewertung – 2COME HOME</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #2D2D2D; margin: 40px; }
        .muted { color: #6B7280; }
        .box { border: 1px solid #E5E7EB; padding: 18px; margin-top: 18px; border-radius: 8px; }
        h1 { font-size: 26px; color: #C84C3D; }
        h2 { font-size: 18px; margin-bottom: 6px; color: #2D2D2D; }
        .logo { max-width: 150px; margin-bottom: 20px; }
        .range { font-size: 22px; font-weight: bold; color: #C84C3D; }
    </style>
</head>
<body>
    <img src="{{ public_path('images/logo-2comehome.png') }}" alt="2COME HOME Immobilien" class="logo">

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
            <p class="range">
                {{ number_format((float) $valuation->range_low, 0, ',', '.') }} €
                bis
                {{ number_format((float) $valuation->range_high, 0, ',', '.') }} €
            </p>
            <p class="muted">Automatisierte Ersteinschätzung mit +/- {{ $valuation->range_percent }} % Range.</p>
        @else
            <p>Eine Bewertung konnte noch nicht berechnet werden.</p>
        @endif
    </div>

    <p class="muted">Hinweis: Diese Bewertung ist eine automatisierte Ersteinschätzung und keine verbindliche Verkehrswertermittlung.</p>
</body>
</html>
