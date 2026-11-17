[//]: # (TOC: 10. Sicherheit, Backup und Datenschutz)

# Sicherheit, Backup und Datenschutz

Pfarrplaner verarbeitet personenbezogene Daten. Darum gehören Sicherheit und Wiederherstellbarkeit nicht an den Rand, sondern in den normalen Betriebsalltag.

## Zugangsschutz

- Nur HTTPS nach außen
- starke Passwörter
- möglichst wenige Super-Admin-Konten
- getrennte Konten für normale Nutzung und Administration, wenn sinnvoll
- keine gemeinsamen Dauerpasswörter im Team

## Serverhärtung

- nur nötige Ports öffnen
- SSH absichern
- Sicherheitsupdates des Betriebssystems zeitnah einspielen
- keine unnötigen Werkzeuge auf dem Produktionsserver belassen

## Pfarrplaner-spezifische Härtung

Für Pfarrplaner selbst sollten Sie besonders darauf achten:

- `APP_DEBUG` in Produktion auf `false`
- Entwicklerhilfen wie Boost, Telescope, Ignition-Runnable-Solutions oder Dusk nur gezielt und nie dauerhaft in Produktion aktivieren
- öffentliche Datei- und Bildlinks aus `attachments/` nur über signierte URLs weitergeben
- keine alten Direktlinks zu Logout-, Token-, Patch- oder Benutzerwechsel-Routen weiterverwenden, wenn Integrationen oder Eigenanpassungen existieren
- öffentliche Einbettungen und Feeds regelmäßig darauf prüfen, ob sie wirklich nur die beabsichtigten Daten preisgeben

Ein technischer Überblick über den Sicherheitsdurchgang und die abgesicherten Routen liegt im [Technikhandbuch](../technikhandbuch/sicherheitsaudit.md).

## Zwei-Faktor-Authentifizierung

Benutzer können die TOTP-Einschreibung nur nach erneuter Eingabe ihres
aktuellen Passworts starten, bestätigen oder deaktivieren. Start und
Bestätigung liefern keine dauerhaften Geheimnisse in Benutzerlisten oder
Protokollen; Recovery-Codes werden nur einmalig nach der Einschreibung
angezeigt und müssen sicher außerhalb von Pfarrplaner verwahrt werden.

Falsche Passwörter und Sicherheitscodes werden generisch abgewiesen. Die
Passwortanmeldung verwendet das bestehende Limit aus Benutzername und
Quell-IP. Es gibt absichtlich keine zusätzliche kontoweite Passwortsperre, da
sonst ein Angreifer gezielt ein fremdes Konto vorübergehend blockieren könnte.
TOTP-Prüfungen werden dagegen pro Benutzerkonto atomar begrenzt: Nach fünf
Prüfungen innerhalb einer Minute werden weitere TOTP-Prüfungen für dieses Konto
vorübergehend gesperrt. Auch die Re-Authentifizierung vor jeder TOTP-Änderung
besitzt ein eigenes Limit. Ein Faktorwechsel wird gegen parallele Start-,
Bestätigungs- und Deaktivierungsanfragen serialisiert.

Bei Verlust des Authenticator-Geräts werden die Recovery-Codes benötigt. Wenn
auch das Postfach und alle Recovery-Codes verloren sind, gibt es derzeit keinen
Selbstbedienungsweg; der Betreiber muss die Identität über einen getrennten,
verifizierten Supportprozess prüfen. Ein Datenbank-Restore muss
immer zusammen mit dem vorherigen Anwendungsimage und einem verifizierten
Backup geplant werden; die TOTP-Migration besitzt keinen automatischen
Spalten-Rollback.

Für aktive TOTP-Konten kann die betroffene Person bei der Anmeldung alternativ
einen Einmalcode per E-Mail anfordern. Die Codes sind fünf Minuten gültig,
werden nur gehasht gespeichert und nach erfolgreicher Nutzung entwertet. Der
Mailversand benötigt die reguläre Mailkonfiguration der Instanz. Bei Verlust
des Postfachs und aller Recovery-Codes ist weiterhin eine administrative
Identitätsprüfung erforderlich; Support darf keine Codes oder TOTP-Schlüssel
per Chat oder E-Mail erfragen.

### Wiederherstellung nach Verlust von Gerät und Recovery-Codes

Die Wiederherstellung ist ein eigener Super-Admin-Vorgang. Sie setzt weder das
Benutzerpasswort zurück noch versendet sie ein Ersatzpasswort oder einen
Anmeldecode. Führen Sie sie erst aus, wenn die Identität der anfragenden Person
über einen bereits bekannten, organisationskontrollierten Kontaktweg geprüft
wurde. Eine eingehende E-Mail oder der Zugriff auf das betroffene Postfach
allein ist kein ausreichender Nachweis. Pflegen Sie nur die kurze
Supportfall-Referenz in Pfarrplaner; tragen Sie dort keine Namen,
Identitätsnachweise oder sonstigen personenbezogenen Details ein.

1. Öffnen Sie in der Benutzerverwaltung das Konto der betroffenen Person.
2. Prüfen Sie, dass die Identität außerhalb von Pfarrplaner bestätigt wurde.
3. Wählen Sie **Verlorene Zwei-Faktor-Anmeldung widerrufen**.
4. Geben Sie Ihr eigenes aktuelles Super-Admin-Passwort und die
   Supportfall-Referenz ein und bestätigen Sie den Vorgang.

Die Referenz muss 1 bis 100 Zeichen enthalten. Zulässig sind ASCII-Buchstaben,
Ziffern sowie Punkt, Unterstrich und Bindestrich. Ein gültiges Beispiel ist
`SUP-2026-123`.

Ein Super-Admin kann nicht das eigene Konto wiederherstellen. Ist das Zielkonto
selbst ein Super-Admin-Konto, muss ein anderer Super-Admin den Vorgang
ausführen. Die Anwendung prüft das aktuelle Passwort unmittelbar vor der
Änderung und begrenzt Fehlversuche. Falsche Passwörter, fehlende Faktoren,
fehlende Berechtigungen und nicht unterstützte Session-Konfigurationen
verhindern die Wiederherstellung.

Bei Erfolg werden der aktive oder noch nicht bestätigte TOTP-Faktor, offene
E-Mail-Anmeldecodes, persönliche API-Tokens, den bisherigen API-/Kalendertoken,
Remember-me-Zugang und alle Datenbank-Sitzungen des Zielkontos widerrufen. Das
Passwort und ein eventuell ausstehender Passwortwechsel bleiben bestehen. Nach
der nächsten Passwortanmeldung darf das Konto nur die TOTP-Wiederherstellung,
notwendige Passwortänderung und Logout verwenden. Erst nach erfolgreicher
TOTP-Bestätigung wird der normale Zugriff freigegeben. Fehlgeschlagene oder
abgebrochene TOTP-Bestätigungen lassen die Sperre bestehen. Die Person muss die
bei der Einrichtung gezeigten neuen Recovery-Codes sicher verwahren und
anschließend gespeicherte Kalender- und API-Verbindungen mit dem neuen Token
aktualisieren.

Für diese Funktion muss `SESSION_DRIVER=database` gesetzt sein. Falls
`SESSION_CONNECTION` angegeben ist, muss sie dieselbe Datenbankverbindung wie
die Anwendung verwenden. Mit einem Datei-, Redis- oder anderen Session-Treiber
steht die Recovery-Aktion nicht zur Verfügung. Fehlversuche werden mit
Zeitstempel am Super-Admin-Konto gespeichert. Eine Datenbanksperre umfasst
Passwortprüfung und Zähleränderung; parallele Versuche werden damit über alle
App-Hosts hinweg serialisiert. Dafür ist kein Cache-Treiber vorgeschrieben.
Prüfen Sie vor dem Supportvorgang die wirksame Session-Konfiguration der
Instanz.

Jeder erfolgreiche Vorgang wird dauerhaft mit handelndem Konto, Zielkonto,
Supportfall-Referenz, Zeitpunkt und IP-Adresse protokolliert. Das Protokoll
enthält keine Passwörter, TOTP-Geheimnisse oder Recovery-Codes. Audit-Einträge
werden bei einer regulären Kontolöschung nicht automatisch entfernt; die
historischen numerischen Konto-IDs bleiben erhalten. Nur die lokale
Demo-Anonymisierung entfernt Audit-Einträge zu den anonymisierten Demokonten.
Behandeln Sie Referenz und IP-Adresse als personenbezogene Auditdaten und
beschränken Sie den Zugriff auf die Benutzerverwaltung.

Die Recovery-Migration ist vorwärtsgerichtet. Verwenden Sie für einen
Rollback nicht `migrate:rollback` und entfernen Sie keine Spalten oder Tabellen
manuell. Sichern und verifizieren Sie vor einem Update Datenbank und
Objektspeicher. Bei einem notwendigen Rollback sind das vorherige
Anwendungsimage und die verifizierte Datenbanksicherung nach dem dokumentierten
Restore-Verfahren gemeinsam wiederherzustellen.

## Backup

Ein brauchbares Backup des Docker-Betriebs umfasst mindestens:

- MariaDB-Datenbank
- alle Objekte im MinIO-Bucket
- `backup.yaml`, Restic-Passwörter und SSH-Dateien in `secrets/`, separat und geschützt
- DKIM-Schlüssel unter `secrets/dkim`, wenn das Mail-Profil verwendet wird
- das benannte Postfix-Queue-Volume, wenn eine laufende Zustellwarteschlange erhalten werden muss

`./planer backup create` erstellt den Datenbank- und MinIO-Satz und lädt ihn in
die aktivierten Restic-Repositories. `./planer backup verify` prüft die
Sicherung; `./planer backup restore` schreibt zunächst in ein separates
Verzeichnis. Ein produktiver Restore benötigt zusätzlich `--apply --yes` und
eine vorherige Sicherheitskopie.

Wichtig ist nicht nur das Erstellen, sondern auch das Wiederherstellen. Testen Sie regelmäßig, ob sich aus den Sicherungen wirklich eine lauffähige Instanz herstellen lässt.

## Datenschutz

Prüfen Sie besonders:

- Wer darf welche personenbezogenen Daten sehen?
- Welche öffentlichen Ausgaben enthalten personenbezogene Daten?
- Welche Demo-, Test- oder Schulungsinstanzen enthalten Produktivdaten?
- Wie lange bleiben Protokolle, Uploads und Exporte gespeichert?

## Mail und externe Integrationen

Externe Integrationen sollten sparsam und nachvollziehbar konfiguriert sein. Zugangsdaten gehören nur in geschützte Konfigurationswege, nicht in Tickets, Chats oder unverschlüsselte Notizen.

## Vor größeren Änderungen

Vor Updates, Serverumzügen oder Integrationsarbeiten:

1. Mit `./planer backup verify` ein Backup prüfen.
2. Wartungsfenster festlegen.
3. Rückfallplan festhalten.
4. Verantwortliche informieren.
