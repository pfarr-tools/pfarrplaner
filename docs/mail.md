# Ausgehende E-Mail

Pfarrplaner verwendet in der Entwicklung ausschließlich Mailpit. Die
Produktionsumgebung kann zusätzlich einen send-only-Postfix-Container aus dem
Compose-Profil `mail` verwenden. Dieser Container nimmt SMTP nur auf dem
privaten Compose-Netzwerk von Pfarrplaner an und veröffentlicht keinen Port auf
dem Docker-Host. Der bestehende Postfix-Dienst des Hosts bleibt unabhängig und
wird weder verändert noch migriert.

## Voraussetzungen und Grenzen

Vor der Aktivierung muss der Betreiber entscheiden, ob die Instanz direkt an
die MX-Server der Empfänger zustellt oder einen authentifizierten SMTP-Relay
verwendet. Direkte Zustellung setzt insbesondere voraus:

- ausgehende TCP-Verbindungen auf Port 25
- einen stabilen öffentlichen Hostnamen für den Mailserver
- passenden A- beziehungsweise AAAA- und PTR/rDNS-Eintrag
- SPF für die öffentliche Versand-IP
- einen DKIM-Schlüssel und den zugehörigen DNS-TXT-Eintrag
- eine passende DMARC-Richtlinie

Die Zustellung selbst wird nicht durch Docker oder die Anwendung garantiert.
Eine direkte Zustellung kann wegen fehlendem rDNS, blockiertem Port 25 oder
fehlender Reputation abgelehnt werden. Ein professioneller SMTP-Relay ist in
solchen Umgebungen der empfohlene Weg.

Der Dienst verarbeitet keine eingehenden Nachrichten und stellt keine
Mailboxen, IMAP- oder POP3-Schnittstelle bereit. Automatische
Bounce-Verarbeitung ist deshalb nicht Bestandteil dieser Integration. Verwenden
Sie einen überwachten Envelope-Sender oder die Bounce-Funktionen des
Upstream-Providers.

## Konfiguration

Die echten Werte stehen ausschließlich in der nicht versionierten `.env` und
unter `secrets/`. Die Vorlage liegt in `.env.example`.

Mindestens erforderlich sind:

```dotenv
MAIL_ALLOWED_SENDER_DOMAINS=example.org
MAIL_HOSTNAME=mail.example.org
MAIL_FROM_ADDRESS=noreply@example.org
MAIL_FROM_NAME=Pfarrplaner
```

`MAIL_HOSTNAME` ist der öffentliche Hostname des ausgehenden Mailservers. Die
Absenderadresse muss zu einer in `MAIL_ALLOWED_SENDER_DOMAINS` erlaubten Domain
gehören. Das Image verweigert nicht erlaubte Absender und ist nur aus dem
privaten Mail-Netzwerk erreichbar.

### Direkte MX-Zustellung

Für direkte Zustellung bleiben diese Werte leer:

```dotenv
MAIL_RELAY_HOST=
MAIL_RELAY_USERNAME=
MAIL_RELAY_TLS_LEVEL=may
```

Der Postfix-Container sucht die MX-Ziele selbst und verbindet sich über Port
25. `MAIL_RELAY_TLS_LEVEL=may` bedeutet, dass TLS verwendet wird, wenn der
Zielserver es anbietet; für direkte Server-zu-Server-Zustellung ist dies nicht
mit dem Submission-TLS der Anwendung zu verwechseln.

### Authentifizierter Upstream-Relay

Für einen Provider oder zentralen Mailserver wird zum Beispiel konfiguriert:

```dotenv
MAIL_RELAY_HOST=[smtp.provider.example]:587
MAIL_RELAY_USERNAME=pfarrplaner@example.org
MAIL_RELAY_TLS_LEVEL=encrypt
```

Das Passwort wird nicht in Git und möglichst nicht als Klartext-Variable
hinterlegt. Legen Sie es als Datei unter
`secrets/mail-relay-password` ab und schützen Sie sie:

```sh
mkdir -p secrets/dkim
umask 077
printf '%s\n' 'GEHEIMES-RELAY-PASSWORT' > secrets/mail-relay-password
```

Die Compose-Konfiguration reicht diese Datei nur als read-only Secret an den
Postfix-Container weiter. Passwörter dürfen nicht in Support-Ausgaben,
Compose-Logs oder Tickets kopiert werden.

### DKIM

Für produktive direkte Zustellung werden eigene DKIM-Schlüssel empfohlen. Das
Image unterstützt die Schlüssel unter `secrets/dkim` und den Selector aus
`MAIL_DKIM_SELECTOR` (Standard: `mail`). Für jede Versanddomain muss dort die
vom Image erwartete Schlüsseldatei liegen. Der öffentliche Teil wird als
`<selector>._domainkey.<domain>` im DNS veröffentlicht; prüfen Sie die genaue
Dateibenennung gegen die verwendete Image-Version.

Die privaten Schlüssel liegen außerhalb von Git unter `secrets/dkim`; sie
gehören in den betrieblichen Backup- und Wiederherstellungsprozess. Ein Backup
der Laravel-Anwendungsdaten allein enthält diese Schlüssel und das Postfix-
Queue-Volume nicht.

## Start und Prüfung

Mailpit in `compose.yaml` bleibt unverändert und wird für lokale Tests wie
bisher gestartet. Produktions-Postfix wird ausdrücklich mit seinem Profil
gestartet:

```sh
docker compose -f compose.production.yaml --profile mail up -d postfix
docker compose -f compose.production.yaml ps postfix
docker compose -f compose.production.yaml logs --tail=100 postfix
```

Danach werden App und Horizon mit derselben Produktions-Compose-Datei gestartet
beziehungsweise aktualisiert:

```sh
./planer prod up -d app horizon scheduler web
./planer prod exec app php artisan queue:failed
```

Der App- und Horizon-Container verwenden intern `postfix:587`. Diese Verbindung
liegt nur im privaten `mail`-Netzwerk und verwendet dort bewusst keine externe
SMTP-Authentifizierung. Das Mail-Netzwerk ist von anderen Containern getrennt;
Postfix besitzt zusätzlich ein nicht veröffentlichtes Egress-Netzwerk für die
Verbindung zu MX- oder Relay-Servern.

Für einen kontrollierten Test verwenden Sie die vorhandene Testmail-Funktion
mit einer ausdrücklich freigegebenen Testadresse. Prüfen Sie anschließend
`queue:failed`, die Horizon-Oberfläche, die Postfix-Logs und – bei direkter
Zustellung – die empfangenen SPF-, DKIM- und DMARC-Header.

## Queue und Fehlerbehebung

Mails werden aus HTTP-Anfragen heraus in die Redis-Warteschlange eingestellt.
Horizon ist der dedizierte Queue-Worker und verwendet dieselbe Anwendung, `.env`
und Redis-Verbindung wie der App-Container. Fehlgeschlagene Laravel-Jobs
bleiben in `failed_jobs` sichtbar:

```sh
./planer prod exec app php artisan queue:failed
./planer prod exec app php artisan queue:retry all
docker compose -f compose.production.yaml logs --tail=200 horizon postfix
```

Ein vorübergehend nicht erreichbares MX-Ziel verbleibt in der persistenten
Postfix-Warteschlange und wird von Postfix erneut versucht. Die Queue liegt im
benannten Volume `postfix_queue` und darf bei normalen Neustarts nicht mit
`docker compose down -v` gelöscht werden. Das separate Volume
Die DKIM-Schlüssel liegen unter `secrets/dkim` und werden separat gesichert.

Bei Zustellproblemen prüfen Sie in dieser Reihenfolge:

1. `postfix` ist im Profil `mail` gestartet und gesund.
2. `MAIL_ALLOWED_SENDER_DOMAINS`, `MAIL_HOSTNAME` und `MAIL_FROM_ADDRESS`
   passen zusammen.
3. Bei Relay-Betrieb sind Host, TLS-Level, Benutzername und Secret korrekt.
4. Bei direkter Zustellung sind DNS, PTR/rDNS und ausgehender Port 25 korrekt.
5. Horizon läuft und `queue:failed` enthält keine blockierten Mailjobs.
6. `docker compose ... logs postfix` zeigt die Zustell- oder TLS-Ursache.

## Sicherheit und Koexistenz mit Host-Postfix

Es gibt keinen `ports:`-Eintrag für Postfix. Der Docker-Host erhält deshalb
keinen zusätzlichen öffentlichen SMTP-Listener, und ein vorhandener Host-
Postfix bleibt auf seinen bisherigen Ports und mit seiner bisherigen Queue
unberührt. Niemals die Host-Queue in den Container kopieren, leeren oder
übernehmen.

Das Image wird mit dem getesteten Release-Tag `v5.1.0` verwendet. Vor einem
Versionswechsel müssen die dokumentierten Environment-Variablen, DKIM-Backend-
Änderungen und Volume-Anforderungen der neuen Version geprüft werden.

## Rollback

Der Rollback betrifft ausschließlich Pfarrplaner:

1. App und Horizon anhalten oder quieszieren.
2. Die produktive Mail-Konfiguration auf den bisherigen SMTP-Dienst
   zurücksetzen.
3. Laravel-Konfiguration leeren und App/Horizon neu starten.
4. Den Postfix-Container erst nach einer Prüfung der verbleibenden Queue
   stoppen; die Volumes behalten, bis die Zustellung abschließend bewertet ist.

Der Host-Postfix, seine Konfiguration und seine Queue werden bei diesem
Rollback nicht angefasst.
