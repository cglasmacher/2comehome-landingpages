<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Immobilienbewertung - 2 COME HOME Immobilien</title>
    <style>
        @page { size: A4; margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body { color: #090909; font-family: "Times New Roman", "DejaVu Serif", serif; font-size: 12pt; }
        .page { position: relative; width: 595pt; height: 841pt; page-break-after: always; overflow: hidden; }
        .page:last-child { page-break-after: auto; }
        .watermark { position: absolute; left: 143.75pt; top: 159pt; width: 315pt; height: 580pt; }
        .logo { position: absolute; left: 388.6pt; top: 28.35pt; width: 142.4pt; height: 107.3pt; }
        .page-two .logo { left: 411pt; top: 28.35pt; width: 141.7pt; height: 103.5pt; }
        .block { position: absolute; z-index: 1; }
        .sender { left: 70.8pt; top: 164pt; width: 294pt; color: #9c563b; font-size: 7.9pt; }
        .recipient { left: 70.8pt; top: 180pt; width: 300pt; font-size: 11pt; line-height: 1.2; }
        .agent { left: 389.8pt; top: 162pt; width: 175pt; font-family: Georgia, "DejaVu Serif", serif; font-size: 7.9pt; line-height: 1.42; }
        .agent .agent-title { font-size: 10.5pt; }
        .date { left: 389.8pt; top: 247pt; font-size: 11pt; }
        .vertical-mark { left: 10pt; top: 346pt; font-size: 13pt; color: #555; }
        .headline { left: 70.8pt; top: 306pt; width: 430pt; font-size: 24pt; font-weight: 700; line-height: 1.05; }
        .property-overview { left: 70.8pt; top: 349pt; width: 477pt; font-size: 11.8pt; line-height: 1.14; }
        .property-overview p { margin: 0; }
        .section-heading { font-size: 18pt; font-weight: 700; line-height: 1.15; }
        .object-heading { left: 70.8pt; top: 431pt; }
        .description { left: 70.8pt; top: 466pt; width: 464pt; font-size: 11.9pt; line-height: 1.2; }
        .description.compact { font-size: 10.7pt; line-height: 1.17; }
        .market-heading { left: 70.8pt; top: 577pt; }
        .market-copy { left: 70.8pt; top: 612pt; width: 466pt; line-height: 1.18; font-size: 11.8pt; }
        .market-copy.compact { font-size: 10.5pt; }
        .price-range { left: 70.8pt; top: 667pt; width: 475pt; font-size: 12pt; font-weight: 700; }
        .sqm-value { left: 70.8pt; top: 695pt; width: 468pt; line-height: 1.2; font-size: 11.8pt; }
        .footer { left: 69pt; right: 68pt; top: 748pt; height: 44pt; color: #a16d55; font-size: 6.45pt; line-height: 1.24; }
        .footer .foot-col { position: absolute; top: 0; }
        .footer .foot-left { left: 0; width: 168pt; }
        .footer .foot-middle { left: 174pt; width: 160pt; }
        .footer .foot-right { left: 337pt; width: 155pt; }
        .page-two .footer { top: 760pt; }
        .page-number { left: 70.8pt; top: 92pt; font-family: Arial, sans-serif; font-size: 6.1pt; }
        .orientation { left: 70.8pt; top: 129pt; width: 462pt; line-height: 1.22; font-size: 12pt; }
        .notes-heading { left: 70.8pt; top: 171pt; }
        .legal { left: 70.8pt; top: 206pt; width: 463pt; line-height: 1.2; font-size: 11.8pt; }
        .notes { left: 70.8pt; top: 261pt; width: 464pt; font-size: 11.8pt; line-height: 1.2; }
        .notes.compact { font-size: 10.5pt; line-height: 1.16; }
        .range-repeat { left: 70.8pt; top: 330pt; font-weight: 700; font-size: 11.8pt; }
        .valuation-date { left: 70.8pt; top: 359pt; font-weight: 700; font-size: 11.8pt; }
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
    <div class="block recipient">{{ $letter->recipient_name }}</div>
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
