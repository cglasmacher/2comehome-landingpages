<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Maklerauftrag</title>
    <style>
        @font-face { font-family: Caslon; src: url("{{ resource_path('contracts/brand/LibreCaslonText-Regular.ttf') }}") format("truetype"); }
        @page { margin: 128pt 68pt 77pt 71pt; }
        body { margin: 0; font: 10pt/1.22 Caslon, "Times New Roman", serif; color: #000; }
        header { position: fixed; top: -93pt; left: -1pt; }
        header img { width: 484pt; height: 65pt; }
        footer { position: fixed; bottom: -41pt; left: -1pt; }
        footer img { width: 405pt; height: 37pt; }
        h1 { font: bold 10pt Helvetica, sans-serif; margin: 0 0 22pt; text-align: center; }
        .contract-title { margin-top: 34pt; }
        h2 { font-size: inherit; font-weight: normal; margin: 0 0 10pt; page-break-after: avoid; }
        p { margin: 0 0 10pt; }
        .address, .special { white-space: pre-line; }
        .seller-address { margin: 24pt 0 16pt; }
        .clause { margin-left: 36pt; position: relative; }
        .clause h2 { margin-bottom: 7pt; }
        .clause h2 span { position: absolute; left: -18pt; }
        .clause p { text-align: justify; margin-bottom: 7pt; }
        .clause + .clause { margin-top: 16pt; }
        .page-break { page-break-before: always; }
        .signatures { width: 100%; table-layout: fixed; border-collapse: collapse; margin-top: 12pt; }
        .signatures td { width: 46%; vertical-align: top; padding: 0 14pt 0 0; }
        .signatures td:first-child { width: 54%; }
        .signature { page-break-inside: avoid; margin-bottom: 14pt; }
        .signature img { display: block; max-width: 120pt; max-height: 40pt; }
        .signature-space { min-height: 40pt; }
        .signature-line { border-bottom: .5pt solid black; width: 160pt; }
        .signature p { margin: 0; }
        .legal { font-size: 8pt; line-height: 1.23; }
        .legal p { margin-bottom: 6pt; text-align: justify; }
        .legal h2 { margin: 9pt 0 3pt; }
        .legal h1 { font-size: 9pt; margin-bottom: 20pt; }
        .agb h1 { text-align: left; margin-bottom: 12pt; }
        .agb { font-size: 7.7pt; line-height: 1.12; }
        .agb p { margin-bottom: 4pt; }
        .agb h2 { margin: 6pt 0 2pt; }
        .page-break.clause + .clause, .page-break.clause + .clause ~ .clause { margin-top: 10pt; }
        .muted { font-size: 8pt; }
        .agb .date { text-align: right; }
        .draft { font: bold 8pt Helvetica, sans-serif; }
    </style>
</head>
<body>
<header><img src="{{ $document['artwork']['header'] }}" alt="2 COME HOME Immobilien"></header>
<footer><img src="{{ $document['artwork']['footer'] }}" alt="Geschäftsangaben"></footer>
@if($draft)<p class="draft">ENTWURF – noch nicht zur Unterzeichnung freigegeben</p>@endif
<h1 class="contract-title">Befristeter Makler-Alleinauftrag</h1>
<p>Zwischen</p>
@foreach($document['sellers'] as $seller)
    <p>Name: {{ $seller['name'] }}</p>
    <p class="address seller-address">Anschrift: {{ $seller['address'] }}</p>
@endforeach
<p>im Folgenden -Verkäufer- genannt</p>
<p>und</p>
<p>{{ $document['broker_name'] }}, {{ $document['broker_address'] }}</p>
<p>im Folgenden -Makler- genannt</p>
<div class="clause">
<h2><span>1)</span> Objekt:</h2>
<p>Die Verkäufer beauftragen den Makler mit dem Verkauf der folgenden Immobilie:</p>
<p class="address">Gelegen {{ $document['property_address'] }}@if($document['property_description']), {{ $document['property_description'] }}@endif</p>
</div><div class="clause">
<h2><span>2)</span> Laufzeit:</h2>
<p>Der Vertrag beginnt am {{ $document['starts_on'] }} und ist befristet bis zum {{ $document['ends_on'] }}.
@if($document['renewal_months'] > 0)
Danach verlängert er sich jeweils um {{ $document['renewal_months'] }} {{ $document['renewal_months'] == 1 ? 'Monat' : 'Monate' }}, sofern der Verkäufer diesen nicht kündigt. Die Kündigungsfrist beträgt {{ $document['notice_months'] }} {{ $document['notice_months'] == 1 ? 'Monat' : 'Monate' }} zum Monatsende, jedoch nicht vor dem {{ $document['ends_on'] }}.
@else
Der Vertrag endet zu diesem Datum ohne automatische Verlängerung. Die Kündigungsfrist beträgt {{ $document['notice_months'] }} {{ $document['notice_months'] == 1 ? 'Monat' : 'Monate' }} zum Monatsende, jedoch nicht vor dem {{ $document['ends_on'] }}.
@endif
</p>
</div>
@foreach($document['terms']['duties'] as $section)
    <div class="clause{{ $loop->index === 1 ? ' page-break' : '' }}">
    <h2><span>{{ $loop->index + 3 }})</span> {{ $section['title'] }}</h2>
    @foreach($section['paragraphs'] as $paragraph)<p>{{ $paragraph }}</p>@endforeach
    </div>
@endforeach
<div class="clause">
<h2><span>5)</span> Angebotspreis:</h2>
<p>Der Angebotspreis wurde nach Marktwerteinschätzung in Absprache mit dem Verkäufer </p><p>festgelegt und beträgt: {{ $document['offer_price'] ? number_format((float) $document['offer_price'], 2, ',', '.').' Euro' : 'noch nicht festgelegt' }}.</p>
</div><div class="clause">
<h2><span>6)</span> Courtage:</h2>
<p>Der Verkäufer verpflichtet sich, bei Abschluss des Kaufvertrages eine Makler-Innenprovision in Höhe von {{ number_format((float) $document['commission_percent'], 2, ',', '.') }} % einschließlich MwSt. des Kaufpreises an den Makler zu zahlen.</p>
<p>{{ $document['terms']['commission_addendum'] }}</p>
</div><div class="clause">
<h2><span>7)</span> Kündigung und Nachweise:</h2>
<p>{{ $document['terms']['termination_evidence'] }}</p>
</div>
@if($document['special_agreements'])<h2>Besondere Vereinbarungen</h2><p class="special">{{ $document['special_agreements'] }}</p>@endif
<table class="signatures">
@foreach($document['sellers'] as $position => $seller)
    @php($signer = $signers->firstWhere('position', $position))
    <tr><td>
        <div class="signature">
            <div class="signature-space">
            @if($signer?->signed_at)
                <img src="{{ $signer->signature_data }}" alt="Unterschrift Verkäufer">
                <p>{{ $signer->signing_place }}, {{ $signer->signed_at->timezone('Europe/Berlin')->format('d.m.Y') }}</p>
            @endif
            </div>
            <div class="signature-line"></div>
            <p>Ort, Datum &nbsp; Verkäufer @if(count($document['sellers']) > 1) · {{ $seller['name'] }}@endif</p>
        </div>
    </td><td>
        @if($loop->first)
            <div class="signature">
                <div class="signature-space">
                    @if($document['agent_signature'])<img src="{{ $document['agent_signature'] }}" alt="Unterschrift Makler">@endif
                    <p>{{ $document['signing_place'] }}, {{ $document['issued_on'] }}</p>
                </div>
                <div class="signature-line"></div>
                <p>Ort, Datum &nbsp; Makler · {{ $document['agent_name'] }}</p>
            </div>
        @endif
    </td></tr>
@endforeach
</table>
<div class="legal page-break">
<h1>Widerrufsrecht</h1>
@foreach($document['terms']['withdrawal'] as $section)
    @unless($loop->first)<h2>{{ $section['title'] }}</h2>@endunless
    @foreach($section['paragraphs'] as $paragraph)<p>{{ $paragraph }}</p>@endforeach
@endforeach
<p>Ende der Widerrufsbelehrung</p>
</div>
<div class="legal agb page-break">
<h1>Allgemeine Geschäftsbedingungen</h1>
@foreach($document['terms']['agb'] as $section)
    <h2>{{ $section['title'] }}</h2>
    @foreach($section['paragraphs'] as $paragraph)<p>{{ $paragraph }}</p>@endforeach
@endforeach
<p class="date">Stand: {{ $document['terms']['source_date'] }}</p>
</div>
@if($signers->whereNotNull('signed_at')->isNotEmpty())
    <div class="page-break"></div>
    <h1>Unterzeichnungsprotokoll</h1>
    <p>Auftrag {{ $document['uuid'] }}</p>
    @foreach($signers->whereNotNull('signed_at') as $signer)
        <h2>{{ $signer->name }}</h2>
        <p>Unterzeichnet in {{ $signer->signing_place }} am {{ $signer->signed_at->timezone('Europe/Berlin')->format('d.m.Y H:i:s') }} Uhr (Europe/Berlin).</p>
        <p>Vertragsinhalt einschließlich AGB und Widerrufsbelehrung bestätigt.</p>
        <p>Vorzeitiger Tätigkeitsbeginn ausdrücklich gewünscht: {{ $signer->early_start_requested ? 'Ja' : 'Nein' }}.</p>
        <p>Kenntnis vom Erlöschen des Widerrufsrechts bei vollständiger Vertragserfüllung ausdrücklich bestätigt: {{ $signer->withdrawal_loss_acknowledged ? 'Ja' : 'Nein' }}.</p>
        <p class="muted">SHA-256 des zur Unterzeichnung bereitgestellten Originaldokuments:<br>{{ $signer->document_sha256 }}</p>
    @endforeach
@endif
</body>
</html>
