[//]: # (TOC: 5. Sicherheitsaudit)

# Sicherheitsaudit

Dieses Kapitel beschreibt den Stand eines vollständigen Sicherheitsdurchgangs auf Web-, API- und Download-Oberflächen von Pfarrplaner.

## Ziel des Audits

Geprüft wurden insbesondere:

- ungeschützte Web- und API-Routen
- benutzerdefinierte Controller-Aktionen ohne Policy- oder Gate-Prüfung
- Aktionen, die verschachtelte Ressourcen ohne Besitzprüfung verändern
- Selbstbedienungsfunktionen rund um Profil, Tokens und Benutzerwechsel
- öffentliche Endpunkte mit möglicher Datenpreisgabe
- alte Debug-, Entwickler- und Wartungsoberflächen

## Ergebnisübersicht

Der produktive Routenbestand wurde geprüft und gezielt gehärtet. Dabei wurden folgende Klassen von Problemen beseitigt:

- Debug- und Diagnose-Routen wurden entfernt oder standardmäßig blockiert.
- Nicht verwendete DGM-Anmeldung wurde vollständig entfernt.
- Öffentliche Schreibzugriffe in Kasual-, Streaming- und Dienstpfaden wurden mit Authentifizierung und Policies abgesichert.
- Mehrere Controller erhielten fehlende `Gate::authorize(...)`-Prüfungen.
- Verschachtelte Routen für Blöcke, Liturgie-Items und Anhänge prüfen jetzt die Zugehörigkeit zum Elternobjekt.
- Selbstbedienungsfunktionen für Profil, Einstellungen, API-Tokens und Benutzerwechsel wurden nachgeschärft.
- Zustandsändernde Alt-Routen wurden von `GET` auf `POST` umgestellt.
- Öffentliche Datei- und Bildzugriffe auf `attachments/*` benötigen nun Anmeldung oder eine signierte URL.
- Die öffentliche Urlaubs-Einbettung eines beliebigen Benutzers ist nicht mehr anonym erreichbar.

## Sicherheitsmodell nach dem Audit

### Routenmatrix nach Schutzklasse

Die Routen wurden nicht nur stichprobenartig, sondern nach Schutzklassen durchgegangen. Für den laufenden Betrieb ist die Einteilung wie folgt relevant:

| Schutzklasse | Typische Routenfamilien | Ergebnis |
| --- | --- | --- |
| authentifiziert | Verwaltung, Bearbeitung, Admin, Profil, Tokens, Berichte, Inputs, Liturgie-Editor, Gottesdienste, Kasualien, API, Extranet, DAV | auf Anmeldung begrenzt; kritische Sonderaktionen zusätzlich mit Objektprüfung nachgeschärft |
| signiert oder authentifiziert | `image/{path}`, `files/{path}`, signierte Liedblatt-Downloads, signierte öffentliche Anfrage-Links | anonym nur mit gültiger Signatur, intern weiterhin nach Anmeldung nutzbar |
| bewusst öffentlich lesend | öffentliche Informationsseiten, Podcasts, Kalender- und Einbettungsansichten, QR-Codes, Kinderkirchen-Ansichten, öffentliche Liturgie | keine allgemeinen Schreibrechte; öffentliche Embed- und Download-Pfade gezielt geprüft |
| bewusst öffentlich schreibend | `dimissoriale/{type}/{id}`, `anfrage/{ministry}/{user}/{services}/{sender?}`, `kontaktformular`, technische Sonderfälle wie `csrf-cookie` | nur verbleibend, wenn fachlich erforderlich; signierte oder fachlich eingegrenzte Verarbeitung |
| paketbedingt registriert, operativ blockiert | `_dusk/*`, `_ignition/*` | nicht als normale Produktoberfläche nutzbar; standardmäßig blockiert oder praktisch stillgelegt |

### Öffentlich bewusst erreichbar

Diese Endpunkte bleiben öffentlich, weil sie Produktfunktion sind:

- ausgewählte öffentliche Inhaltsseiten wie `was-ist-der-pfarrplaner`
- öffentliche Liturgieansicht `mitfeiern/{service:slug}`
- öffentliche Einbettungen für Gottesdienst-, Kinderkirchen- und Streaming-Ausgaben
- Podcast-Feed
- QR-Code-Ausgabe
- signierte öffentliche Workflows wie Ministry-Request, Dimissoriale und signierte Liedblatt-Downloads

### Öffentlich, aber technisch eingeschränkt

- Laravel-Dusk-Routen können paketbedingt registriert sein, werden aber serverseitig blockiert, solange `DUSK_ENABLED` nicht ausdrücklich gesetzt ist.
- Ignition-Routen können paketbedingt registriert sein, sind aber ohne aktivierte Runnable-Solutions praktisch stillgelegt.
- Datei- und Bildrouten für `attachments/*` akzeptieren anonym nur noch signierte URLs.

### Authentifiziert erforderlich

Die Verwaltungs-, Bearbeitungs- und API-Oberflächen für Benutzer, Dienste, Kasualien, Liturgie, Urlaub, Teams, Orte, Rollen und Konfiguration sind auf Authentifizierung begrenzt und wurden in den kritischen Sonderaktionen zusätzlich gegen fehlende Objektberechtigung geprüft.

## Wichtige behobene Schwachstellen

### 1. Öffentliche Mutationen

Mehrere spezielle Aktionen waren zwar fachlich intern gedacht, aber nicht ausreichend mit Authentifizierung oder Policy-Prüfungen abgesichert.

Behoben in:

- Dienst-, Streaming- und Kasual-Controller
- öffentliche Dimissorial- und Ministry-Request-Flows

### 2. Objektverwechslung in verschachtelten Routen

Bei Routen wie `service -> block -> item` oder `rite -> attachment` konnten zuvor fremde Kindobjekte über direkte IDs adressiert werden.

Jetzt wird das Kindobjekt jeweils über die Elternbeziehung aufgelöst oder gegen diese geprüft.

### 3. Selbstbedienung und Rechteausweitung

Nachgeschärft wurden:

- Benutzer-Impersonation
- Passwort-Reset
- Stadtberechtigungen bei Benutzeränderungen
- Einstellungen und Token-Erzeugung

### 4. Öffentliche Dateipfade

Die generischen Endpunkte `image/{path}` und `files/{path}` waren für `attachments/*` anonym nutzbar. Dadurch waren erratbare oder bekannte Dateinamen öffentlich abrufbar.

Jetzt gilt:

- intern: Zugriff nach Anmeldung
- öffentlich: Zugriff nur mit signierter URL

## Regressionstests

Für den Audit wurden spezielle Sicherheitsprüfungen als Tests ergänzt:

- `tests/Builder/SecurityRouteAuditTest.php`
- `tests/Feature/SecurityPublicAssetAccessFeatureTest.php`

Diese Tests prüfen unter anderem:

- entfernte Debug-Routen
- keine sensiblen Zustandsänderungen mehr per `GET`
- keine unerwarteten öffentlichen Mutationsrouten
- Schutz öffentlicher Datei- und Bildpfade

## Verbleibende bewusst öffentliche Schreibpfade

Nach Abschluss des Audits bleiben nur noch fachlich oder technisch begründete öffentliche Schreibpfade übrig:

- `POST dimissoriale/{type}/{id}`: nur mit gültiger signierter Anforderung sinnvoll nutzbar
- `POST anfrage/{ministry}/{user}/{services}/{sender?}`: Diensteauswahl durch signierte Vorgabeliste begrenzt
- `POST kontaktformular`: öffentlicher Kontaktkanal ohne Objektmutation
- `POST _ignition/execute-solution` und `POST _ignition/update-config`: paketbedingt vorhanden, aber ohne aktivierte Runnable-Solutions praktisch stillgelegt
- `GET|POST csrf-cookie`: technischer Bootstrap-Endpunkt

## Restliche Aufgaben nach diesem Audit

Der kritische Härtungsdurchgang ist abgeschlossen. Weitere Prüfungen bleiben normale Weiterentwicklungsarbeit:

- neue Spezialrouten bei künftigen Features wieder gegen diese Checkliste prüfen
- bei neuen öffentlichen Ausgaben immer zwischen offen, signiert und authentifiziert unterscheiden
- bei neuen verschachtelten Routen die Eltern-Kind-Zugehörigkeit explizit absichern
