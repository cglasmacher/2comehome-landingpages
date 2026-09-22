# Bewertungsanbieter und Fallback

Die zentrale Konfiguration steht in `config/valuation.php`:

```php
'order' => ['somantic', 'pricehubble', 'formula'],
```

Der erste aktivierte Anbieter mit einem gültigen Ergebnis gewinnt. Es werden nicht alle drei APIs parallel abgefragt. Reihenfolge verändern oder Einträge weglassen, um umzuschalten. Nur Somantic: `['somantic']`. Nur Formel: `['formula']`. Über `providers.<name>.enabled` können Anbieter zusätzlich deaktiviert werden. Nach jeder Änderung `php artisan config:cache` ausführen.

## Einrichtung und Deployment

1. Pull Request mergen, in Plesk pullen/bereitstellen.
2. Den Somantic-Schlüssel ausschließlich auf dem Server in `.env` eintragen (niemals in Git):

```dotenv
SOMANTIC_API_KEY="DEIN_NEUER_API_SCHLUESSEL"
```

3. `php artisan config:cache` und `npm run build` ausführen.
4. Einen neuen Testlead anlegen und `valuation provider selected` im Laravel-Log prüfen. Bei Erfolg muss dort `provider: somantic` stehen. Dieser Test nutzt das Somantic-Kontingent. Keine neue Migration nötig.

Weitere optionale Einstellungen:

```dotenv
SOMANTIC_ENABLED=true
SOMANTIC_BASE_URL=https://www.somantic.net/api
SOMANTIC_TIMEOUT=10
PRICEHUBBLE_ENABLED=true
PRICEHUBBLE_TIMEOUT=10
VALUATION_FORMULA_ENABLED=true
```

## Somantic

`POST https://www.somantic.net/api/estimate`, Authentifizierung mit `X-API-Key`. Übertragen werden nur Objektdaten: Haus/Wohnung, Straße und Hausnummer, PLZ, Ort, Wohnfläche und optional Zimmer/Baujahr. Keine Namen, E-Mail-Adressen oder Telefonnummern. Grundlage: https://www.somantic.net/developers/docs.

`estimates.price` wird als Schätzwert übernommen, `estimates.price_range.low/high` als Spanne. Die echte Anbieterspanne wird nicht durch die bisherige Prozentformel ersetzt. Fehlt die Spanne vollständig, wird sie aus dem Landingpage-Prozentsatz berechnet und als solche gekennzeichnet. Einseitige, negative, invertierte oder nicht plausible Spannen führen zum nächsten Anbieter. Authentifizierungsfehler, HTTP 429, Serverfehler und Timeouts führen ohne automatische Wiederholung zum Fallback.

## PriceHubble

Der bestehende direkte Adapter bleibt erhalten und nutzt `PRICEHUBBLE_BASE_URL` und `PRICEHUBBLE_API_KEY`. Ohne diese beiden Werte wird er ausdrücklich als `not_configured` übersprungen. Der bisherige Vertrag `POST /valuations` mit Bearer-Token bleibt bestehen; er ist noch nicht gegen einen echten PriceHubble-Zugang geprüft. Die interne PriceHubble-Bewertung in onOffice ist **nicht** dieser direkte API-Zugang. Ein automatisches Auslösen und Abholen dieser internen Bewertung ist nicht Bestandteil dieses Updates. Es wird dafür kein neuer Zugang gebucht und keine Browser-Automatisierung verwendet.

## Formel

Die bisherige Rechenformel wurde unverändert in einen eigenen Anbieter ausgelagert: PLZ-Präfix-Faktor × Wohnfläche, ggf. Grundstück × 150 EUR, Zuschläge für Baujahr nach 2000 und mindestens vier Zimmer, Rundung auf Tausender. Dies ist eine grobe regelbasierte Orientierung, keine datenbasierte Marktwertermittlung. Sie wird jetzt korrekt als `formula` gespeichert und in Website, PDF, E-Mails und Objektbeschreibung als „Formelbasierte Orientierung“ bezeichnet. Die alte Kennzeichnung als Fake-PriceHubble entfällt für neue Bewertungen.

## Herkunft, Protokolle und onOffice

Die vorhandene Spalte `valuations.provider` speichert den tatsächlich ausgewählten Anbieter. In `provider_response` stehen `range_source` und die sicheren Versuchsergebnisse. Rohantworten, Schlüssel und Kontaktangaben werden nicht in diese Metadaten oder die neuen Anbieter-Logs geschrieben. `range_percent` bleibt als konfigurierter Ersatzprozentsatz gespeichert; bei `range_source=provider` ist er nicht die tatsächliche Spannweite.

Die bestehende onOffice-Anlage und der PDF-Versand bleiben erhalten. Alle gültigen Schätzungen stehen mit Quelle in der Objektbeschreibung. Standardmäßig schreibt nur `pricehubble` in die konfigurierten numerischen Preisfelder. Somantic- und Formelwerte werden nicht als PriceHubble-Ergebnis in `MPPricehubble*` eingetragen. Für neutrale eigene onOffice-Bewertungsfelder können die bekannten `ONOFFICE_ESTATE_VALUE_FIELD`, `ONOFFICE_ESTATE_MIN_FIELD` und `ONOFFICE_ESTATE_MAX_FIELD` passend zugeordnet und `onoffice_price_fields_providers` erweitert werden.

Wenn alle konfigurierten Anbieter scheitern oder deaktiviert sind, wird die Bewertung `failed`, ohne erfundene Nullwerte. Kontaktanlage und Benachrichtigungen bleiben möglich. Bestehende Bewertungen werden nicht neu berechnet.
