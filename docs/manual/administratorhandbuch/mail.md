[//]: # (TOC: 7. Mailbetrieb)

# Mailbetrieb

Pfarrplaner stellt Mails in der Produktion asynchron über Redis und Horizon in
die Zustellwarteschlange. Die eigentliche SMTP-Zustellung übernimmt im
Docker-Betrieb der optionale, interne Postfix-Dienst. In der Entwicklung bleibt
Mailpit der lokale Testdienst.

## Produktionsmail aktivieren

Der Produktionsdienst wird nur mit dem Compose-Profil `mail` gestartet:

```sh
./planer prod up -d app horizon scheduler web
docker compose -f compose.production.yaml --profile mail up -d postfix
./planer prod exec app php artisan queue:failed
```

Postfix veröffentlicht keinen Host-Port. App und Horizon erreichen ihn nur als
`postfix:587` im privaten Mail-Netzwerk. Der Host-Postfix bleibt unabhängig.

## Erforderliche Konfiguration

Die Werte stehen ausschließlich in der nicht versionierten `.env`:

```dotenv
MAIL_ALLOWED_SENDER_DOMAINS=example.org
MAIL_HOSTNAME=mail.example.org
MAIL_FROM_ADDRESS=noreply@example.org
MAIL_FROM_NAME=Pfarrplaner
```

Für direkte MX-Zustellung bleiben Relay-Adresse und Relay-Benutzer leer. Bei
einem authentifizierten Relay werden Host, Benutzername und TLS-Stufe gesetzt;
das Passwort liegt ausschließlich in `secrets/mail-relay-password`.

Direkte Zustellung benötigt ausgehenden TCP-Port 25, passende A-/AAAA- und
PTR-/rDNS-Einträge sowie SPF, DKIM und DMARC. Ein SMTP-Relay ist in vielen
Umgebungen zuverlässiger.

## DKIM und Backups

DKIM-Schlüssel liegen unter `secrets/dkim` und dürfen nicht in Git gelangen.
Sie gehören in den betrieblichen Backup- und Wiederherstellungsprozess. Die
Postfix-Zustellwarteschlange liegt im benannten Volume `postfix_queue`; bei
normalen Neustarts darf sie nicht mit `docker compose down -v` gelöscht werden.

## Prüfung und Fehlersuche

```sh
./planer prod ps
./planer prod logs --tail=200 postfix
./planer prod logs --tail=200 horizon
./planer prod exec app php artisan queue:failed
./planer prod exec app php artisan queue:retry all
```

Prüfen Sie zusätzlich die freigegebene Absenderdomain, `MAIL_HOSTNAME`, DNS,
Relay-Zugangsdaten und die SPF-, DKIM- und DMARC-Header einer Testmail.

Mails werden in Laravel über Horizon verarbeitet. Ein vorübergehend nicht
erreichbares MX-Ziel verbleibt anschließend in der persistenten Postfix-Queue.
Fehlgeschlagene Laravel-Jobs bleiben in `failed_jobs` sichtbar.

## Rollback

Bei einer Umstellung wird nur die Pfarrplaner-Mailkonfiguration auf den
vorherigen SMTP-Dienst zurückgesetzt. Postfix und seine Queue werden erst nach
Prüfung der noch ausstehenden Zustellungen beendet. Die Konfiguration und Queue
des Host-Postfix werden nicht verändert.
