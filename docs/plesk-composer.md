# Plesk: reproduzierbare Composer-Installation

Nach jeder Git-Bereitstellung im Projektverzeichnis ausführen:

```sh
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php artisan config:cache
```

Diese beiden Schritte können über `sh deploy/plesk.sh` als zusätzliche Bereitstellungsaktion in Plesk hinterlegt werden. Das Skript wechselt selbst ins Projektverzeichnis und bricht bei Fehlern ab. Es führt weder Migrationen noch Frontend-Builds aus.

PHP und Composer müssen für den Deployment-Benutzer erreichbar sein. Die CLI muss die passende PHP-Version verwenden; der bestehende Lockfile enthält Symfony-8-Pakete und benötigt PHP 8.4 oder neuer. Falls Plesk eine andere CLI-Version verwendet, die PATH-Einstellung für den Deployment-Prozess auf die passende Plesk-PHP-Version setzen. Der hier verwendete `composer`-Befehl muss dieselbe PHP-Version benutzen.

`composer.lock` wird eingecheckt. `composer install` installiert die dort festgelegten Versionen inklusive Dompdf; `composer update` gehört nicht in die regelmäßige Serverbereitstellung.

Der aktuelle Repository-Stand enthält auch `vendor/`. Dieser Pull Request entfernt das Verzeichnis nicht, damit eine bestehende Bereitstellung nicht plötzlich alle Bibliotheken verliert. Gerade deshalb muss die Composer-Installation nach dem Kopieren der Git-Dateien laufen, damit Paketbestand und Autoloader wieder dem Lockfile entsprechen. Eine spätere Entfernung von `vendor/` aus Git sollte erst nach Einrichtung der automatischen Installation erfolgen.

Validiert: Composer validate --strict und sauberer Produktions-Installationsplan (install --dry-run --no-dev), PHP 8.4.10. Kein vollständiger Paketdownload oder Deployment auf Plesk durch diesen Fix.
