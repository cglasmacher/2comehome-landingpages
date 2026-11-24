<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Immobilienbewertung - 2 COME HOME Immobilien</title>
    <style>
        @page { size: A4; margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        /* Public landingpage headlines use Georgia (resources/css/app.css).
           Dompdf can fall back to its bundled DejaVu Serif when Georgia is not
           installed on the PHP host. This intentionally needs no remote fonts. */
        body { color: #111111; font-family: Georgia, "DejaVu Serif", serif; font-size: 10.8pt; line-height: 1.28; }
        .page { position: relative; width: 595pt; height: 841pt; page-break-after: always; overflow: hidden; }
        .page:last-child { page-break-after: auto; }
        .watermark { position: absolute; left: 143.75pt; top: 159pt; width: 315pt; height: 580pt; }
        .logo { position: absolute; left: 388.6pt; top: 28.35pt; width: 142.4pt; height: 110.5pt; }
        .page-two .logo { left: 411pt; top: 28.35pt; width: 141.7pt; height: 110pt; }
        .block { position: absolute; z-index: 1; }
        .sender { left: 70.8pt; top: 164pt; width: 294pt; color: #9c563b; font-size: 7.9pt; }
        .recipient { left: 70.8pt; top: 180pt; width: 300pt; font-size: 10.6pt; line-height: 1.34; }
        .recipient div { margin: 0; padding: 0; }
        .agent { left: 389.8pt; top: 162pt; width: 175pt; font-size: 8pt; line-height: 1.38; }
        .agent .agent-title { font-size: 10.2pt; line-height: 1.25; }
        .date { left: 389.8pt; top: 247pt; font-size: 10.8pt; }
        .vertical-mark { left: 10pt; top: 346pt; font-size: 13pt; color: #555; }
        .headline { left: 70.8pt; top: 305pt; width: 430pt; font-family: Georgia, "DejaVu Serif", serif; font-size: 26pt; font-weight: 400; line-height: 1.13; letter-spacing: -.028em; }
        .property-overview { left: 70.8pt; top: 351pt; width: 477pt; font-size: 10.5pt; line-height: 1.31; }
        .property-overview p { margin: 0; }
        .section-heading { font-family: Georgia, "DejaVu Serif", serif; font-size: 18pt; font-weight: 400; line-height: 1.17; letter-spacing: -.022em; }
        .object-heading { left: 70.8pt; top: 433pt; }
        .description { left: 70.8pt; top: 467pt; width: 464pt; font-size: 10.8pt; line-height: 1.30; }
        .description.compact { font-size: 10.15pt; line-height: 1.23; }
        .market-heading { left: 70.8pt; top: 581pt; }
        .market-copy { left: 70.8pt; top: 612pt; width: 466pt; font-size: 10.65pt; line-height: 1.29; }
        .market-copy.compact { font-size: 10.0pt; line-height: 1.20; }
        .price-range { left: 70.8pt; top: 678pt; width: 475pt; font-size: 11.0pt; line-height: 1.19; font-weight: 700; }
        .sqm-value { left: 70.8pt; top: 704pt; width: 468pt; font-size: 10.25pt; line-height: 1.25; }
        .footer { left: 69pt; right: 68pt; top: 748pt; height: 44pt; color: #a16d55; font-size: 6.35pt; line-height: 1.25; }
        .footer .foot-col { position: absolute; top: 0; }
        .footer .foot-left { left: 0; width: 168pt; }
        .footer .foot-middle { left: 174pt; width: 160pt; }
        .footer .foot-right { left: 337pt; width: 155pt; }
        .page-two .footer { top: 760pt; }
        .page-number { left: 70.8pt; top: 92pt; font-family: Arial, sans-serif; font-size: 6.1pt; }
        .orientation { left: 70.8pt; top: 127pt; width: 462pt; font-size: 11.0pt; line-height: 1.30; }
        .notes-heading { left: 70.8pt; top: 173pt; }
        .legal { left: 70.8pt; top: 207pt; width: 463pt; font-size: 10.5pt; line-height: 1.30; }
        .notes { left: 70.8pt; top: 263pt; width: 464pt; font-size: 10.45pt; line-height: 1.27; }
        .notes.compact { font-size: 9.65pt; line-height: 1.20; }
        .range-repeat { left: 70.8pt; top: 345pt; font-weight: 700; font-size: 10.9pt; line-height: 1.18; }
        .valuation-date { left: 70.8pt; top: 370pt; font-weight: 700; font-size: 10.9pt; line-height: 1.18; }
        .photo { position: absolute; z-index: 2; display: block; }
        .photo-1 { left: 70.9pt; top: 386.5pt; width: 144.7pt; height: 192.9pt; }
        .photo-2 { left: 221.9pt; top: 425.3pt; width: 205.5pt; height: 154.1pt; }
        .photo-3 { left: 70.9pt; top: 600.1pt; width: 191.9pt; height: 143.9pt; }
        .photo-4 { left: 271.9pt; top: 593pt; width: 201.4pt; height: 151pt; }
    </style>
</head>
<body>
@php
    $money = fn ($amount) => number_format((float) $amount, 0, ',', '.').' €';
    $address = trim(implode(' ', array_filter([$lead->property?->street, $lead->property?->house_number])));
    $address .= ($address && ($lead->property?->zip || $lead->property?->city) ? ', ' : '');
    $address .= trim(implode(' ', array_filter([$lead->property?->zip, $lead->property?->city])));
    $descriptionCompact = mb_strlen($letter->object_description) > 365;
    $marketCompact = mb_strlen($letter->market_description) > 230;
    $notesCompact = mb_strlen($letter->valuation_notes) > 325;
    // Allow emphasis in editable paragraphs without permitting arbitrary HTML.
    $boldText = fn (string $text) => nl2br(preg_replace('/\\*\\*(.+?)\\*\\*/us', '<strong>$1</strong>', e($text)));
@endphp
<div class="page page-one">
    <img src="{{ $brand['watermark'] }}" class="watermark" alt="">
    <img src="{{ $brand['logo'] }}" class="logo" alt="2 COME HOME Immobilien">
    <div class="block sender">{{ $company['company'] }} | Markt 14-16 | {{ $company['postcode_city'] }}</div>
    <div class="block recipient">
        <div>{{ $letter->recipient_name }}</div>
        @if($letter->recipient_street)<div>{{ $letter->recipient_street }}</div>@endif
        @if($letter->recipient_zip || $letter->recipient_city)<div>{{ trim($letter->recipient_zip.' '.$letter->recipient_city) }}</div>@endif
    </div>
    <div class="block agent">
        <div class="agent-title"><strong>Ihr Ansprechpartner:</strong></div>
        <div>{{ $letter->adviser_name }}</div>
        <div>{{ $letter->adviser_role }}</div>
        <div>Mobil: &nbsp;{{ $letter->adviser_mobile }}</div>
        <div>E-Mail: {{ $letter->adviser_email }}</div>
    </div>
    <div class="block date">{{ $company['place'] }}, {{ $letter->issued_on->format('d.m.Y') }}</div>
    <div class="block vertical-mark">–</div>
    <div class="block headline">Immobilienbewertung</div>
    <div class="block property-overview">
        <p><strong>Objekt:</strong> {{ $letter->property_label }}</p>
        <p><strong>Adresse:</strong> {{ $address ?: '–' }}</p>
        @if($letter->floor_location)<p><strong>Lage im Gebäude:</strong> {{ $letter->floor_location }}</p>@endif
        <p><strong>Wohnfläche:</strong> {{ $lead->property?->living_area ? 'ca. '.$lead->property->living_area.' m²' : '–' }}</p>
        @if($letter->room_summary)<p><strong>Zimmer:</strong> {{ $letter->room_summary }}</p>@endif
    </div>
    <div class="block section-heading object-heading">Objektbeschreibung</div>
    <div class="block description{{ $descriptionCompact ? ' compact' : '' }}">{!! $boldText($letter->object_description) !!}</div>
    <div class="block section-heading market-heading">Marktwerteinschätzung</div>
    <div class="block market-copy{{ $marketCompact ? ' compact' : '' }}">{!! $boldText($letter->market_description) !!}</div>
    <div class="block price-range">Geschätzter Marktwert: {{ $money($letter->value_low) }} bis {{ $money($letter->value_high) }}</div>
    @if($pricePerSquareMeter)
        <div class="block sqm-value">
            Dies entspricht bei einer Wohnfläche von ca. {{ $lead->property->living_area }} m² einem Wert von rund
            <strong>{{ $money($pricePerSquareMeter['low']) }} bis {{ $money($pricePerSquareMeter['high']) }}</strong>
            <strong>pro m² Wohnfläche.</strong>
        </div>
    @endif
    <div class="block footer">
        <div class="foot-col foot-left">{{ $company['company'] }}<br>{{ $company['street'] }}<br>{{ $company['postcode_city'] }}<br>{{ $company['country'] }}</div>
        <div class="foot-col foot-middle">T: {{ $company['office_phone'] }}<br>M: {{ $company['mobile'] }}<br>{{ $company['email'] }}<br>{{ $company['website'] }}</div>
        <div class="foot-col foot-right">Inhaber: {{ $company['owner'] }}<br>{{ $company['bank_name'] }}<br>IBAN: {{ $company['iban'] }}<br>UST-IdNr.: {{ $company['vat_id'] }}</div>
    </div>
</div>

<div class="page page-two">
    <img src="{{ $brand['watermark'] }}" class="watermark" alt="">
    <img src="{{ $brand['logo'] }}" class="logo" alt="2 COME HOME Immobilien">
    <div class="block page-number">Seite 2 von 2</div>
    <div class="block orientation">Als realistischer Orientierungswert kann somit ein Immobilienwert von rund <strong>{{ $money($letter->orientation_value) }}</strong> angesetzt werden.</div>
    <div class="block section-heading notes-heading">Hinweise zur Bewertung</div>
    <div class="block legal">
        Die vorliegende Bewertung stellt eine <strong>marktbezogene Preiseinschätzung</strong> dar und dient der
        Orientierung hinsichtlich eines aktuell erzielbaren Verkaufspreises. Sie stellt kein Verkehrswertgutachten
        im Sinne des § 194 BauGB dar.
    </div>
    <div class="block notes{{ $notesCompact ? ' compact' : '' }}">{!! $boldText($letter->valuation_notes) !!}</div>
    <div class="block range-repeat">Bewertungsspanne: {{ $money($letter->value_low) }} – {{ $money($letter->value_high) }}</div>
    <div class="block valuation-date">Bewertungsstichtag: {{ $letter->valuation_date->locale('de')->translatedFormat('F Y') }}</div>
    @foreach($photos as $position => $dataUrl)
        <img class="photo photo-{{ $position }}" src="{{ $dataUrl }}" alt="">
    @endforeach
    <div class="block footer">
        <div class="foot-col foot-left">{{ $company['company'] }}<br>{{ $company['street'] }}<br>{{ $company['postcode_city'] }}<br>{{ $company['country'] }}</div>
        <div class="foot-col foot-middle">T: {{ $company['office_phone'] }}<br>M: {{ $company['mobile'] }}<br>{{ $company['email'] }}<br>{{ $company['website'] }}</div>
        <div class="foot-col foot-right">Inhaber: {{ $company['owner'] }}<br>{{ $company['bank_name'] }}<br>IBAN: {{ $company['iban'] }}<br>UST-IdNr.: {{ $company['vat_id'] }}</div>
    </div>
</div>
</body>
</html>
