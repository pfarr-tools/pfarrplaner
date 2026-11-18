# Profil-Sicherheitsdialoge Umsetzungsplan

> **Planänderung vom 18.11.2026:** Die nachstehenden ursprünglichen Etappen zur Passwortbestätigung im Profil sind durch die bestätigte Entscheidung ersetzt: sämtliche Passwort-UI und -Anforderungen aus dem Profil entfernen, Passwortänderung ausschließlich über `/passwort/aendern`, persönliche Passkey- und TOTP-Verwaltung ohne erneute Passwortabfrage. Anmeldung, Passkey-Eigentum, User Verification, frische Registrierungschallenge, TOTP-Codeprüfung und der getrennte Super-Admin-Recovery-Ablauf bleiben erhalten. Die historischen Etappen unten dokumentieren den bisherigen Umsetzungsstand; ihre Passwortbestätigungs-Punkte sind erledigt und werden durch die abschließenden Änderungsaufgaben ergänzt.

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Schritte werden einzeln verifiziert, reviewed und committed.

**Ziel:** Kontosicherheit verständlich und bedienbar machen: Passkey- und TOTP-Verwaltung ohne Passwort-UI im Profil, verständlicher Sicherheitshinweis und passwortloser Login über Passkey oder einmaligen E-Mail-Link.

**Architektur:** `SecurityTab` bietet Passkey- und TOTP-Verwaltung innerhalb der angemeldeten Sitzung. TOTP wird durch einen gültigen App-Code bestätigt, die Deaktivierung durch eine einfache Klickbestätigung. Passwortänderung bleibt auf der eigenständigen Seite `/passwort/aendern`. Das Sicherheitshinweis-Interstitial erklärt Passkey und Authenticator-App und speichert den 21-Tage-Aufschub vor der Navigation. Der passwortlose Login zeigt zuerst Passkey, danach die E-Mail-Link-Option. E-Mail-Links verwenden einen einmaligen, gehashten Nonce und eine tokenfreie Bestätigungsseite; Kontoabfrage und Mailjob laufen nach der generischen Antwort. Bei TOTP-Konten folgt die bestehende TOTP-Challenge; E-Mail-Codes sind kein Ersatzfaktor.

**Tech Stack:** Laravel 11, Inertia.js, Vue, Axios, PHPUnit, Vitest.

**Spezifikation:** `docs/superpowers/specs/2026-11-17-security-profile-dialogs-design.md`

## Globale Vorgaben

- Authentifizierung, Passkey-Eigentumsprüfung, User Verification, frische Registrierungschallenge, TOTP-Codeprüfung, übrige Rate-Limits, Berechtigungen, 21-Tage-Aufschub und Produktionsbedingungen bleiben erhalten. Passwörter bleiben auf die eigenständigen Passwortwechsel- und Super-Admin-Recovery-Abläufe begrenzt.
- Der Login-Link nutzt einen kryptografisch zufälligen 256-Bit-Nonce, wird in der DB nur als SHA-256-Hash und im Queue-Mailjob verschlüsselt gespeichert, läuft nach zehn Minuten ab und wird bei Bestätigung atomar genau einmal verbraucht; GET verbraucht nichts.
- E-Mail-Link-Anforderungen verwenden identische Antworten für vorhandene und unbekannte Konten und werden pro IP und normalisierter E-Mail begrenzt.
- Bei aktiviertem TOTP verlangt der E-Mail-Link weiterhin TOTP; im Challenge-Schritt gibt es keinen E-Mail-Code als TOTP-Ersatz.
- Die bestehende `two_factor_login_challenges`-Migration bleibt erhalten; laufende Erzeugung/Prüfung von E-Mail-TOTP-Codes wird entfernt und Legacy-Cleanup bleibt bestehen.
- Das Passwort wird nie dauerhaft im Parent- oder Komponentenstatus gespeichert und nach jeder Antwort bzw. jedem Abbruch gelöscht.
- Passkey-Registrierung wird nach Bestätigung nicht automatisch neu gestartet; ein erneuter Klick löst die Browser-API aus.
- Tests laufen ausschließlich isoliert über den vorgesehenen Docker-Workflow; keine Dev- oder Produktionsdatenbank verwenden.
- Deutsche sichtbare Texte und alle drei Handbücher auf Konsistenz prüfen.
- Nach jeder Etappe Review, Issue-Kommentar, fokussierte Verifikation und eigener Commit.

## Review-Fokus

- Falsches Passwort im JSON-Bestätigungspfad muss als `422` mit Feldfehler erscheinen, ohne die Sitzung als bestätigt zu markieren.
- Erfolgreiche JSON-Bestätigung muss denselben Zeitstempel setzen wie der bestehende Browser-Redirectpfad.
- TOTP-Widerruf muss bei falschem Passwort oder Rate-Limit aktiviert bleiben und das Passwort löschen.
- Passkey-Dialogabschluss darf nicht ohne erneuten Klick `navigator.credentials.create()` auslösen.
- Sicherheitsinterstitial bleibt für ausstehende TOTP, erzwungenen Passwortwechsel, aktive Faktoren und gültigen Aufschub unterdrückt; Microsoft Authenticator und 2FAS Auth sind als Beispiele verständlich verlinkt.
- Primärbutton speichert den Aufschub vor dem Profilaufruf; Konten ohne Passkey/TOTP erhalten den Hinweis unabhängig vom passwortlosen E-Mail-Login erneut nach dem Aufschub.
- TOTP-Schlüssel steht ohne Aufklappen direkt unter dem QR-Code; TOTP- und Login-Aktionspaare verwenden gemeinsame Flex-Zeilen.
- Der manuelle Schlüssel hat einen eigenen Kopierbutton; Recovery-Codes stehen newline-getrennt in einem schreibgeschützten Textfeld und lassen sich zusammen kopieren.
- Die passwortlose Loginseite zeigt Passkey zuerst, danach „oder“ und den E-Mail-Anmeldelink jeweils mit voller Eingabefeldbreite, Symbol und ohne Passwortfeld/Passwortreset-Link.
- E-Mail-Link-GETs verbrauchen keine Tokens, unbekannte Konten werden nicht offengelegt, TOTP-Challenges akzeptieren keinen E-Mail-Code.

---

### Etappe 1: JSON-Antwort für Passwortbestätigung

**Dateien:**
- Ändern: `src/app/Http/Controllers/Auth/ConfirmPasswordController.php`
- Testen: `src/tests/Feature/PasskeyManagementFeatureTest.php`

- [x] Featuretests für korrekte und falsche JSON-Passwortbestätigung ergänzen; Erfolg erwartet JSON `confirmed: true` und `auth.password_confirmed_at`, Fehler erwartet `422`, Feldfehler und keinen Zeitstempel.
- [x] Isolierte Tests vor der Implementierung ausführen und den Fehlernachweis festhalten.
- [x] `store()` für JSON-Anfragen um strukturierte Erfolgs-/Validierungsantworten ergänzen; Redirect- und Sessionfehler-Verhalten normaler Formulare unverändert lassen.
- [x] Fokussierte Featuretests, Formatprüfung und `git diff --check` ausführen; Senior Review, Issue-Kommentar und Commit.

### Etappe 2: Wiederverwendbarer Passwortdialog und Profilabläufe

**Dateien:**
- Erstellen: `src/resources/js/components/Profile/PasswordConfirmationDialog.vue`
- Ändern: `src/resources/js/components/Profile/Tabs/SecurityTab.vue`
- Testen: `src/tests/js/components/Profile/SecurityTab.test.js`

- [x] Vitest-Fälle für Öffnen/Abbrechen/Geheimnisbereinigung, TOTP-Widerruf mit Dialog, fehlerhaftes Passwort, Throttling sowie Passkey-`423`-Bestätigung und ausdrücklichen Neustart ergänzen.
- [x] Tests isoliert vor der UI-Änderung ausführen und Fehlernachweis festhalten.
- [x] Dialog mit beschriftetem Passwortfeld, Fokussteuerung, Escape/Abbrechen, Ladezustand und Fehleranzeige erstellen; Passwort direkt nach Submit oder Abbruch löschen.
- [x] Inline-TOTP-Passwortfeld entfernen und Widerrufsschaltfläche an den Dialog koppeln.
- [x] Passkey-`423` öffnet den Dialog; erfolgreicher JSON-POST schließt ihn und zeigt den bestätigten Zustand. Die Passkey-Erstellung läuft erst nach erneutem Klick.
- [x] Fokussierte Vitest-Suite, Formatprüfung und `git diff --check` ausführen; Senior Review, Issue-Kommentar und Commit.

### Etappe 3: Handbücher und Interstitial-Vorschau

**Dateien:**
- Prüfen/ändern: Benutzer-, Administratoren- und Technisches Handbuch in `docs/`
- Prüfen/ändern: `docs/superpowers/plans/2026-11-15-passwortverwaltung.md`
- Testen: `src/tests/Unit/Services/InterstitialServiceUnitTest.php`, `src/tests/Feature/InterstitialFeatureTest.php`, falls bestehende Abdeckung den Vorschaupfad nicht bereits belegt

- [x] Bestehende Tests zur lokalen Unterdrückung und zu `?show_interstitial=1` prüfen.
- [x] Interstitial-Overlay um sicher verlinkte App-Empfehlungen erweitern; Empfehlungstext in `src/config/interstitials.php` so ausformulieren, dass Passkey, Authenticator-App, sechsstelliger wechselnder Code, TOTP-Begriff und Wiederherstellungscodes ohne Vorwissen verständlich sind.
- [x] Benutzerhandbuch um Dialogabläufe und den erneuten Klick zum Starten der Passkey-Abfrage ergänzen.
- [x] Administratorenhandbuch auf geänderte Bedien- oder Betriebsanforderungen prüfen und die Nichtänderung begründen, falls nichts angepasst werden muss.
- [x] Technisches Handbuch um JSON-Passwortbestätigung und den lokalen Vorschauparameter ergänzen.
- [x] `./planer dev docs:check`, relevante fokussierte Tests, `git diff --check` und passende Formatprüfung ausführen; Senior Review, vollständigen Issue-Bericht und Commit.

### Ergänzung nach UI-Rückmeldung: sichtbarer Schlüssel, Aktionszeilen und Snooze-Regressionsschutz

**Dateien:**
- Ändern: `src/resources/js/components/Profile/Tabs/SecurityTab.vue`, `src/resources/js/Pages/Auth/Login.vue`, `src/resources/js/Pages/Auth/LoginChallenge.vue`
- Testen: `src/tests/js/components/Profile/SecurityTab.test.js`, `src/tests/js/components/Auth/Login.test.js`, `src/tests/js/components/Auth/LoginChallenge.test.js`, `src/tests/js/components/Ui/InterstitialOverlay.test.js`, `src/tests/Feature/InterstitialFeatureTest.php`, `src/tests/Unit/Services/InterstitialServiceUnitTest.php`

- [x] Den manuellen TOTP-Schlüssel direkt unter dem QR-Code zeigen und Bitwarden als Beispiel nennen.
- [x] Kopierbutton für den manuellen Schlüssel und für alle Recovery-Codes ergänzen; Recovery-Codes in schreibgeschütztem Textfeld ausgeben und den Kopierstatus anzeigen.
- [x] TOTP-Assistent sowie Passwort-/Passkey-Login und Login-Challenge mit gemeinsamen responsiven Aktionszeilen versehen.
- [x] Regressionstests ergänzen: Snooze-POST vor Navigation, sofortiges Ausblenden, persistente 21-Tage-Unterdrückung bei Passwort-only-Konten, sichtbarer manueller Schlüssel und gemeinsame Aktionszeilen.
- [x] Fokussierte PHP- und JavaScript-Tests, Build, Cachebereinigung und Format-/Dokumentationsprüfung abschließen; Senior Reviews, Issue-Bericht und Commit. Letzte Kopier-UI-Prüfung: 19 SecurityTab-Tests bestanden; zwei Review-Findings (Screenreader-Live-Ansage und Clipboard-Mock-Cleanup) korrigiert und senior-reviewed **APPROVED**.

### Ergänzung: Passkey- und E-Mail-Link-Login ohne Passwortfeld

**Dateien:**
- Erstellen: `src/app/Http/Controllers/Auth/EmailLoginLinkController.php`, `src/app/Mail/User/LoginLink.php`, `src/resources/js/Pages/Auth/ConfirmLoginLink.vue`, Migration für `passwordless_login_links`, Mailable-View und `src/tests/Feature/EmailLoginLinkFeatureTest.php`.
- Ändern: `src/routes/web/Auth.php`, `src/app/Http/Controllers/Auth/LoginController.php`, `src/app/Providers/AppServiceProvider.php`, `src/app/Providers/TelescopeServiceProvider.php`, `src/resources/js/Pages/Auth/Login.vue`, `src/resources/js/Pages/Auth/LoginChallenge.vue`, vorhandene Login-/Challenge-Tests und die drei Handbücher.
- Entfernen: Challenge-E-Mail-Code-Route und -Sender, die aktive Mailable-/View-Nutzung sowie deren Login-Challenge-UI. Die Migrationstabelle und Legacy-Bereinigung bleiben, damit bereits ausgeführte Migrationen und offene Bestandsdatensätze kompatibel bleiben.

#### Etappe 4: E-Mail-Link-Backend

- [x] Featuretests zuerst schreiben: generische Antwort für bekannte/unbekannte Adressen, Rate-Limits, gehashter 10-Minuten-Token, GET ohne Verbrauch, bestätigter einmaliger Login, TOTP-Challenge, Ablauf und Replay.
- [x] `passwordless_login_links` mit `user_id`, `token_hash`, `expires_at`, `consumed_at` und Timestamps migrieren; alte aktive/abgelaufene Challenge-Tabelle nicht verändern.
- [x] `EmailLoginLinkController::request`, `open` und `confirm` sowie die `login.link.*`-Routen implementieren. GET bindet einen gültigen Link an die Session und leitet auf eine tokenfreie Bestätigungsseite; POST verbraucht ihn atomar.
- [x] `LoginLink`-Mailable samt No-Referrer/No-Store-Bestätigungsseite implementieren und Telescope-Redaktion für Linkgeheimnisse testen.
- [x] Nach Bestätigung ohne TOTP regulär anmelden; bei TOTP ausschließlich die vorhandene TOTP-Challenge starten.
- [x] Isolierte Featuretests, Format-/Dokumentationsprüfung, Senior Review und Issue-Kommentar: Die Link-Featuretests belegen generische Antworten, Limits, Hashspeicherung, Ablauf, GET-ohne-Verbrauch, einmalige Bestätigung, TOTP-Challenge und Replay-Schutz.

#### Etappe 5: E-Mail-Code als TOTP-Ersatz entfernen

- [x] Tests für TOTP- und Recovery-Code-Verifikation erhalten; Tests für das E-Mail-Code-Senden/-Verifizieren entfernen und durch Regressionen ersetzen, dass die Route nicht existiert und ein E-Mail-Code die Challenge nicht abschließt.
- [x] `login.challenge.email`, `sendEmailChallenge`, `consumeEmailCode`, Code-Mailable/-View und E-Mail-Eingabemodus aus der Challenge entfernen.
- [x] Challenge-Seite und Benutzer-/Administrator-/Technikhandbuch auf Authenticator- und Recovery-Code-Abläufe reduzieren; Legacy-Tabellenbereinigung bestehen lassen.
- [x] Fokussierte Feature-/Vitest-Läufe, Senior Review und Issue-Bericht. TOTP-/Recovery-Code-Challenge bleibt der einzige zweite Schritt nach E-Mail-Link-Login mit TOTP.

#### Etappe 6: Login-Oberfläche

- [x] Vitest zuerst ergänzen: Passwort-/Reset-Link fehlen; Passkey-Primärbutton mit Erklärung und Fingerabdrucksymbol, danach die E-Mail-Eingabe direkt über dem E-Mail-Link-Button mit Briefsymbol, gleiche volle Breite und verständliche Lade-/Erfolg-/Fehlerzustände.
- [x] Reguläre Loginseite auf Passkey zuerst, dann „oder“, E-Mail-Adresse und Anmeldelink reduzieren; Demo-Sonderlogin beibehalten. Passkey bleibt ohne Kontenabfrage discoverable; E-Mail-Aktion holt einen frischen CSRF-Token und sendet ihn mit der Adresse an `login.link.request`.
- [x] Login-Link-Bestätigungsseite und TOTP-/Recovery-Challenge mit Tastaturbedienung und verständlichen deutschen Texten abschließen; Passkey-Abbrüche und nicht unterstützte Browser verständlich erklären und den E-Mail-Link als Alternative nennen.
- [x] Handbücher aktualisieren, fokussierte PHP-/Vitest-Tests, Format, `docs:check`, Build und Cachebereinigung ausführen; Senior Review, Issue-Kommentar und Etappen-Commit abschließen. Verifiziert: 101 isolierte PHP-Tests mit 711 Assertions, 14 Vitest-Tests, Produktionsbuild mit 2.308 Modulen, `docs:check`, `git diff --check` und `optimize:clear`; Senior Gesamt-Review **APPROVED**. Der Branch-Push folgt unmittelbar nach diesem finalen Plan-Commit.

## Umsetzungshinweise

- Den JSON-Pfad am bestehenden `expectsJson()` festmachen; Redirectanfragen müssen ihr bisheriges Verhalten behalten.
- Die Passkey-Schaltfläche nach bestätigtem Passwort wieder fokussieren, damit der notwendige nächste Klick klar und per Tastatur erreichbar ist.
- Keine Passwortwerte in Fehlermeldungen, Ereignisse, Logs oder persistente Komponenten-Props übernehmen.


### Abschließende Planänderung: Passwortfunktionen aus dem Profil entfernen

- [x] Sicherheitsreiter und Profileditor von Passwortfeldern, Passwortanforderungen und Passwort-Payload befreien.
- [x] Passwortänderung im Profil-Speicher-Endpunkt ausschließen; separates `/passwort/aendern` für reguläre und erzwungene Änderungen erhalten.
- [x] `password.confirm`-Gate aus persönlichen Passkey-Routen entfernen; Authentifizierung, Besitzprüfung, frische Challenge und User Verification erhalten.
- [x] TOTP Start/Bestätigung/Deaktivierung ohne Kontopasswort ermöglichen; Codeprüfung und Rate-Limit erhalten, Deaktivierung per Fehlklickbestätigung schützen.
- [x] Veraltete Profil-Passwortdialoge, Controller, Route und Seite entfernen; Benutzer-, Administrator- und Technikhandbücher aktualisieren.
