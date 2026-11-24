# Bewertungsanschreiben: 413 / PostTooLargeException bei Foto-Uploads

## Ursache

Die Seite `/admin/leads/{lead}/anschreiben` übermittelt Textfelder und bis zu
vier JPG/PNG-Bilder per `multipart/form-data`. Der PHP-Middleware-Check
`ValidatePostSize` vergleicht die **gesamte** Größe des POST-Requests mit
`post_max_size`; die bisherige Laravel-Fotovalidierung begrenzt dagegen
**einzelne** Bilder (8 MB). Vier Bilder können gemeinsam das PHP-Limit
überschreiten, bevor der Controller erreicht wird.

## Korrektur in der Anwendung

`prepareValuationLetterPhoto.js` verkleinert alle ausgewählten Bilder lokal
im Browser auf höchstens 320 KiB pro Datei, konvertiert sie in JPG und belässt
die Originaldatei auf dem Gerät unverändert. Erst danach lässt sich das
Anschreiben speichern. Fehler werden je Fotofeld angezeigt.

## Plesk-Einstellungen (Domain: verkauf.2comehome.de)

In **Websites & Domains → PHP-Einstellungen** des tatsächlich genutzten
PHP-Handlers prüfen / setzen:

- `upload_max_filesize = 16M`
- `post_max_size = 48M`
- `memory_limit = 256M`
- `file_uploads = On`

Danach PHP-Einstellungen speichern. Das PHP-CLI-`php -i` spiegelt nicht
unbedingt die PHP-FPM-Einstellungen der Website wider.

Wenn die neue Fehlermeldung unmittelbar von **nginx** statt von Laravel
stammt, dessen `client_max_body_size` und Plesk-Reverse-Proxy-Konfiguration
zusätzlich prüfen. Die ursprüngliche Exception in `ValidatePostSize`
bestätigt dagegen, dass der Request den PHP/Laravel-Stack bereits erreicht
hat.

## Deployment

```bash
git pull origin main
npm run build
```

Keine Datenbankmigration und keine Composer-Änderung nötig.

## Prüfung

1. Browser hart neu laden (alte JS-Bundles vermeiden).
2. Vier JPG- oder PNG-Fotos wählen und auf jeweils angezeigte KB-Größe achten.
3. „Anschreiben speichern“ klicken und prüfen, dass nach dem Speichern
   alle vier Bildpositionen verfügbar sind.
4. PDF-Vorschau öffnen.

Neue Frontend-Komprimierung reduziert die Upload-Größe; serverseitige Limits
sollten dennoch korrekt dimensioniert bleiben.
