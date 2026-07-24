<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ihre Immobilien-Einschätzung</title>
</head>
<body style="margin:0; padding:0; background:#FAFAFA; color:#2D2D2D; font-family:Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#FAFAFA;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:620px; background:#FFFFFF; border:1px solid #E5E7EB;">
                    <tr>
                        <td style="padding:28px 32px; border-top:5px solid #C84C3D;">
                            <p style="margin:0; color:#C84C3D; font-size:12px; font-weight:bold; letter-spacing:1.5px; text-transform:uppercase;">2 COME HOME Immobilien</p>
                            <h1 style="margin:20px 0 12px; color:#2D2D2D; font-size:28px; line-height:1.2;">Vielen Dank für Ihre Anfrage{{ $lead->first_name ? ', ' . $lead->first_name : '' }}.</h1>
                            <p style="margin:0; color:#6B7280; font-size:16px; line-height:1.6;">Wir haben Ihre Angaben erhalten und melden uns schnellstmöglich persönlich bei Ihnen.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 28px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#FAFAFA; border-left:4px solid #D4A017;">
                                <tr>
                                    <td style="padding:20px 22px;">
                                        <p style="margin:0 0 6px; color:#6B7280; font-size:12px; font-weight:bold; letter-spacing:1px; text-transform:uppercase;">Ihre erste Einschätzung</p>
                                        <p style="margin:0; color:#2D2D2D; font-size:26px; font-weight:bold; line-height:1.25;">
                                            @if($lead->valuation?->range_low && $lead->valuation?->range_high)
                                                {{ number_format((float) $lead->valuation->range_low, 0, ',', '.') }} € – {{ number_format((float) $lead->valuation->range_high, 0, ',', '.') }} €
                                            @else
                                                Wird persönlich geprüft
                                            @endif
                                        </p>
                                        <p style="margin:10px 0 0; color:#6B7280; font-size:13px; line-height:1.5;">Unverbindliche automatisierte Ersteinschätzung – keine offizielle Einwertung.</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 28px;">
                            <h2 style="margin:0 0 16px; color:#2D2D2D; font-size:18px;">Ihre Angaben im Überblick</h2>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td valign="top" width="50%" style="padding:0 12px 14px 0;">
                                        <p style="margin:0 0 4px; color:#6B7280; font-size:11px; font-weight:bold; letter-spacing:1px; text-transform:uppercase;">Kontakt</p>
                                        <p style="margin:0; color:#2D2D2D; font-size:14px; line-height:1.5;">{{ trim(($lead->first_name ?? '') . ' ' . ($lead->last_name ?? '')) ?: '—' }}<br>{{ $lead->email }}<br>{{ $lead->phone }}</p>
                                    </td>
                                    <td valign="top" width="50%" style="padding:0 0 14px 12px;">
                                        <p style="margin:0 0 4px; color:#6B7280; font-size:11px; font-weight:bold; letter-spacing:1px; text-transform:uppercase;">Immobilie</p>
                                        <p style="margin:0; color:#2D2D2D; font-size:14px; line-height:1.5;">{{ $lead->property?->property_type ?: '—' }}<br>{{ trim(($lead->property?->street ?? '') . ' ' . ($lead->property?->house_number ?? '')) ?: '—' }}<br>{{ trim(($lead->property?->zip ?? '') . ' ' . ($lead->property?->city ?? '')) ?: '—' }}</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="top" width="50%" style="padding:0 12px 0 0;">
                                        <p style="margin:0 0 4px; color:#6B7280; font-size:11px; font-weight:bold; letter-spacing:1px; text-transform:uppercase;">Flächen &amp; Baujahr</p>
                                        <p style="margin:0; color:#2D2D2D; font-size:14px; line-height:1.5;">Wohnfläche: {{ $lead->property?->living_area ? $lead->property->living_area . ' m²' : '—' }}<br>Grundstück: {{ $lead->property?->plot_area ? $lead->property->plot_area . ' m²' : '—' }}<br>Baujahr: {{ $lead->property?->construction_year ?: '—' }}</p>
                                    </td>
                                    <td valign="top" width="50%" style="padding:0 0 0 12px;">
                                        <p style="margin:0 0 4px; color:#6B7280; font-size:11px; font-weight:bold; letter-spacing:1px; text-transform:uppercase;">Ihre Notiz</p>
                                        <p style="margin:0; color:#2D2D2D; font-size:14px; line-height:1.5;">{{ $lead->notes ?: 'Keine Notiz hinterlegt.' }}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:22px 32px; background:#2D2D2D;">
                            <p style="margin:0 0 8px; color:#FFFFFF; font-size:16px; font-weight:bold;">Wie geht es weiter?</p>
                            <p style="margin:0; color:#E5E7EB; font-size:14px; line-height:1.6;">Wir prüfen Ihre Angaben persönlich und melden uns schnellstmöglich bei Ihnen. Ihren Bewertungsbericht können Sie hier öffnen:</p>
                            <p style="margin:18px 0 0;"><a href="{{ route('valuation-reports.show', $lead) }}" style="display:inline-block; padding:12px 18px; background:#C84C3D; color:#FFFFFF; font-size:14px; font-weight:bold; text-decoration:none;">PDF-Bericht öffnen</a></p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 32px;">
                            <p style="margin:0; color:#6B7280; font-size:12px; line-height:1.5;">Diese E-Mail wurde automatisch nach Ihrer Immobilienbewertung versendet. Bei Fragen antworten Sie gerne direkt auf diese Nachricht.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
