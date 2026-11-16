[//]: # (TOC: 7. Sicherheitsaudit)

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

### Passwortlebenszyklus

Die Passwortregel ist zentral in `App\Rules\StrongPassword` definiert und wird bei Profiländerungen, administrativ gesetzten Passwörtern, öffentlichen Zurücksetzungen und der Ersteinrichtung verwendet. Sie verlangt mindestens acht Zeichen sowie Großbuchstaben, Kleinbuchstaben, eine Zahl und ein Sonderzeichen. Die Regel wird serverseitig geprüft; die Hinweise in den Vue-Komponenten sind nur eine Bedienhilfe.

Automatisch erzeugte Anfangspasswörter sind eine ausdrücklich begrenzte Ausnahme: `PasswordService::temporaryInitialPassword()` erzeugt eine zufällige Kennung im dokumentierten Muster, und die aufrufenden Pfade setzen `must_change_password`. Diese Kennung ist keine dauerhafte Passwortvariante. Nach einem erfolgreichen Profil- oder öffentlichen Reset wird das Flag gelöscht.

Das Setzen eines Passworts für ein anderes Konto ist auf Super-Administrator:innen begrenzt. Die Autorisierung verwendet für Formularwerte die normalisierte Boolean-Auswertung, damit ein transportierter String `false` nicht als Kontoanlage missverstanden wird. Die Installationspfade rufen ebenfalls die zentrale Validierung auf; ungültige Administratorpasswörter werden vor dem Speichern abgewiesen.

Bei einer neuen Kontoanlage wird ein bestätigter Initialwert aus dem Benutzereditor an den bereits autorisierten Request gesendet. Der Server validiert ihn erneut, hasht ihn über das User-Modell, setzt `must_change_password` und übergibt ihn ausschließlich an die Kontodaten-Mail. Der Wert wird nicht in Redirects, Flash-Daten oder Inertia-Antworten ausgegeben.

Das Middleware-Modul `App\Http\Middleware\RequirePasswordChange` liegt nach der Authentifizierung in der Web-Middleware-Kette. Es erlaubt nur Passwortwechsel, Logout, CSRF-Token-Erneuerung und den kontrollierten Admin-Rückweg. Alle anderen authentifizierten Webrouten werden auf `password.edit` mit einem sicheren Routennamen als Rücksprung-Hinweis umgeleitet. Eine vorhandene `adminUserSwitchBack`-Session markiert ausschließlich den bewusst kontrollierten Proxy-Fall als Ausnahme.

### Zwei-Faktor-Authentifizierung

Für die TOTP-Einschreibung ist `roundly-consulting/two-factor-for-laravel` als
Composer-Abhängigkeit installiert. Das Paket stellt RFC-6238-TOTP,
verschlüsselte Secrets, gehashte Recovery-Codes und den Schutz vor der
Wiederverwendung eines Zeitfensters bereit. Die Anwendung ergänzt die
fachliche Re-Authentifizierung, Rate-Limits und CSRF-geschützte Web-Endpunkte.
Die Profiloberfläche zeigt URI und Recovery-Codes ausschließlich während der
noch nicht bestätigten Einschreibung. Ein Neustart ersetzt eine ausstehende
Einschreibung und die zugehörigen Recovery-Codes. Die Paketversion und ihre
Lizenz werden im Composer-Lockfile festgeschrieben und vor jedem Release
geprüft.

Der Login prüft nach korrektem Passwort den Faktor, bevor ein Guard oder ein
Remember-Cookie gesetzt wird. Der Pending-Zustand liegt in der Session und
läuft nach fünf Minuten ab; Passwortfehlversuche werden währenddessen nicht
zurückgesetzt. TOTP- und Recovery-Code-Prüfung verwenden die Paket-API mit
Replay-Schutz und Einmalverwendung. Alternativ erzeugt die Anwendung einen
achtstelligen E-Mail-Code, speichert nur dessen Hash in
`two_factor_login_challenges`, begrenzt Fehlversuche und verbraucht den Datensatz
unter Zeilensperre atomar. Die Login-Routen sind CSRF-geschützte Web-Routen.
TOTP-/Recovery-Eingaben werden in Telescope maskiert; die zugehörige E-Mail mit
dem Einmalcode wird dort nicht aufgezeichnet. Der DemoBuilder entfernt
importierte Faktor- und Login-Challenge-Daten, damit anonymisierte Konten ihre
Demo-Zugangsdaten weiterhin verwenden können.

Für die spätere Passkey-Etappe wird `web-auth/webauthn-framework` als Kandidat vorgemerkt. Das Projekt beschreibt eine MIT-lizenzierte PHP-WebAuthn-Implementierung; die genaue Paketversion, RP-ID-, Origin- und Laravel-Integrationsentscheidung bleibt der Passkey-Etappe vorbehalten.

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

- Laravel-Dusk-Routen (`/_dusk` und `/_dusk/*`) sind ausschließlich bei `APP_ENV=dusk` und `DUSK_ENABLED=true` zugänglich. In allen anderen Umgebungen antwortet die globale Middleware `BlockDevHelperRoutes` mit HTTP 404, auch wenn das Flag versehentlich aktiviert ist.
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
