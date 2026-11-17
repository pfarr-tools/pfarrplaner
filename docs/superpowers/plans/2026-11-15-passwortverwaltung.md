# Passwortverwaltung und moderne Anmeldung – Umsetzungsplan

> **Für agentische Bearbeiter:** Nutze `superpowers:executing-plans` oder
> `superpowers:subagent-driven-development`, um diesen Plan schrittweise
> umzusetzen. Jeder Schritt wird separat verifiziert.

**Ziel:** Issue #80 in sichere, testbare Etappen zerlegen und die
Passwortverwaltung sowie die zusätzlichen Anmeldefaktoren für Pfarrplaner
umsetzen.

**Architektur:** Die bestehende Laravel-Authentifizierung, `User`-Modell,
`PasswordService`, Inertia-Seiten und Benutzerverwaltung bleiben die
Integrationspunkte. Passwortregeln, Reset, erzwungene Änderung, E-Mail-/TOTP-
2FA und Passkeys werden als getrennte Sicherheitsbausteine mit gemeinsamen
Session- und Recovery-Regeln eingeführt. Kein Klartextpasswort und kein
geheimer Faktor wird in Logs, Props oder API-Antworten ausgegeben.

**Technologie:** Laravel 13 / PHP 8.4, bestehende Session-Authentifizierung,
Inertia.js/Vue, PHPUnit, Dusk und die vorhandene Mail-Infrastruktur. Für TOTP
und WebAuthn wird vor der Implementierung geprüft, ob eine gepflegte, mit der
aktuellen Laravel-Version kompatible Bibliothek bereits vorhanden ist; neue
Abhängigkeiten werden nur mit dokumentierter Begründung aufgenommen.

**Spezifikation:** GitHub Issue #80 – Neue Features zur Verwaltung von
Passwörtern.

## Globale Einschränkungen

- Die Sicherheitsfunktionen müssen über bestehende Rollen und Policies
  autorisiert werden; nur Super-Administrator:innen dürfen fremde Passwörter
  direkt setzen.
- Ein von Administrator:innen gesetztes oder automatisch erzeugtes Passwort
  setzt `must_change_password`; die Änderung erfolgt in einem nicht
  umgehbaren Interstitial ohne Admin-Layout.
- Neue Passwörter werden serverseitig nach einer zentralen Regel validiert:
  mindestens 8 Zeichen sowie Groß-/Kleinbuchstaben, Zahl und Sonderzeichen.
- Der vorhandene Reset-Flow bleibt für nicht angemeldete Benutzer:innen
  nutzbar und darf keine Benutzerexistenz verraten.
- Sicherheitsgeheimnisse werden verschlüsselt oder gehasht gespeichert und
  niemals in Logs, Demo-Daten oder Commit-Inhalten abgelegt.
- Jede Datenbankmigration implementiert `up()` und `down()`; Tests verwenden
  ausschließlich die isolierte Testdatenbank.
- User-facing Änderungen werden im Benutzer- und Administratorenhandbuch
  dokumentiert; technische Authentifizierungsdetails gehören zusätzlich ins
  technische Handbuch.

## Betrachtete Ansätze

1. **Ein großer Auth-Refactor:** alle Passwort-, 2FA- und Passkey-Flows in
   einem Zug umbauen. Das ergäbe eine einheitliche Oberfläche, erhöht aber
   Risiko, Reviewgröße und Rollback-Aufwand erheblich.
2. **Getrennte Sicherheitsbausteine mit gemeinsamem Kern (gewählt):** zuerst
   Passwortregeln, Reset, Admin-Vorschlag und erzwungene Änderung; danach
   E-Mail-/TOTP-2FA; zuletzt Passkeys. Gemeinsame Challenge-, Recovery- und
   Session-Regeln werden früh definiert, während jede Etappe unabhängig
   testbar und ausrollbar bleibt.
3. **Nur externe Identitätsanbieter:** Passwörter und Faktoren vollständig an
   einen zentralen Identity Provider delegieren. Das passt nicht zum
   vorhandenen lokalen Login, zur bestehenden Admin-Verwaltung und zum
   Offline-/Installationsmodell.

## Review-Fokus

- Ein Admin setzt ein Passwort für eine andere Person: Berechtigung,
  Bestätigung, Hashing, `must_change_password` und keine Passwortausgabe in
  Folgeanfragen.
- Eine Person fordert einen Reset für eine unbekannte E-Mailadresse an:
  identische Antwort und kein Account-Leak.
- Ein erzwungener Passwortwechsel wird direkt über Menü, Zurück-Navigation
  oder Admin-Benutzerwechsel umgangen: Zugriff bleibt gesperrt; der
  kontrollierte Admin-Proxy-Fall bleibt möglich.
- 2FA-Challenges laufen ab, werden nur einmal verwendet und sind gegen
  Wiederholungs- und Rate-Limit-Angriffe geschützt.
- Passkeys werden bei Registrierung, Widerruf, mehreren Geräten und
  ungültigen Browserantworten sicher behandelt.

---

### Task 1: Sicherheitsmodell und Passwortregeln

**Dateien:**

- Ändern: `src/app/Models/People/User.php`,
  `src/app/Services/PasswordService.php`, `src/app/Http/Requests/UserRequest.php`
- Erstellen: dedizierte Passwortregel/Form-Request- und ggf. Migrationsdatei
- Testen: bestehende User-/PasswordService-Feature- und Unit-Tests erweitern

- [ ] Bestehende Passwortänderungs-, Admin- und Resetpfade inventarisieren und
  ihre erlaubten Rollen und Zustände als Tests festhalten.
- [ ] Eine zentrale serverseitige Passwortregel für Mindestlänge,
  Groß-/Kleinbuchstaben, Zahl und Sonderzeichen einführen; die UI zeigt nur
  dieselben Anforderungen an.
- [ ] `PasswordService::randomPassword()` kryptographisch sicher machen und
  das bestehende Muster `{Konsonant}{Vokal}{Konsonant}{Vokal}{4 Zahlen}` für
  automatisch erzeugte Initialpasswörter beibehalten.
- [ ] Tests für gültige/ungültige Passwörter, Hash-Erhalt, Reset und
  `must_change_password` schreiben und ausführen.

### Task 2: Admin-Passwort setzen und Initialpasswort vorschlagen

**Dateien:**

- Ändern: `src/app/Http/Controllers/UserController.php`,
  `src/app/Http/Requests/UserRequest.php`,
  `src/resources/js/Pages/Admin/User/UserEditor.vue`
- Prüfen: `src/app/Policies/UserPolicy.php`, `src/app/Mail/User/AccountData.php`
- Testen: `src/tests/Feature/UserWebFeatureTest.php` und fokussierte
  Browserabdeckung für den Benutzereditor

- [ ] Admin-Setzen und normales Selbständern in getrennte autorisierte
  Aktionen/Requests trennen; Super-Admin-Rechte dürfen nicht versehentlich
  auf lokale Admins ausgeweitet werden.
- [ ] Beim Erstellen eines Benutzerkontos ein neues Passwort vorschlagen,
  explizit anzeigen und nur nach Bestätigung speichern bzw. über die
  bestehende Kontodaten-Mail versenden.
- [ ] Beim Zurücksetzen eines Benutzerpassworts die bisherige Aktion auf den
  zentralen Service umstellen, `must_change_password` setzen und das
  Passwort nicht in URL, Session-Flash oder Inertia-Props zurückgeben.
- [ ] Feature- und Dusk-Tests für Berechtigungen, Mailinhalt ohne unnötige
  Geheimnisverbreitung, Hashing und den Vorschlagsdialog ergänzen.

### Task 3: Nicht umgehbares Interstitial und Passwort-Reset

**Dateien:**

- Ändern: `src/app/Http/Controllers/HomeController.php`,
  `src/app/Http/Controllers/Auth/*`, `src/routes/web/Auth.php`,
  `src/resources/js/Pages/Auth/PasswordChanger.vue`
- Erstellen/Ändern: Auth-Middleware bzw. Redirect-Regel und Reset-Tests
- Dokumentieren: Benutzer- und Administratorenhandbuch

- [ ] Den erzwungenen Wechsel als eigenes, schlankes Auth-Layout darstellen;
  normale Navigation und Admin-Menü werden blockiert.
- [ ] Direkte URLs, Zurück-Navigation, Inertia-Navigation und Logout sauber
  behandeln; ein Admin-Benutzerwechsel darf den kontrollierten Proxy-Fall
  nicht fälschlich blockieren.
- [ ] Laravel-Reset-Token, Ablauf, Throttling und generische Antworten prüfen
  und fehlende Sicherheitsabdeckung ergänzen.
- [ ] Feature- und Browser-Tests für alle Bypass-Versuche und den erfolgreichen
  Wechsel ergänzen.

### Task 4: E-Mail- und TOTP-Zwei-Faktor-Authentifizierung

**Dateien:**

- Erstellen: migrations-/modellbezogene 2FA-Sicherheitsdaten,
  Challenge-Service, Requests, Controller und Inertia-Komponenten
- Ändern: Login-Routen/-Controller, User-Sicherheitsbereich,
  Übersetzungen und Mail-Templates
- Testen: PHPUnit-Featuretests, Mail-/Rate-Limit-Tests und Dusk-Flows

- [x] Vor der Implementierung Bibliotheks- und Verschlüsselungsentscheidung
  festhalten; TOTP-Geheimnisse verschlüsselt, Recovery-Codes gehasht und
  Einmal-Challenges mit Ablauf und Zweckbindung speichern.
- [ ] Aktivierung, Bestätigung, Deaktivierung und Geräte-/Recovery-Verlust
  mit Re-Authentifizierung und Super-Admin-Recovery modellieren.
- [x] Nach korrektem Passwort eine begrenzte E-Mail- oder TOTP-Challenge
  anzeigen; Session-Freigabe erst nach erfolgreicher Prüfung setzen.
- [x] Rate-Limits, Replay-Schutz, Ablauf, Mailversand und generische
  Fehlermeldungen testen und im Administratorenhandbuch dokumentieren.

### Task 4b: Verständlicher TOTP- und Login-Ablauf

Der UI-Kommentar in Issue #80 ergänzt vor Passkey-Anmeldung diese
Nutzerführung:

- [x] TOTP-Einrichtung startet über einen einzelnen Button und führt in einem
  mehrstufigen Popup mit klaren, nicht-technischen Erklärungen durch App-Auswahl,
  QR-Code, Bestätigung und sichere Aufbewahrung der Recovery-Codes. Microsoft
  Authenticator und [2FAS Auth](https://2fas.com/auth/) als Open-Source-App für
  iOS und Android werden als Beispiele genannt. Der QR-Code wird lokal erzeugt;
  der TOTP-Seed darf nicht an einen externen QR-Dienst gelangen.
- [x] Login beginnt mit dem Benutzernamen und führt verständlich zum
  Passwortschritt. Nach dem Benutzernamen
  sehen alle denselben nächsten Schritt. Der Browser fragt das Konto beim
  Wechsel nicht ab; erst nach erfolgreicher Passwortprüfung wird bei
  entsprechend eingerichteten Konten TOTP verlangt. Unbekannte Konten und
  konfigurierte Faktoren dürfen vor erfolgreicher Anmeldung nicht erkennbar
  sein.
- [ ] Passkey wird als allgemeine Option in diesem gemeinsamen Login-Schritt
  angeboten und mit Task 5 funktionsfähig umgesetzt.
- [x] TOTP-Eingabefelder erhalten passende Browser- und Passwortmanager-Semantik
  (`autocomplete="one-time-code"`, numerische Eingabe und eindeutige
  Beschriftung). Für den
  sechsstelligen TOTP-Code wird ein gruppiertes Sechs-Felder-Eingabefeld mit
  Fokuswechsel, Rücksprung per Rücktaste, Ziffernfilter, vollständigem
  Einfügen/Autofill, Live-Status für Screenreader und regulärem Absende-Button
  verwendet. Die vollständige Eingabe wird als `code` an das Backend gesendet.
- [ ] Die Erkennung mit Bitwarden wird in einem echten Browser geprüft.
- [x] E-Mail-Codes und Recovery-Codes bleiben als ausdrücklich wählbare
  Alternativen bedienbar: E-Mail-Codes verwenden acht Ziffern; Recovery-Codes
  behalten ein unsegmentiertes Textfeld. Cache-Header verhindern die
  Zwischenspeicherung der Challenge-Seite.
- [x] Die bestehende TOTP-Sicherheitsbasis verifizieren und regressionssichern:
  der gesperrte Dienst zählt Versuche pro Konto atomar (fünf pro Minute),
  blockiert weitere Verifikationen temporär, schützt vor Replay und verwendet
  zeitkonstante Codevergleiche; TOTP-Geheimnisse liegen verschlüsselt. Die
  Standardkonfiguration akzeptiert höchstens ein Zeitfenster Abweichung.
- [x] Keine zusätzliche kontoweite Sperre für falsche Passwörter einführen.
  Passwortfehler verwenden die bestehende Begrenzung nach Benutzername und IP;
  falsche TOTP-Prüfungen werden pro Konto nach fünf Prüfungen pro Minute
  temporär gesperrt. So bleibt der TOTP-Kontoschutz erhalten, ohne dass Dritte
  gezielt Konten allein durch Passwortversuche blockieren können.
- [x] Auch für Passkey-Anfragen keine kontoweite Sperre aufgrund fremder
  Fehlversuche einführen; der Passkey-Endpunkt behält eine anfragebasierte
  Begrenzung.
- [ ] Benutzerhandbuch und relevante Browser-/Feature-Tests aktualisieren.

### Task 5: Passkey-Anmeldung

**Dateien:**

- Erstellen: Credential-Migration/Modell, WebAuthn-Service, Controller,
  Requests und Inertia-Komponenten
- Ändern: Login-/Profilrouten, User-Sicherheitsbereich, Übersetzungen und
  technische Dokumentation
- Testen: serverseitige Credential-/Challenge-Tests und Browserabdeckung mit
  unterstützter Teststrategie

- [x] Als WebAuthn-Bibliothek `laravel/passkeys` (`v0.3.0`) und den offiziellen
  Browser-Client `@laravel/passkeys` (`v0.4.1`) auswählen und locken. Die
  Serverbibliothek unterstützt Laravel 13 und PHP 8.2+; der Browser-Client
  unterstützt Vue. Die Auflösung brachte 13 neue Composer-Pakete ohne Updates
  bestehender Pakete; Composer Audit fand keine Advisories in den neuen Paketen.
- [x] RP-ID und erlaubte Origins explizit konfigurierbar machen. Leerwerte
  leiten RP-ID und Origin aus `APP_URL` ab; mehrere exakte Origins können
  kommasepariert angegeben werden. Der User-Handle-Schlüssel ist stabil an
  `APP_KEY` gebunden und optional separat konfigurierbar. Lokal ergibt sich
  `localhost` sowie `http://localhost:8180`; Dev und Produktion benötigen
  HTTPS, während lokale Entwicklung `http://localhost` als sichere
  Browser-Origin verwenden darf.
- [x] Das `User`-Modell an das Passkey-User-Contract anbinden, einen
  nicht-personenbezogenen HMAC-User-Handle bereitstellen, die Paketmigration
  auf die feste `users`-Tabelle pinnen und Passkeys beim Demo-Anonymisieren
  entfernen.
- [x] Paket-Routen bis zur integrierten Anmeldung vollständig deaktivieren;
  Regressionstests sichern ab, dass Login- und Registrierungsrouten nicht
  vorzeitig erreichbar sind.
- [ ] Die allgemeine Passkey-Option aus Task 4b funktionsfähig in den
  gemeinsamen zweiten Login-Schritt integrieren. Der generische Login verwendet
  discoverable Credentials ohne vorausgehende kontoabhängige Auswahl.
- Registrierung und Anmeldung verlangen eine lokale Benutzerverifikation
  (Geräte-PIN, Biometrie oder gleichwertig). Die Serverprüfung muss das
  User-Verified-Flag kryptografisch validieren; bloß angeforderte Verifikation
  reicht nicht.
- [ ] Registrierung, Mehrfachgeräte, Umbenennung, Widerruf und Login mit
  zweckgebundener, an die Browser-Session gebundener Challenge, Ablauf und
  Replay-Schutz implementieren. Die Session-Challenge ist serverseitig nach
  höchstens 60 Sekunden ungültig; der WebAuthn-Timeout im Browser ist nur eine
  zusätzliche UX-Begrenzung. Erfolg rotiert die Session-ID. Tests decken
  abgelaufene, wiederverwendete und in einer anderen Session eingereichte
  Challenges ab.
- [ ] Fallback auf Passwort/2FA, verlorene Geräte und Admin-Recovery ohne
  Account-Übernahme absichern. Passkey-Login ersetzt weder das
  Benutzername/IP-Limit für Passwortfehlversuche noch die bestehenden
  Recovery- und `must_change_password`-Sperren. Bei Passkey-Login gilt die
  verpflichtende Geräteverifikation als vollständige passwortlose Anmeldung;
  die TOTP-Challenge aus dem Passwortpfad wird nicht zusätzlich verlangt. Die
  erfolgreiche Authentifizierung verwendet eine eigene Login-Antwort, die
  Konten mit `must_change_password` direkt auf das Passwort-Interstitial und
  wiederherzustellende Konten auf die TOTP-Recovery-Seite leitet; diese Gates
  werden nicht über einen abgewiesenen Login umgangen oder unerreichbar.
- Der öffentliche Passkey-Login wird bei späterer Freischaltung auf sechs
  Anfragen pro Minute und Client-IP begrenzt. Verwaltungs-/Registrierungsrouten
  bleiben zusätzlich authentifiziert und erfordern die bestehende
  Passwortbestätigung.
- [ ] API-/Browsergrenzen, HTTPS-Anforderung und Betriebsmaßnahmen im
  technischen und Administratorenhandbuch dokumentieren.

**Senior-Review des Task-5-Plans:** Die erste Prüfung verlangte verpflichtende
User Verification, serverseitige Challenge-Laufzeit, Session-Rotation,
Recovery-/Passwortwechsel-Weiterleitung, eine konkrete IP-Drosselung und die
korrekte `localhost`-Ausnahme. Nach Ergänzung dieser Abnahmekriterien wurde der
Plan im zweiten Re-Review **APPROVED**. Die Implementierungsreviews prüfen
deren tatsächliche Durchsetzung erneut.

**Task 5a — Paket- und Persistenzgrundlage:** Der erste Implementierungsreview
fand, dass die automatische Paketregistrierung bereits eigenständige Login-
und Verwaltungsrouten freischaltet. Diese wurden in `AppServiceProvider`
deaktiviert und durch Regressionstests abgesichert. Die Handbücher erklären
den aktuellen Integrationsstand, Umgebungsvariablen, HTTPS und Migration.
Senior Review 1 forderte die Routen-Härtung; Senior Re-Review 2 genehmigte die
Etappe ohne offene Findings. Der fokussierte Test bestand mit 5 Tests und 35
Assertions; `git diff --check` war sauber.

**Task 5b — Generischer Passkey-Login:** Eigene Optionen- und Prüfendpunkte
verwenden discoverable Credentials ohne Benutzername oder kontoabhängige
Vorab-Auskunft. Die kryptografische Paketprüfung verlangt User Verification.
Die Challenge ist an Session-ID und Zweck gebunden, läuft nach höchstens 60
Sekunden ab und wird nach jedem Einreichungsversuch verbraucht; beide Endpunkte
haben das vereinbarte Limit von sechs Anfragen pro Minute. Erfolgreicher Login
rotiert die Session und lässt zentrale Passwortwechsel- und Recovery-Gates
weiterwirken. Die Registrierung und Verwaltung der Credentials folgt als
nächster Umsetzungsschritt, daher ist die Login-Option bis dahin noch nicht
für Endbenutzer verfügbar.

### Task 6: Gesamtprüfung und Übergabe

- [ ] `./planer dev format` und `./planer dev format:check` ausführen.
- [ ] `./planer dev quality` mit isolierter Testdatenbank ausführen.
- [ ] Relevante Dusk-/Manual-Tests und `./planer dev docs:check` ausführen.
- [ ] Migrations-Rollback, DemoBuilder-Anonymisierung und Recovery-Pfad
  prüfen.
- [ ] Gesamtprüfung, Migrationen, DemoBuilder-Anonymisierung, Recovery-Pfad,
  Dokumentation und Browserabläufe abschließen.
- [ ] Issue #80 mit Implementierung, Tests, Dokumentation und Commits
  aktualisieren; bis zum Abschluss von Task 7 offen lassen.

### Task 7: Wiederkehrende Sicherheitsempfehlung

Diese letzte Produktetappe folgt nach der Passkey-Implementierung und der
Gesamtprüfung. Vor der Umsetzung wird mit dem Auftraggeber abgestimmt, welche
Sicherheitsstrategie Pfarrplaner als nächste empfehlen soll und wann ein Konto
als ausreichend abgesichert gilt.

Vereinbarte Reihenfolge und Erledigungskriterium:

1. Passkey als bevorzugte, phishing-resistente Anmeldung; damit ist zugleich
   passwortlose Anmeldung möglich.
2. TOTP als etablierte Alternative oder zusätzliche zweite Stufe für Konten,
   die Passkeys nicht verwenden können oder wollen.
3. Ein starkes, einzigartiges Passwort bleibt als Rückfallweg erforderlich;
   wo nur Passwortanmeldung eingerichtet ist, empfiehlt die Oberfläche zuerst
   Passkey und danach TOTP.

- Passkey ist die bevorzugte Empfehlung und ermöglicht passwortlose Anmeldung.
- Ein eingerichteter Passkey **oder** TOTP genügt, damit der Hinweis nicht mehr
  als Passwort-only-Konto gilt. TOTP bleibt eine gleichwertige Möglichkeit,
  den Hinweis zu erledigen, auch wenn Passkey zuerst empfohlen wird.
- Nur Konten, die ausschließlich ein Passwort verwenden, erhalten den Hinweis.
- Der Hinweis lässt sich für mehrere Wochen ausblenden und erscheint danach
  erneut, solange weder Passkey noch TOTP eingerichtet ist.

- [x] Reihenfolge und Mindestkriterium für „zusätzlich abgesichert“ mit dem
  Auftraggeber festlegen und im Issue dokumentieren.
- [ ] Ein wiederkehrendes Interstitial für Konten erstellen, die ausschließlich
  ein Passwort verwenden. Es erklärt die empfohlene nächste Maßnahme und führt
  direkt zu deren Einrichtung.
- [ ] Den Hinweis für mehrere Wochen ausblendbar machen; nach Ablauf erscheint
  er erneut, solange weder Passkey noch TOTP eingerichtet ist.
- [ ] Snooze, Wiederkehr, Einrichtungslinks, Statusänderungen und bereits
  abgesicherte Konten mit fokussierten Tests abdecken und in Benutzer- sowie
  Administratorenhandbuch dokumentieren.
- [ ] Issue #80 nach Implementierung, Senior Review und erfolgreicher
  Gesamtverifikation abschließen.
