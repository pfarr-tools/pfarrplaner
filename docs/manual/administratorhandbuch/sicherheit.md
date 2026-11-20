[//]: # (TOC: 10. Sicherheit, Backup und Datenschutz)

# Sicherheit, Backup und Datenschutz

Pfarrplaner verarbeitet personenbezogene Daten. Darum gehören Sicherheit und Wiederherstellbarkeit nicht an den Rand, sondern in den normalen Betriebsalltag.

## Zugangsschutz

- Nur HTTPS nach außen
- Passkeys und Authenticator-Apps für Benutzerkonten fördern
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

## Passkeys und Authenticator-App

Pfarrplaner authentifiziert Konten per Passkey oder E-Mail-Anmeldelink. Ein zusätzlich bestätigter Passkey oder eine Authenticator-App schützt Konten außerdem dann, wenn jemand Zugriff auf das E-Mail-Postfach erhält.

Bei den ersten vier erfolgreichen E-Mail-Link-Anmeldungen ohne Passkey und ohne bestätigte Authenticator-App führt Pfarrplaner die Person zur Einrichtung. Danach erscheint die Erinnerung frühestens 14 Tage nach der letzten tatsächlichen Anzeige erneut. **Später erinnern** gibt nur die aktuelle Sitzung frei. Ein Passkey oder eine bestätigte Authenticator-App beendet die Erinnerung.

Empfohlen wird zuerst ein Passkey. Die Person bestätigt die Anmeldung mit Geräte-PIN oder Biometrie. Als Alternative oder zusätzliche Möglichkeit kann sie eine Authenticator-App einrichten, zum Beispiel Microsoft Authenticator oder die quelloffene App 2FAS Auth. Die App erzeugt wechselnde sechsstellige Codes. Wiederherstellungscodes werden nur bei der Einrichtung angezeigt und müssen sicher außerhalb von Pfarrplaner verwahrt werden.

Die persönliche Verwaltung befindet sich im Profil unter **Sicherheit**. Passkey-Aktionen setzen eine angemeldete Sitzung voraus, verlangen User Verification und lehnen Stellvertretersitzungen ab. TOTP wird erst nach Eingabe eines gültigen App-Codes aktiviert. Nach fünf TOTP-Prüfungen innerhalb einer Minute werden weitere Prüfungen für dieses Konto vorübergehend gesperrt.

Bei aktiviertem TOTP folgt auf die Bestätigung eines E-Mail-Anmeldelinks eine Codeabfrage. Zulässig sind nur der aktuelle App-Code oder ein Recovery-Code. Ein E-Mail-Code ersetzt TOTP nicht. Eine Passkey-Anmeldung benötigt keine zusätzliche TOTP-Abfrage.

### Wiederherstellung eines verlorenen Faktors

Wenn eine Person ihr Authenticator-Gerät und alle Recovery-Codes verloren hat, prüfen Sie ihre Identität außerhalb von Pfarrplaner über einen bereits bekannten, organisationskontrollierten Kontaktweg. Eine eingehende E-Mail oder der Zugriff auf das betroffene Postfach allein genügt nicht. Dokumentieren Sie in Pfarrplaner nur eine kurze Supportfall-Referenz, keine Identitätsnachweise oder weiteren personenbezogenen Angaben.

Ein Super-Administrator kann anschließend den Faktor einer anderen Person widerrufen:

1. Öffnen Sie das Konto in der Benutzerverwaltung und wählen Sie die Wiederherstellung.
2. Geben Sie einen aktuellen sechsstelligen TOTP-Code aus der eigenen Super-Admin-Authenticator-App ein.
3. Tragen Sie die Supportfall-Referenz ein und bestätigen Sie den Widerruf.

Ein Super-Administrator kann den eigenen Faktor nicht wiederherstellen. Auch das Zielkonto darf kein Super-Admin-Konto sein. Fehlversuche werden begrenzt; nach fünf ungültigen Prüfungen innerhalb einer Minute wird die Aktion vorübergehend gesperrt. Die Recovery verlangt eine funktionierende TOTP-App des handelnden Super-Admins.

Bei Erfolg werden aktive oder unbestätigte Faktoren, ausstehende Anmeldelinks, persönliche API-Tokens und alle Datenbank-Sitzungen des Zielkontos widerrufen. Beim nächsten Anmelden muss die betroffene Person eine Authenticator-App neu einrichten und den ersten Code bestätigen. Bis dahin bleibt der normale Anwendungszugriff gesperrt. Der Vorgang wird mit handelndem Konto, Zielkonto, Referenz, Zeitpunkt und IP-Adresse protokolliert; Codes und Geheimnisse werden nicht protokolliert.

Für die Recovery muss `SESSION_DRIVER=database` gesetzt sein. Eine konfigurierte `SESSION_CONNECTION` muss dieselbe Datenbankverbindung wie die Anwendung verwenden. Vor Updates sind Datenbank und MinIO zu sichern und die Sicherung zu verifizieren. Die Faktor-Recovery-Migration ist vorwärtsgerichtet; einen Rollback führen Sie mit dem vorherigen Release-Image aus der Registry und einer verifizierten Datenbanksicherung aus, nicht durch manuelles Entfernen von Spalten.

### Benutzerkonten sperren und entfernen

Super-Administratoren können im Reiter **Benutzerkonto** eines anderen Kontos
alle registrierten Passkeys widerrufen, das Web-Konto sperren oder das Konto
entfernen. Eine Kontosperre verhindert neue Web-Anmeldungen und beendet den
Zugriff über bestehende Web-Sitzungen. Sie ändert den separaten DAV-Zugang
nicht. Für eine vollständige Entziehung des DAV-Zugangs muss das Konto entfernt
werden.

**Konto entfernen** wandelt das Konto in eine Person ohne Benutzerkonto um.
Personenstammdaten und Zuordnungen bleiben erhalten. Anmeldeinformationen,
Passkeys, Zwei-Faktor-Faktoren, persönliche API-Tokens, DAV-Zugangsdaten,
ausstehende Anmeldungen und Datenbank-Sitzungen werden widerrufen. Die Person
kann später erneut mit einem Benutzerkonto ausgestattet werden.

### Passkey-Betrieb

Passkey-Login und die Einrichtung sowie Verwaltung registrierter Geräte sind
integriert. `APP_URL`, RP-ID und zugelassene Origins müssen zur tatsächlich
verwendeten Browseradresse passen:

- `PASSKEYS_RELYING_PARTY_ID`: Domain ohne `https://` und ohne Port; leer
  übernimmt den Host aus `APP_URL`.
- `PASSKEYS_ALLOWED_ORIGINS`: kommaseparierte, exakte Origins, zum Beispiel
  `https://www.example.org`; Schema und Port müssen übereinstimmen. Leer
  übernimmt `APP_URL`.
- `PASSKEYS_USER_HANDLE_SECRET`: optionaler stabiler Schlüssel für
  pseudonyme WebAuthn-Benutzerkennungen. Leer wird `APP_KEY` genutzt. Ein
  eigener Wert muss auf allen App-Instanzen identisch und in der
  Wiederherstellungssicherung enthalten sein.

Für lokale Entwicklung ist `http://localhost` die HTTP-Ausnahme. Greift ein
anderes Gerät über einen Rechnernamen oder eine IP-Adresse zu, ist HTTPS
erforderlich. `APP_URL`, RP-ID und zugelassene Origins müssen dann exakt zu
dieser HTTPS-Adresse passen. Die Migration
`2026_11_17_000000_create_passkeys_table` legt Credential-Daten an. Sichern
Sie vor dem späteren Produktiv-Update Datenbank und Anwendungsversion. Ein
Rollback dieser Migration entfernt die Passkeys-Tabelle samt registrierten
Credentials; Benutzer müssten Passkeys anschließend erneut einrichten. Die
automatischen Paket-Routen bleiben deaktiviert. Projektspezifische Login-Routen
verwenden discoverable Credentials und User Verification. Ihre Challenges sind
an die Session gebunden, nach 60 Sekunden abgelaufen und nach jeder Einreichung
verbraucht. Login-Optionen und Prüfungen teilen ein Limit von sechs Anfragen pro
Minute je IP-Adresse. Verwaltungsrouten erfordern eine angemeldete Sitzung.
Benutzer können eigene Credentials registrieren, umbenennen und widerrufen;
Stellvertretersitzungen dürfen keine Anmeldefaktoren ändern. Passkey-Anmeldungen umgehen keine aktive TOTP-Recovery-Sperre.

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
