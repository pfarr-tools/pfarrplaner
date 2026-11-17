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

Anfangspasswörter sind eine ausdrücklich begrenzte Ausnahme: `PasswordService::temporaryInitialPassword()` erzeugt eine zufällige Kennung im dokumentierten Muster, und die aufrufenden Pfade setzen `must_change_password`. Bei der Kontoanlage akzeptiert `StrongPassword` dieses Muster zusätzlich zur normalen starken Passwortregel. Eine manuelle Super-Admin-Setzung verlangt nur einen nichtleeren String und erzwingt ebenfalls `must_change_password`; der Wert muss nicht dem Generator-Muster oder der Regel für dauerhaft gewählte Passwörter entsprechen. Normale Passwortänderungen verwenden unverändert die starke Passwortregel. Nach einem erfolgreichen Profil- oder öffentlichen Reset wird das Flag gelöscht.

Das Setzen eines Passworts für ein anderes Konto ist auf Super-Administrator:innen begrenzt. Die Autorisierung verwendet für Formularwerte die normalisierte Boolean-Auswertung, damit ein transportierter String `false` nicht als Kontoanlage missverstanden wird. Die Installationspfade rufen ebenfalls die zentrale Validierung auf; ungültige Administratorpasswörter werden vor dem Speichern abgewiesen.

Bei einer neuen Kontoanlage wird ein bestätigter Initialwert aus dem Benutzereditor an den bereits autorisierten Request gesendet. Der Server validiert ihn erneut, hasht ihn über das User-Modell, setzt `must_change_password` und übergibt ihn ausschließlich an die Kontodaten-Mail. Der Wert wird nicht in Redirects, Flash-Daten oder Inertia-Antworten ausgegeben.

Das Middleware-Modul `App\Http\Middleware\RequirePasswordChange` liegt nach der Authentifizierung in der Web-Middleware-Kette. Es erlaubt nur Passwortwechsel, Logout, CSRF-Token-Erneuerung und den kontrollierten Admin-Rückweg. Alle anderen authentifizierten Webrouten werden auf `password.edit` mit einem sicheren Routennamen als Rücksprung-Hinweis umgeleitet. Eine vorhandene `adminUserSwitchBack`-Session markiert ausschließlich den bewusst kontrollierten Proxy-Fall als Ausnahme.

Das konfigurierte Interstitial `security-beyond-password` gilt nur für Konten
ohne Passkey und bestätigtes oder ausstehendes TOTP. Es wird während eines
erzwungenen Passwortwechsels ebenfalls unterdrückt. `InterstitialService`
speichert „Später“ pro Benutzer mit Ablaufzeit (21 Tage); die feste
Ausblendaktion ist deaktiviert und wird serverseitig abgewiesen. Nach einem
Passkey oder bestätigtem TOTP greift die Zielgruppe nicht mehr. Die Empfehlung
verwendet vorhandene Benutzereinstellungen und benötigt keine Migration.

Der Admin-Reset ist an dieselbe `update`-Policy wie die Benutzerbearbeitung
gebunden und schließt Selbst-Reset sowie Super-Administrationskonten aus.
Manuelle Passwortvergabe für bestehende Konten ist Super-Administratoren
vorbehalten; der Request validiert das Passwort erneut und versendet es nur
über `AccountData`, während Inertia- und Flash-Antworten keine Passwörter
enthalten.

### Zwei-Faktor-Authentifizierung

Für die TOTP-Einschreibung ist `roundly-consulting/two-factor-for-laravel` als
Composer-Abhängigkeit installiert. Das Paket stellt RFC-6238-TOTP,
verschlüsselte Secrets, gehashte Recovery-Codes und den Schutz vor der
Wiederverwendung eines Zeitfensters bereit. Die Anwendung ergänzt die
fachliche Re-Authentifizierung, Rate-Limits und CSRF-geschützte Web-Endpunkte.
Die Profiloberfläche führt durch einen Dialog mit lokal erzeugtem QR-Code,
manueller Eingabealternative, Passwortbestätigung und expliziter Sicherung der
Recovery-Codes. URI und Recovery-Codes werden ausschließlich während der noch
nicht bestätigten Einschreibung angezeigt. Ein Neustart ersetzt eine
ausstehende Einschreibung und die zugehörigen Recovery-Codes. Die Paketversion und ihre
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
Die Login-Oberfläche fragt den Benutzernamen zunächst ohne Serveranfrage ab und
zeigt anschließend allen denselben Passwortschritt. Erst nach erfolgreicher
Passwortprüfung wird die kontoabhängige TOTP-Challenge gezeigt. Die Challenge-
Seite erhält `Cache-Control: no-store, no-cache, must-revalidate, private` und
`Pragma: no-cache`. Sechsstellige TOTP-Codes werden über ein zugängliches
Sechs-Felder-Eingabefeld erfasst; E-Mail-Codes und Recovery-Codes bleiben
ausdrückliche Alternativen.
Fehlversuche bei der Passwortanmeldung verwenden weiterhin das Limit aus
Benutzername und Quell-IP. Eine kontoweite Passwortsperre wird nicht ergänzt,
um gezieltes Sperren fremder Konten zu vermeiden. Der TOTP-Dienst sperrt
Fehlversuche dagegen kontoweit nach fünf Prüfungen pro Minute.
TOTP-/Recovery-Eingaben werden in Telescope maskiert; die zugehörige E-Mail mit
dem Einmalcode wird dort nicht aufgezeichnet. Der DemoBuilder entfernt
importierte Faktor- und Login-Challenge-Daten, damit anonymisierte Konten ihre
Demo-Zugangsdaten weiterhin verwenden können.

#### Administrative Wiederherstellung verlorener Faktoren

Die Recovery ist von Passwortreset und Login per E-Mail-Code getrennt. Der
geschützte POST-Endpunkt `user.two-factor.recover` akzeptiert das aktuelle
Passwort des handelnden Super-Admins und eine 1 bis 100 Zeichen lange
Supportfall-Referenz aus ASCII-Buchstaben, Ziffern, Punkt, Unterstrich oder
Bindestrich, zum Beispiel `SUP-2026-123`. `RecoverTwoFactorRequest`, `UserPolicy::recoverTwoFactor`
und `RecoverUserTwoFactor` prüfen die Super-Admin-Berechtigung; Selbst-Recovery
ist sowohl in Request als auch Action ausgeschlossen. Der Passwortvergleich
erfolgt mit `Hash::check` nach Sperren und erneutem Laden des Actor-Datensatzes.
Nach fünf Fehlversuchen innerhalb einer gleitenden Minute wird der Vorgang
vorübergehend gesperrt. Zähler und Zeitpunkt liegen auf dem Super-Admin-
Datensatz. `lockForUpdate()` serialisiert das Lesen und Ändern gemeinsam mit
der Passwortprüfung in einer Datenbanktransaktion; dadurch gilt die Grenze
über alle App-Hosts hinweg und benötigt keinen gemeinsam genutzten Cache.
Erfolgreiche Recovery setzt den Zähler zurück. Passwörter werden weder in
Auditdaten noch in Logs geschrieben.

Die Action verweigert die Wiederherstellung, wenn
`config('session.driver')` nicht `database` ist. Eine gesetzte
`session.connection` muss `database.default` entsprechen. In einer Transaktion
werden zunächst Actor und danach Zielkonto mit `lockForUpdate()` gesperrt. Nur
bei aktivem oder ausstehendem TOTP werden Faktor und Recovery-Codes gelöscht,
unverbrauchte `two_factor_login_challenges` verbraucht, `remember_token` und
`api_token` rotiert, Datensätze des Ziels in der Session-Tabelle und dessen
persönliche Sanctum-Tokens entfernt sowie der Audit-Datensatz angelegt. Der
Legacy-iCalendar-Tokenvalidator weist recovery-gesperrte Konten ebenfalls ab.
Ein Fehler rollt die Aktion als Ganzes zurück.

Die Migration `2026_11_16_000001_add_two_factor_recovery_state_and_audit_table`
fügt `users.two_factor_recovery_required_at` und
`two_factor_recovery_audits` hinzu. Die Migration
`2026_11_16_000002_add_two_factor_recovery_attempt_tracking_to_users` ergänzt
`users.two_factor_recovery_attempts` und
`users.two_factor_recovery_attempted_at`. Die Audit-Tabelle hält
`actor_user_id`, `target_user_id`, `support_reference`, `ip_address` und
Zeitstempel ohne kaskadierende Fremdschlüssel. Damit bleiben Auditzeilen nach
Kontolöschung erhalten; die numerischen IDs sind dann nicht mehr über eine
Beziehung auflösbar. Das Audit-Modell ist aus generierten Web-/API-CRUD-Routen
ausgeschlossen. Nur die Admin-Editor-Props für Super-Admins, die ein anderes
Konto bearbeiten, liefern Name und ID des handelnden Kontos, Zeitpunkt und Referenz zurück; IP
und Faktor-/Passwortdaten sind nicht Teil der Historienantwort. Die
Demo-Anonymisierung löscht passende Audits ausdrücklich mit.

Der Zielaccount erhält `two_factor_recovery_required_at`. Die Middleware
`RequireTwoFactorRecovery` schützt Web-, API- und Extranet-Routen
serverseitig. Per Routennamen bleiben ausschließlich die Recovery-Seite,
TOTP-Start/-Bestätigung, Logout, der erforderliche Passwortwechsel,
`user.switchback` und `csrf.keepalive` erlaubt. Andere Webanfragen werden auf
`user.two-factor.recovery` umgeleitet; API- und Extranet-Anfragen erhalten
HTTP 403. Nur die erfolgreiche Bestätigung über `user.two-factor.confirm`
löscht den Marker innerhalb derselben Benutzerzeilen-Transaktion, die den
neuen TOTP-Faktor aktiviert. Ein fehlgeschlagener oder unvollständiger Versuch
lässt den Marker bestehen. Nicht markierte Konten und Gäste werden nicht
eingeschränkt.

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
Kontext; Dev und Produktion müssen HTTPS verwenden. Die Passkey-Tabelle wird
mit der Migration `2026_11_17_000000_create_passkeys_table` angelegt. Sie
verweist fest auf `users` und löscht zugehörige Credentials bei regulärer
Kontolöschung. DemoBuilder entfernt importierte Passkeys bei der
Demo-Anonymisierung.

Die automatischen Paket-Routen bleiben deaktiviert. Der öffentliche Login
verwendet eigene Routen mit discoverable Credentials ohne Kontoauswahl. Die
Paketprüfung erzwingt User Verification; Laravel bindet jede Login-Challenge
zweckgebunden an die Session, begrenzt sie auf 60 Sekunden und verwirft sie
nach jedem Einreichungsversuch. Beide Endpunkte sind auf sechs Anfragen pro
Minute begrenzt. Ein erfolgreicher Passkey-Login rotiert die Session und
durchläuft weiterhin die zentralen Passwortwechsel- und Recovery-Gates.
Eigene Registrierungs- und Verwaltungsrouten verlangen Authentifizierung und
eine frische Passwortbestätigung. Die Registrierung verlangt discoverable
Credentials und User Verification. Listendarstellungen enthalten nur Namen,
Gerätekennung und Zeitstempel, keine Credential-Payloads. Umbenennen und
Widerruf prüfen die Eigentümerschaft; der Widerruf löscht das Credential.
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
