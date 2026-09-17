<!doctype html>
<html lang="de"><head><meta charset="utf-8"><title>Neuer Landingpage Lead</title></head>
<body style="font-family:Arial,sans-serif;color:#2D2D2D;background:#F5F3EF;padding:24px">
<div style="max-width:640px;margin:auto;background:white;border-top:5px solid #C84C3D;padding:32px">
<p style="color:#C84C3D;font-weight:bold">2 COME HOME · Neue Anfrage</p>
<h1 style="font-size:24px">Landingpage Lead von {{ $lead->landingPage->slug }}</h1>
<p>Der Bewertungsbericht ist als PDF angehängt. Antworten auf diese E-Mail gehen direkt an den Interessenten.</p>
<h2 style="font-size:18px">Kontakt</h2>
<p>{{ trim($lead->first_name.' '.$lead->last_name) }}<br>
<a href="mailto:{{ $lead->email }}">{{ $lead->email }}</a><br>{{ $lead->phone }}</p>
<h2 style="font-size:18px">Immobilie</h2>
<p>{{ $lead->property?->property_type }}<br>
{{ $lead->property?->street }} {{ $lead->property?->house_number }}<br>
{{ $lead->property?->zip }} {{ $lead->property?->city }}<br>
Wohnfläche: {{ $lead->property?->living_area ?? '–' }} m² · Grundstück: {{ $lead->property?->plot_area ?? '–' }} m²<br>
Baujahr: {{ $lead->property?->construction_year ?? '–' }} · Zimmer: {{ $lead->property?->rooms ?? '–' }}</p>
<h2 style="font-size:18px">Ersteinschätzung</h2>
@if($lead->valuation?->range_low && $lead->valuation?->range_high)
<p>{{ number_format((float) $lead->valuation->range_low, 0, ',', '.') }} € bis {{ number_format((float) $lead->valuation->range_high, 0, ',', '.') }} €</p>
@else
<p>Keine automatische Einschätzung verfügbar. Persönliche Prüfung erforderlich.</p>
@endif
<h2 style="font-size:18px">Nachricht</h2><p style="white-space:pre-wrap">{{ $lead->notes ?: 'Keine Nachricht hinterlassen.' }}</p>
<p>Telefonische Kontaktaufnahme: {{ $lead->phone_contact_consent_at ? 'zugestimmt' : 'keine Zustimmung' }}</p>
<p style="font-size:12px;color:#666">Lead-ID: {{ $lead->id }} · Kanal: {{ $lead->landingPage->slug }}</p>
</div></body></html>
