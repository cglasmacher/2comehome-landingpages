<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Maklerauftrag</title>
    <style>
        @page { margin: 32mm 20mm 23mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; line-height: 1.35; color: #26313a; }
        header { position: fixed; top: -23mm; left: 0; right: 0; height: 21mm; border-bottom: 1px solid #d9d5cc; }
        header img { height: 18mm; width: auto; max-width: 55mm; }
        footer { position: fixed; bottom: -16mm; font-size: 7pt; color: #74776f; }
        h1 { font-size: 17pt; margin: 0 0 5mm; color: #172741; }
        h2 { font-size: 10pt; margin: 3mm 0 1mm; color: #172741; page-break-after: avoid; }
        p { margin: 0 0 1.5mm; }
        .address, .special { white-space: pre-line; }
        .withdrawal { line-height: 1.25; }
        .withdrawal p { margin-bottom: 1mm; }
        .page-break { page-break-before: always; }
        .signature { page-break-inside: avoid; margin: 5mm 0; }
        .signature img { display: block; max-width: 170px; max-height: 55px; }
        .signature-line { border-bottom: 1px solid #74776f; height: 17mm; width: 65mm; }
        .muted { font-size: 8pt; color: #74776f; }
        .draft { font-weight: bold; color: #a24a3d; }
    </style>
</head>
<body>
<header>@if($document['logo'])<img src="{{ $document['logo'] }}" alt="2 COME HOME">@else 2 COME HOME Immobilien @endif</header>
<footer>{{ $document['broker_name'] }} · {{ $document['broker_address'] }} · {{ $document['broker_email'] }}<br>Auftrag {{ $document['uuid'] }} · Vorlage {{ $document['template_version'] }}</footer>
@if($draft)<p class="draft">ENTWURF – noch nicht zur Unterzeichnung freigegeben</p>@endif
<h1>Befristeter Makler-Alleinauftrag</h1>
<p>Zwischen</p>
@foreach($document['sellers'] as $seller)
    <p class="address"><strong>{{ $seller['name'] }}</strong><br>{{ $seller['address'] }}</p>
@endforeach
<p>im Folgenden „Verkäufer“ genannt</p>
<p>und <strong>{{ $document['broker_name'] }}</strong>, {{ $document['broker_address'] }}, im Folgenden „Makler“ genannt.</p>
<h2>Objekt</h2>
<p>Die Verkäufer beauftragen den Makler mit dem Verkauf der folgenden Immobilie:</p>
<p class="address"><strong>{{ $document['property_description'] }}</strong><br>{{ $document['property_address'] }}</p>
<h2>Laufzeit</h2>
<p>Der Vertrag beginnt am <strong>{{ $document['starts_on'] }}</strong> und ist befristet bis zum <strong>{{ $document['ends_on'] }}</strong>.
@if($document['renewal_months'] > 0)
Danach verlängert er sich jeweils um {{ $document['renewal_months'] }} {{ $document['renewal_months'] == 1 ? 'Monat' : 'Monate' }}, sofern der Verkäufer diesen nicht kündigt. Die Kündigungsfrist beträgt {{ $document['notice_months'] }} {{ $document['notice_months'] == 1 ? 'Monat' : 'Monate' }} zum Monatsende, jedoch nicht vor dem {{ $document['ends_on'] }}.
@else
Der Vertrag endet zu diesem Datum ohne automatische Verlängerung. Die Kündigungsfrist beträgt {{ $document['notice_months'] }} {{ $document['notice_months'] == 1 ? 'Monat' : 'Monate' }} zum Monatsende, jedoch nicht vor dem {{ $document['ends_on'] }}.
@endif
</p>
@foreach($document['terms']['duties'] as $section)
    <h2>{{ $section['title'] }}</h2>
    @foreach($section['paragraphs'] as $paragraph)<p>{{ $paragraph }}</p>@endforeach
@endforeach
<h2>Angebotspreis</h2>
<p>Der Angebotspreis wurde nach Marktwerteinschätzung in Absprache mit dem Verkäufer festgelegt und beträgt <strong>{{ $document['offer_price'] ? number_format((float) $document['offer_price'], 2, ',', '.').' €' : 'noch nicht festgelegt' }}</strong>.</p>
<h2>Courtage</h2>
<p>Der Verkäufer verpflichtet sich, bei Abschluss des Kaufvertrages eine Makler-Innenprovision in Höhe von <strong>{{ number_format((float) $document['commission_percent'], 2, ',', '.') }} % einschließlich MwSt.</strong> des Kaufpreises an den Makler zu zahlen.</p>
<p>{{ $document['terms']['commission_addendum'] }}</p>
<h2>Kündigung und Nachweise</h2>
<p>{{ $document['terms']['termination_evidence'] }}</p>
@if($document['special_agreements'])<h2>Besondere Vereinbarungen</h2><p class="special">{{ $document['special_agreements'] }}</p>@endif
@foreach($document['sellers'] as $position => $seller)
    @php($signer = $signers->firstWhere('position', $position))
    <div class="signature">
        <p><strong>{{ $seller['name'] }} · Verkäufer</strong></p>
        @if($signer?->signed_at)
            <img src="{{ $signer->signature_data }}" alt="Unterschrift Verkäufer">
            <p>{{ $signer->signing_place }}, {{ $signer->signed_at->timezone('Europe/Berlin')->format('d.m.Y H:i') }} Uhr</p>
        @else
            <div class="signature-line"></div><p class="muted">Ort, Datum, Unterschrift Verkäufer</p>
        @endif
    </div>
@endforeach
<div class="signature">
    @if($document['agent_signature'])<img src="{{ $document['agent_signature'] }}" alt="Unterschrift Makler">@else<div class="signature-line"></div>@endif
    <p>{{ $document['signing_place'] }}, {{ $document['issued_on'] }}<br>{{ $document['agent_name'] }} für {{ $document['broker_name'] }}</p>
</div>
<div class="page-break"></div>
<div class="withdrawal">
<h1>Widerrufsbelehrung</h1>
@foreach($document['terms']['withdrawal'] as $section)
    <h2>{{ $section['title'] }}</h2>
    @foreach($section['paragraphs'] as $paragraph)<p>{{ $paragraph }}</p>@endforeach
@endforeach
<p>Ende der Widerrufsbelehrung</p>
</div>
<div class="page-break"></div>
<h1>Allgemeine Geschäftsbedingungen</h1>
@foreach($document['terms']['agb'] as $section)
    <h2>{{ $section['title'] }}</h2>
    @foreach($section['paragraphs'] as $paragraph)<p>{{ $paragraph }}</p>@endforeach
@endforeach
<p class="muted">Stand der übernommenen AGB: {{ $document['terms']['source_date'] }}</p>
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
