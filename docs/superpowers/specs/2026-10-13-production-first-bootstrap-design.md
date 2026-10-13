# Production-first CLI und Bootstrap

## Ziel

Ein frischer Produktionscheckout soll nach

```sh
./planer bootstrap
./planer up -d
```

betriebsbereit sein. Der Bootstrap richtet die anwendungsspezifische
Konfiguration, optionale Backups und optionalen ausgehenden Mailversand ein.
Entwicklungsbetrieb ist ausdrücklich über `./planer dev ...` erreichbar.

Die Änderung ist bewusst nicht abwärtskompatibel für bisherige unqualifizierte
Entwicklungsbefehle. Der Betreiber dieses Repositories ist der einzige Nutzer.

## CLI-Modell

Produktionsbefehle sind die unqualifizierten Befehle:

```text
./planer up|down|restart|status|logs|exec
./planer artisan ...
./planer backup ...
./planer manual ...
./planer migrate ...
```

Sie verwenden `compose.production.yaml`.

Entwicklungsbefehle stehen ausschließlich unter `dev`:

```text
./planer dev up|down|restart|status|logs|exec
./planer dev artisan ...
./planer dev test
./planer dev composer ...
./planer dev npm ...
./planer dev pint ...
./planer dev fresh --force
```

Sie verwenden `compose.yaml`. `prod` beziehungsweise `production` bleiben als
explizite Produktionsaliases erhalten. Alte unqualifizierte Entwicklungsaliases
werden nicht erhalten.

`./planer bootstrap` erzeugt eine Produktionskonfiguration. `--dev` erzeugt
eine Entwicklungskonfiguration. `--prod` bleibt vorübergehend als Alias für
den Produktionsmodus bestehen.

## Bootstrap-Grundkonfiguration

Im Produktionsmodus werden ohne Nachfrage gesetzt:

- `COMPOSE_PROJECT_NAME=pfarrplaner`
- `APP_NAME=Pfarrplaner`
- `APP_ENV=production`
- `APP_DEBUG=false`
- `OCTANE_HTTPS=false`
- zufällige Werte für alle bestehenden Anwendungsschlüssel

Interaktiv werden mindestens abgefragt:

- öffentliche `APP_URL`
- `APP_PROVIDER`, beispielsweise `Kirchenbezirk Herrenberg`
- `APP_ADMINISTRATOR`
- der lokal gebundene HTTP-Port für den externen Reverse Proxy

Wenn keine Daten übernommen werden, werden zusätzlich Vorname, Nachname und
Passwort des ersten Super-Administrators abgefragt. Das Passwort wird ohne Echo
zweimal eingegeben, nur gehasht in der Datenbank gespeichert und niemals in
`.env`, Logs oder der Bootstrap-Ausgabe abgelegt. Der Benutzer erhält die
Rolle `Super-Administrator:in`; ein Passwortwechsel beim ersten Login wird
standardmäßig erzwungen.

Die Eingabe von Geheimnissen erfolgt ohne Echo. Zufällige Secrets werden nicht
ausgegeben. `.env` wird weiterhin niemals überschrieben.

## Backup-Assistent

Der Produktionsbootstrap fragt, ob Backups eingerichtet werden sollen. Bei
„Nein“ wird kein `backup.yaml` erstellt; der normale Stack bleibt startbar,
`./planer backup ...` meldet dann ausdrücklich, dass Backups nicht konfiguriert
sind.

Bei „Ja“ können beliebig viele Ziele angelegt werden. Für jedes Ziel werden
Name, Typ (`local` oder `sftp`), Pflichtstatus und die erforderlichen Pfade
beziehungsweise Verbindungsdaten abgefragt. Für jedes aktivierte Ziel wird
ein zufälliges, nicht ausgegebenes Restic-Passwort erzeugt. SFTP-Schlüssel und
`known_hosts` werden nicht kopiert oder erzeugt; der Bootstrap prüft die
angegebenen Dateien und ihre restriktiven Berechtigungen.

Der Bootstrap erzeugt eine gültige `backup.yaml` mit:

- Produktions-Compose-Dateien und Produktionsdiensten
- den aktuellen Datenbank- und MinIO-Werten aus `.env`
- den konfigurierten Restic-Repositories
- Standardaufbewahrung und Verifikation
- `policy.require_offsite=true`, sobald ein erforderliches SFTP-Ziel angelegt
  wurde

Die Datei erhält `0600`. Anschließend werden die benötigten lokalen Verzeichnisse
angelegt und jedes aktivierte Repository mit `./planer backup init` initialisiert.
Fehler bei einem erforderlichen Ziel brechen den Bootstrap mit einer klaren
Fehlermeldung ab; optionale Ziele werden als Warnung angezeigt.

## Mail-Assistent

Der Produktionsbootstrap fragt nach dem Mailmodus:

1. ausgehender SMTP-Server ohne Docker-Postfix,
2. interner send-only-Postfix-Container,
3. Mailversand zunächst deaktivieren.

Für externen SMTP werden Host, Port, Verschlüsselung, Benutzer, Passwort,
Absenderadresse und Anzeigename abgefragt. Das Passwort wird nicht auf der
Kommandozeile ausgegeben.

Für Postfix werden zusätzlich direkte MX-Zustellung oder authentifiziertes
Relay, erlaubte Absenderdomain, öffentlicher Mailhostname, DKIM-Selector sowie
gegebenenfalls Relaydaten abgefragt. Der private DKIM-Schlüssel wird unter
`secrets/dkim` erzeugt, mit restriktiven Rechten versehen und nicht versioniert.
Der öffentliche DNS-TXT-Eintrag wird am Ende ausgegeben.

Die Compose-Konfiguration erhält keine fest verdrahtete `postfix`-Verbindung
mehr. App und Worker verwenden die im `.env` konfigurierte Mailverbindung.
Wenn Postfix aktiviert ist, setzt der Wrapper beim Produktionsstart automatisch
das Compose-Profil `mail`; andernfalls wird der Dienst nicht gestartet.

Für den Postfix-Modus werden `MAIL_HOST=postfix`, Port 587 und die bereits
erforderliche interne Einstellung `MAIL_AUTO_TLS=false` gesetzt. Für externe
SMTP-Konfigurationen bleiben Host, Port und TLS-Einstellungen frei
konfigurierbar.

## Optionaler Datenimport

Nach der Konfiguration fragt der Produktionsbootstrap, ob die neue Instanz leer
gestartet oder aus einer bestehenden Installation befüllt werden soll:

1. keine Daten übernehmen,
2. ein vorhandenes Restic-Backup wiederherstellen,
3. eine Legacy-Installation migrieren.

Die Option ist nur im Produktionsbootstrap verfügbar. Für einen Import wird
der Compose-Stack zunächst mit den erzeugten Produktionswerten gestartet,
damit Datenbank, Redis und MinIO erreichbar sind. Erst danach wird der Import
ausgeführt; bei einem Fehler werden die Writer-Dienste beendet und der Fehler
sichtbar gemeldet. Ein erfolgreicher Import endet mit einem erneuten
Konfigurations- und Gesundheitscheck.

Beim Backup-Import werden Backup-Ziel und Backup-ID abgefragt. Es wird der
vorhandene sichere Restore-/Apply-Pfad verwendet; ein Anwenden auf die neue
Instanz benötigt eine explizite Bestätigung. Die Ziel-`.env` und alle erzeugten
Schlüssel bleiben erhalten, soweit der bestehende Restore-Vertrag dies vorsieht.

Bei einer Legacy-Migration werden lokaler absoluter Pfad und
`user@host:/absoluter/pfad` als getrennte Eingabeformen akzeptiert. Ein lokaler
Pfad verwendet kein SSH. Der Ablauf führt zuerst einen Dry-Run aus und verlangt
danach eine ausdrückliche Bestätigung. Für die Produktionsmigration werden die
Legacy-Identitätsschlüssel nach dem bestehenden Migrationsvertrag übernommen;
sie werden niemals ausgegeben. Die alte Installation bleibt unverändert.

Die Auswahl „keine Daten übernehmen“ darf weder eine Migration noch einen
Restore ausführen. Die Importauswahl und ihre Parameter werden nicht in
versionierten Dateien oder Logs mit Geheimnissen gespeichert.

Bei „keine Daten übernehmen“ werden nach Migrationen und dem Rollen-Seeding
die erforderlichen initialen Rollen angelegt und genau ein idempotenter erster
Super-Administrator erzeugt. Bei Backup-Restore oder Legacy-Migration wird kein
zusätzlicher Benutzer angelegt, weil die Benutzerdaten aus der Quelle stammen.

## Abschlussprüfung

Vor dem erfolgreichen Ende prüft der Bootstrap:

- `.env` enthält alle Pflichtwerte und keine Platzhalter
- Produktions-Compose lässt sich mit `docker compose config` auflösen
- `backup.yaml` ist, falls vorhanden, syntaktisch gültig und `0600`
- aktivierte SFTP-Dateien sind vorhanden und restriktiv lesbar
- Postfix-DKIM-Dateien sind, falls aktiviert, vorhanden
- `./planer backup init` war für alle erforderlichen Ziele erfolgreich
- ein gewählter Import wurde mit Zielumgebung `production` abgeschlossen

Die Ausgabe endet mit:

```text
Bootstrap abgeschlossen. Starte jetzt mit:
  ./planer up -d
```

Bei nicht erreichbaren externen Systemen darf der Bootstrap keine scheinbar
erfolgreiche Konfiguration hinterlassen; lokale Konfigurationsdateien bleiben
jedoch zur Korrektur erhalten.

## Dokumentation und Tests

Die CLI-Dokumentation, das Installationshandbuch, das Mailhandbuch und die
Administratorenhandbücher werden auf das production-first Modell aktualisiert.

Fokussierte Tests decken ab:

- Produktions- und Entwicklungsrouting
- feste Projektbezeichnung und `APP_PROVIDER`
- Produktionsdefaults und zufällige Secrets
- kein `backup.yaml` beim Überspringen
- mehrere lokale/SFTP-Backupziele und `0600`
- Mailmodi und automatisches Postfix-Profil
- kein fest verdrahtetes `postfix` bei externem SMTP
- DKIM-Schlüsselerzeugung ohne Ausgabe des privaten Schlüssels
- initialer Super-Administrator nur bei leerem Start

Zusätzlich laufen `git diff --check`, Shell-Syntaxprüfungen und die bestehenden
Compose-, Backup- und Mail-Strategietests.
