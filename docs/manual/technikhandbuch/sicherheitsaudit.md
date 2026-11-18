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

### Passwortlose Konten und Sicherheits-Onboarding

Webkonten melden sich per Passkey oder einmaligem E-Mail-Anmeldelink an. Es gibt keine Passwortanmeldung, keinen Passwortwechsel und keinen Passwort-Reset. Das Modell `App\Models\People\User` nutzt `is_official_user` als persistierte boolesche Kontoklassifikation. Die Migration übernimmt für Bestandskonten den Wert `true`, wenn ein nichtleerer Legacy-Hash vorhanden ist, und setzt `must_change_password` für alle Konten auf `false`. Bestehende Hashes bleiben zunächst gespeichert, werden jedoch von keinem Authentifizierungs- oder Verwaltungsablauf verwendet.

Eine Aktivierung des Login-Kontos erfolgt über die bestehende `update`-Berechtigung für die Person; die Schnellanlage verwendet die bestehende `create`-Berechtigung. Ein Super-Admin-Sonderrecht ist dafür nicht erforderlich. Beim erstmaligen Aktivieren wird eine Kontodaten-Mail mit Hinweisen zum E-Mail-Link und zur optionalen Passkey-Einrichtung versendet. Login-Links werden nur für aktivierte offizielle Konten angefordert und unmittelbar vor der Anmeldung erneut gegen den Kontostatus geprüft.

`SecurityOnboardingService` zählt erfolgreiche E-Mail-Link-Logins für offizielle Konten ohne Passkey und ohne bestätigtes TOTP. Die ersten vier passenden Logins lösen das Middleware-Gate aus; danach gilt eine Frist von mindestens 14 Tagen seit der tatsächlichen Anzeige. Eine Sitzungsfreigabe wird pro Benutzer in der Session gespeichert und endet mit ihr. Das Gate ist Teil der Web-, API- und Extranet-Middleware. Die Onboarding-Seite, Faktor-Setup, Abmelden und Aufschieben bleiben erreichbar. Der Anzeigezeitpunkt wird erst beim Rendern der Seite geschrieben, nicht durch einen API- oder Redirect-Versuch.

Die Onboarding-Inertia-Seite erklärt Passkeys und Authenticator-Apps und bindet die bestehende `SecurityTab` direkt ein. Passkey-Registrierung erfordert eine frische WebAuthn-Challenge und User Verification; TOTP wird erst nach erfolgreicher Codeprüfung aktiviert. Ein unvollständiges TOTP-Setup beendet das Gate nicht. Als Authenticator-Apps nennt die UI Microsoft Authenticator und die quelloffene App 2FAS Auth.

### Zwei-Faktor-Authentifizierung

Für die TOTP-Einschreibung ist `roundly-consulting/two-factor-for-laravel` als
Composer-Abhängigkeit installiert. Das Paket stellt RFC-6238-TOTP,
verschlüsselte Secrets, gehashte Recovery-Codes und den Schutz vor der
Wiederverwendung eines Zeitfensters bereit. Die Anwendung ergänzt die
authentifizierte Sitzung, Rate-Limits und CSRF-geschützte Web-Endpunkte.
Die Profiloberfläche führt durch einen Dialog mit lokal erzeugtem QR-Code,
manueller Eingabealternative und expliziter Sicherung der Recovery-Codes.
TOTP wird erst nach Prüfung des ersten App-Codes aktiviert. URI und Recovery-Codes werden ausschließlich während der noch
nicht bestätigten Einschreibung angezeigt. Ein Neustart ersetzt eine
ausstehende Einschreibung und die zugehörigen Recovery-Codes. Die Paketversion und ihre
Lizenz werden im Composer-Lockfile festgeschrieben und vor jedem Release
geprüft.

Nach erfolgreicher Bestätigung eines E-Mail-Anmeldelinks prüft die Anwendung,
ob ein bestätigter TOTP-Faktor nötig ist. In diesem Fall liegt der Pending-
Zustand fünf Minuten in der Session; erst ein gültiger App- oder Recovery-Code
schließt die Anmeldung ab und setzt den Guard. TOTP- und Recovery-Code-Prüfung
verwenden die Paket-API mit Replay-Schutz und Einmalverwendung. Der E-Mail-
Anmeldelink wird mit
`random_bytes` erzeugt; in `passwordless_login_links` liegt ausschließlich
sein SHA-256-Hash. Links verfallen nach zehn Minuten, werden bei einer neuen
Anforderung für dasselbe Konto ungültig und beim Bestätigen unter
`lockForUpdate()` atomar einmalig verbraucht. Anforderungen sind pro Quell-IP
und mit einem HMAC-Schlüssel pro normalisierter E-Mailadresse begrenzt.
Bekannte und unbekannte Adressen erhalten dieselbe Rückmeldung. Die
Kontoabfrage und das Erzeugen des Mailjobs laufen in terminierender
Middleware erst nach dem Senden dieser Antwort, damit ihre Verarbeitungszeit
die Antwortlaufzeit nicht unterscheidet. Der Login-Link-Request lädt zuvor
einen frischen CSRF-Token und übermittelt ihn mit dem POST.

Das Öffnen des tokenhaltigen Links verbraucht ihn nicht. Es legt nur den
Datensatz in der Sitzung ab und leitet auf eine tokenfreie Bestätigungsseite
weiter. Erst deren CSRF-geschützter POST verbraucht den Link. Dadurch können
E-Mail-Linkscanner keine Anmeldung allein durch einen GET auslösen. Die Seite
setzt `Cache-Control: no-store` und `Referrer-Policy: no-referrer`. Konten mit
aktiviertem TOTP wechseln nach Linkbestätigung in die bestehende
TOTP-Challenge; dort sind ausschließlich Authenticator- und Recovery-Codes
zulässig. Es gibt keinen E-Mail-Code als TOTP-Ersatz mehr. Die Challenge-Seite
erhält `Cache-Control: no-store, no-cache, must-revalidate, private` und
`Pragma: no-cache`.
E-Mail-Link-Anforderungen werden pro Quell-IP und normalisierter E-Mailadresse
begrenzt. TOTP-Prüfungen werden zusätzlich pro Benutzerkonto nach fünf
Prüfungen innerhalb einer Minute vorübergehend gesperrt.
TOTP-/Recovery-Eingaben werden in Telescope maskiert; E-Mails mit
Anmeldelinks werden dort nicht aufgezeichnet. Die historische Tabelle
`two_factor_login_challenges` bleibt für bereits ausgeführte Migrationen und
die Bereinigung vorhandener Datensätze erhalten, wird aber nicht mehr für neue
Anmeldungen beschrieben. Der DemoBuilder entfernt importierte Faktor- und
Login-Challenge-Daten, damit anonymisierte Konten ihre Demo-Zugangsdaten
weiterhin verwenden können.

#### Administrative Wiederherstellung verlorener Faktoren

Die administrative Wiederherstellung setzt eine außerhalb von Pfarrplaner geprüfte Identität voraus. Der geschützte POST-Endpunkt `user.two-factor.recover` akzeptiert einen aktuellen sechsstelligen TOTP-Code des handelnden Super-Admins und eine 1 bis 100 Zeichen lange Supportfall-Referenz aus ASCII-Buchstaben, Ziffern, Punkt, Unterstrich oder Bindestrich. `RecoverTwoFactorRequest`, `UserPolicy::recoverTwoFactor` und `RecoverUserTwoFactor` prüfen Super-Admin-Berechtigung, Eingaben und Replay-Schutz. Selbst-Recovery und ein Super-Admin-Zielkonto sind ausgeschlossen.

Fehlversuche werden am handelnden Konto mit Zeitstempel gespeichert. Nach fünf Prüfungen innerhalb einer gleitenden Minute wird der Vorgang vorübergehend gesperrt. Zähler, Actor- und Zielladung sowie Codeprüfung sind mit `lockForUpdate()` und einer Datenbanktransaktion serialisiert. Passwörter werden bei dieser Aktion weder verlangt noch verarbeitet.

`SESSION_DRIVER=database` ist erforderlich; eine gesetzte `SESSION_CONNECTION` muss `database.default` entsprechen. Bei Erfolg werden Faktor und Recovery-Codes des Zielkontos, offene E-Mail-Links, API-Tokens und Datenbank-Sitzungen widerrufen und ein Audit-Datensatz angelegt. `RequireTwoFactorRecovery` schützt danach Web-, API- und Extranet-Routen. Erlaubt bleiben Recovery-Seite, TOTP-Setup/-Bestätigung, Logout, kontrollierter Admin-Rückweg und CSRF-Keepalive. Nur die erfolgreiche TOTP-Bestätigung hebt die Sperre auf.

Die Migrationen ergänzen `users.two_factor_recovery_required_at`, Versuchszähler und Zeitstempel sowie `two_factor_recovery_audits`. Das Audit speichert Actor-ID, Ziel-ID, Supportfall-Referenz, IP-Adresse und Zeitpunkt, jedoch keine Codes oder Geheimnisse. Die Migrationen sind vorwärtsgerichtet; Rollback erfolgt mit vorherigem Image und verifizierter Datenbanksicherung.

## Passkey-Anmeldung und Geräteverwaltung

Die WebAuthn-Basis verwendet Laravels `laravel/passkeys` (`v0.3.0`) und den
offiziellen Browser-Client `@laravel/passkeys` (`v0.4.1`). Das PHP-Paket
verwaltet WebAuthn-Credentials und -Ceremonies; der Browser-Client nutzt
`@simplewebauthn/browser`. Das Projekt bindet sein
`App\Models\People\User`-Modell über `PasskeyUser` und
`PasskeyAuthenticatable` an. Ein HMAC aus Tabellennamen und Benutzer-ID bildet
den nicht personenbezogenen WebAuthn-User-Handle.

`config/passkeys.php` bezieht die RP-ID und Browser-Origins aus der
Betriebskonfiguration:

- `PASSKEYS_RELYING_PARTY_ID` ist die Domain ohne Schema oder Port; leer wird
  der Host aus `APP_URL` verwendet.
- `PASSKEYS_ALLOWED_ORIGINS` enthält kommasepariert die exakten Browser-Origins
  einschließlich Schema und gegebenenfalls Port; leer wird `APP_URL` verwendet.
- `PASSKEYS_USER_HANDLE_SECRET` ist optional. Leer wird `APP_KEY` verwendet.
  Ein eigener Wert muss zwischen allen App-Instanzen stabil und in Backups
  enthalten sein.

Lokal ergibt `APP_URL=http://localhost:8180` die RP-ID `localhost` und die
Origin `http://localhost:8180`. Browser behandeln `localhost` als sicheren
Kontext; `http://localhost` ist die vorgesehene lokale HTTP-Ausnahme. Zugriffe
von anderen Geräten über Rechnernamen oder IP-Adressen benötigen HTTPS. Die
Passkey-Tabelle wird
mit der Migration `2026_11_17_000000_create_passkeys_table` angelegt. Sie
verweist fest auf `users` und löscht zugehörige Credentials bei regulärer
Kontolöschung. DemoBuilder entfernt importierte Passkeys bei der
Demo-Anonymisierung.

Die automatischen Paket-Routen bleiben deaktiviert. Der öffentliche Login
verwendet eigene Routen mit discoverable Credentials ohne Kontoauswahl. Die
Paketprüfung erzwingt User Verification; Laravel bindet jede Login-Challenge
zweckgebunden an die Session, begrenzt sie auf 60 Sekunden und verwirft sie
nach jedem Einreichungsversuch. Beide Endpunkte sind auf sechs Anfragen pro
Minute begrenzt. Ein erfolgreicher Passkey-Login rotiert die Session und durchläuft weiterhin ein aktives
TOTP-Recovery-Gate.
Eigene Registrierungs- und Verwaltungsrouten verlangen Authentifizierung. Die
Registrierung bleibt an eine frische WebAuthn-Challenge gebunden und verlangt
discoverable Credentials sowie User Verification. Listendarstellungen enthalten nur Namen,
Gerätekennung und Zeitstempel, keine Credential-Payloads. Umbenennen und
Widerruf prüfen die Eigentümerschaft; der Widerruf löscht das Credential.
Faktorverwaltungsrouten lehnen Stellvertretersitzungen serverseitig ab.
Das Profil bietet mehrere Geräte, Umbenennen und Widerruf an. Die Login-Seite
zeigt die Passkey-Option kontoübergreifend vor einer Kontoauswahl.

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
- Kontoverwaltung und Benutzerwechsel
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
