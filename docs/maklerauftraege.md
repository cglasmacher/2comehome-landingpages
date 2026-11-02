# Makleraufträge

## Installation

Nach dem Pull:

```sh
php artisan migrate --force
npm install --ignore-scripts
npm run build
php artisan optimize:clear
```

PHP benötigt GD (PNG-Unterschriften), die bereits für die Bildverarbeitung der PDF-Ausgabe verwendet werden kann. `APP_KEY` muss gesetzt sein. `APP_URL`, `LANDINGPAGE_PUBLIC_DOMAIN` und HTTPS müssen zur öffentlichen Adresse passen, da persönliche Unterschriftslinks kryptografisch signierte absolute URLs sind. Private Dateien liegen auf dem `local`-Datenträger; dafür ist kein öffentlicher Storage-Link nötig. Datenbank und `storage/app/private` gemeinsam sichern.

## Bedienung

1. Jeder Bearbeiter lädt unter **Meine Unterschrift** seine eigene PNG-/JPG-Datei hoch.
2. Beim Lead unter **Makleraufträge → Maklerauftrag erstellen** Auftraggeber und Konditionen ergänzen. Die Wohnanschrift des Verkäufers wird bewusst nicht aus der Objektadresse geraten.
3. Entwurf speichern und PDF-Vorschau prüfen. Standard: 3,57 % inkl. MwSt., sechs Monate Laufzeit, ein Monat Kündigungsfrist zum Monatsende, monatliche Verlängerung. Die Parameter sind je Auftrag editierbar. Bei manuellem Enddatum ist dieses maßgeblich; Beginn-/Laufzeitänderungen berechnen das Ende erneut.
4. Vertrag fertigstellen. Dazu sind Angebotspreis und Bearbeiterunterschrift erforderlich. Vertragspartner bleibt immer **2 COME HOME Immobilien - Maximilian Schäfer**, unabhängig vom Bearbeiter.
5. Persönlichen Link je Verkäufer kopieren und weitergeben. Die Anwendung verschickt diese Links nicht automatisch. Links gelten standardmäßig 30 Tage und können im Auftrag gemeinsam gesperrt werden. Ein neuer Entwurf bzw. eine neue Fassung wird über **Neue Fassung** angelegt. Alte Links bei Bedarf ausdrücklich sperren; neue Fassungen sperren bestehende Links nicht automatisch.
6. Verkäufer öffnen den Vertrag und unterschreiben mit Finger, Stift oder Maus. AGB und Widerrufsbelehrung sind Teil des PDFs. Zustimmung zum vorzeitigen Tätigkeitsbeginn ist optional, getrennt von der erforderlichen Vertragsbestätigung. Wenn der vorzeitige Beginn gewünscht wird, ist die zusätzliche Kenntnisbestätigung erforderlich.
7. Das aktuelle PDF und die Originalfassung sind im Backend verfügbar. Im Kundenlink kann der Verkäufer das PDF öffnen und im PDF-Betrachter speichern. Bei mehreren Verkäufern ist der Vertrag erst nach allen Unterschriften vollständig unterzeichnet.

Die abschließende Wertermittlung bekommt eine zusätzliche CTA-Seite, wenn ein fertiggestellter, noch nicht vollständig unterschriebener Auftrag mit aktiven Links vorhanden ist. Sie verwendet den neuesten passenden Auftrag und enthält bei genau einem Verkäufer seinen namentlich bezeichneten Link. Bei mehreren Verkäufern verweist sie auf die separate Zustellung persönlicher Links, damit das gemeinsame PDF keine Links anderer Verkäufer offenlegt. Die Ersteinschätzung bleibt unverändert.

## Dokumente und Vorlagen

`config/brokerage.php` enthält die Standards und die Vorlagenversion. `resources/contracts/sale-terms.json` enthält die aus der bereitgestellten Word-Vorlage übernommenen Pflichten, AGB und Widerrufsbelehrung, Stand 01.07.2020. Die dynamischen Vertragsklauseln und das Layout stehen in `resources/views/pdf/brokerage-contract.blade.php`. Die Übernahme ist keine rechtliche Aktualisierung der Vertragstexte.

Beim Fertigstellen werden Daten, Vertragspartner, Bearbeitername, Signaturbild, Logo, Textbestand und Vorlagenversion in einem Snapshot festgehalten. Das ausgegebene PDF wird privat gespeichert und mit SHA-256 gehasht. Änderungen an Lead, Profil oder Standards ändern diesen Bestand nicht. Nach jeder Verkäuferunterschrift entsteht eine weitere PDF-Datei; das ursprüngliche PDF und Zwischenfassungen werden nicht überschrieben. Das Unterzeichnungsprotokoll dokumentiert Name, Ort, Zeit, Bestätigungen und Hash der Originalfassung. Ein gezeichnetes Signaturbild mit Protokoll wird erfasst; eine qualifizierte elektronische Signatur oder externe Identitätsprüfung wird nicht durchgeführt.

Fertiggestellte Aufträge sind über die Anwendung unveränderlich. Dies ist kein externes revisionssicheres Archiv. Datenbankänderungen durch Administratoren außerhalb der Anwendung werden hierdurch nicht verhindert.
