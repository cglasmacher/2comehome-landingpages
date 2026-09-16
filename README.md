<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## onOffice: Immobilienanlage und Diagnose

Neue Immobilien werden mit `status=2` (inaktiv), dem mandantenspezifischen
Status-2-Schluessel fuer **In Akquise** und dem Benutzer **CG** angelegt.
`CG` wird ueber `user.Kuerzel` eindeutig auf eine numerische ID aufgeloest.
Der API-Benutzer braucht hierfuer das Recht **Benutzerdaten ueber API auslesen**.
Alternativ `ONOFFICE_ESTATE_USER_ID` mit der verifizierten numerischen ID von CG
setzen; dann entfaellt der Benutzer-Leseaufruf. Keine Beispiel-ID uebernehmen.

Nach dem Deployment (mit der PHP-Version des Projekts, mindestens 8.3):

```bash
php artisan migrate --force
php artisan config:cache
php artisan onoffice:diagnose
```

Die Diagnose liest nur Feldkonfiguration und Benutzer, erneuert deren lokalen
Cache und schreibt das Ergebnis in die Konsole und das konfigurierte Laravel-Log
(standardmaessig `storage/logs/laravel.log`). Sie legt keine Datensaetze an.
Bei fehlender/eindeutig nicht aufloesbarer Konfiguration endet sie mit Exit-Code 1.
Ein Erfolg bestaetigt die Konfiguration, nicht die Schreibrechte oder eine echte Anlage.

Optionale `.env`-Einstellungen:

```dotenv
ONOFFICE_DEBUG=true
ONOFFICE_ESTATE_STATUS2_LABEL="In Akquise"
ONOFFICE_ESTATE_USER_INITIALS=CG
ONOFFICE_ESTATE_USER_ID=
```

API-Fehler werden auch bei `ONOFFICE_DEBUG=false` protokolliert, einschliesslich
HTTP-Status, globalem/Aktions-Fehlercode und Request-Identifier. Kontakt- und
Immobilien-IDs sowie der fehlgeschlagene Schritt stehen im Sync-Abschlusslog.
Details der Antworten stehen weiterhin in `lead_sync_logs.response_payload`.
Fehlende Zugangsdaten ergeben einen Fehler, keinen vorgetaeuschten Demo-Erfolg.

Die Formular-Objekttypen werden in `config/landingpages.php` auf `objektart` und
`objekttyp` abgebildet; Laendercodes werden dort auf ISO alpha-3 umgewandelt.
Bei abweichenden mandantenspezifischen Auswahlwerten diese Zuordnung anpassen.

Nach erfolgreicher Diagnose einmal einen neuen Testlead ueber das Formular senden
und Kontakt, Immobilie (inaktiv / In Akquise / CG) sowie Eigentuemer-Verknuepfung
in onOffice kontrollieren. Bestehende Teilerfolge nicht blind erneut senden:
Der Sync hat noch keine Duplikaterkennung. Insbesondere nach einem Timeout kann
ein Datensatz bereits angelegt sein; deshalb erfolgen keine automatischen Retries.

Token/Secret gehoeren ausschliesslich in die Server-Konfiguration. Aus dem
Beispiel wurden vorbelegte Werte entfernt. Falls diese echt waren, muessen sie
in onOffice erneuert werden; alte Git-Commits enthalten weiterhin die alten Werte.

### Fehler 143: Unknown field in estate data

Vor jeder Immobilienanlage werden die gesendeten Feldnamen mit der aktiven
onOffice-Feldkonfiguration abgeglichen. Die Konfiguration wird pro API-Zugang
zwischengespeichert; `php artisan onoffice:diagnose` liest sie frisch ein.
Die Diagnose enthaelt jetzt `estate_fields.unknown_fields` und moegliche interne
Notizfelder samt Beschriftung. Unbekannte Immobilienfelder fuehren zu einem
lokalen Fehler mit konkretem Feldnamen, ohne einen Immobilien-Schreibaufruf.

Ausnahme ist die optionale, automatisch erzeugte Herkunftsnotiz: Ist das bisher
verwendete Feld `interne_Bemerkung` nicht vorhanden, wird die Notiz mit einer
Log-Warnung nur im lokalen Sync-Protokoll behalten. Sie wird nicht in ein
anderes (moeglicherweise oeffentliches) Textfeld umgeleitet. Wenn ein geeignetes
internes Feld verifiziert wurde, kann dessen exakter API-Name mit
`ONOFFICE_ESTATE_NOTE_FIELD` gesetzt werden; danach `php artisan config:cache`.
Status, Bearbeiter und eingegebene Immobiliendaten werden nicht still entfernt.
