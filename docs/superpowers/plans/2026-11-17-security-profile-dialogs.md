# Profil-Sicherheitsdialoge Umsetzungsplan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Schritte werden einzeln verifiziert, reviewed und committed.

**Ziel:** TOTP-Deaktivierung und Passkey-Neuregistrierung bei abgelaufener Passwortbestätigung mit einem gemeinsamen Passwortdialog im Profil ermöglichen und den Sicherheitshinweis für nicht-technische Benutzer:innen verständlich erklären.

**Architektur:** Eine zugängliche Vue-Dialogkomponente kapselt Passwortfeld, Formularzustände und Abbrechen. `SecurityTab` nutzt sie für beide Aktionen; der JSON-Pfad des Passwortbestätigungscontrollers gibt maschinenlesbare Erfolgs- und Validierungsantworten zurück, während normale Formularnavigation erhalten bleibt. Das Sicherheitshinweis-Interstitial erklärt Passkey und Authenticator-App in Alltagssprache, verlinkt offizielle App-Informationen und behält seine bestehende lokale Vorschau mit `?show_interstitial=1`. Der TOTP-Schlüssel ist immer sichtbar; zusammengehörige Aktionsbuttons stehen in responsiven Zeilen. Der Interstitial-Primärbutton speichert den 21-Tage-Aufschub vor der Navigation.

**Tech Stack:** Laravel 11, Inertia.js, Vue, Axios, PHPUnit, Vitest.

**Spezifikation:** `docs/superpowers/specs/2026-11-17-security-profile-dialogs-design.md`

## Globale Vorgaben

- Bestehende Passwortprüfung, Rate-Limits, Berechtigungen, 21-Tage-Aufschub und Produktionsbedingungen bleiben erhalten.
- Der Login-Link hat ein 10-Minuten-Limit, wird gehasht und atomar einmalig verbraucht; seine GET-Ankunft verbraucht nichts.
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
- Primärbutton speichert den Aufschub vor dem Profilaufruf; bei weiterhin Passwort-only-Konten bleiben die Interstitial-Props bis zum Ablauf leer.
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

- [ ] Featuretests zuerst schreiben: generische Antwort für bekannte/unbekannte Adressen, Rate-Limits, gehashter 10-Minuten-Token, GET ohne Verbrauch, bestätigter einmaliger Login, TOTP-Challenge, Ablauf und Replay.
- [ ] `passwordless_login_links` mit `user_id`, `token_hash`, `expires_at`, `consumed_at` und Timestamps migrieren; alte aktive/abgelaufene Challenge-Tabelle nicht verändern.
- [ ] `EmailLoginLinkController::request`, `open` und `confirm` sowie die `login.link.*`-Routen implementieren. GET bindet einen gültigen Link an die Session und leitet auf eine tokenfreie Bestätigungsseite; POST verbraucht ihn atomar.
- [ ] `LoginLink`-Mailable samt No-Referrer/No-Store-Bestätigungsseite implementieren und Telescope-Redaktion für Linkgeheimnisse testen.
- [ ] Nach Bestätigung ohne TOTP regulär anmelden; bei TOTP ausschließlich die vorhandene TOTP-Challenge starten.
- [ ] Isolierte Featuretests, Format-/Dokumentationsprüfung, Senior Review, Issue-Kommentar und Etappen-Commit.

#### Etappe 5: E-Mail-Code als TOTP-Ersatz entfernen

- [ ] Tests für TOTP- und Recovery-Code-Verifikation erhalten; Tests für das E-Mail-Code-Senden/-Verifizieren entfernen und durch Regressionen ersetzen, dass die Route nicht existiert und ein E-Mail-Code die Challenge nicht abschließt.
- [ ] `login.challenge.email`, `sendEmailChallenge`, `consumeEmailCode`, Code-Mailable/-View und E-Mail-Eingabemodus aus der Challenge entfernen.
- [ ] Challenge-Seite und Benutzer-/Administrator-/Technikhandbuch auf Authenticator- und Recovery-Code-Abläufe reduzieren; Legacy-Tabellenbereinigung bestehen lassen.
- [ ] Fokussierte Feature-/Vitest-Läufe, Senior Review, Issue-Bericht und Etappen-Commit.

#### Etappe 6: Login-Oberfläche

- [ ] Vitest zuerst ergänzen: Passwort-/Reset-Link fehlen; E-Mail-Eingabe, Passkey-Primärbutton mit Fingerabdrucksymbol, zentriertes „oder“, E-Mail-Link-Button mit Briefsymbol, gleiche volle Breite und Lade-/Erfolg-/Fehlerzustände.
- [ ] Reguläre Loginseite auf E-Mail + zwei Aktionen reduzieren; Demo-Sonderlogin beibehalten. Passkey bleibt ohne Kontenabfrage discoverable; E-Mail-Aktion postet die Adresse an `login.link.request`.
- [ ] Login-Link-Bestätigungsseite und TOTP-/Recovery-Challenge mit Tastaturbedienung und verständlichen deutschen Texten abschließen.
- [ ] Handbücher aktualisieren, fokussierte PHP-/Vitest-Tests, Format, `docs:check`, Build und Cachebereinigung ausführen; Senior Review, Issue-Kommentar, Commit und Push.

## Umsetzungshinweise

- Den JSON-Pfad am bestehenden `expectsJson()` festmachen; Redirectanfragen müssen ihr bisheriges Verhalten behalten.
- Die Passkey-Schaltfläche nach bestätigtem Passwort wieder fokussieren, damit der notwendige nächste Klick klar und per Tastatur erreichbar ist.
- Keine Passwortwerte in Fehlermeldungen, Ereignisse, Logs oder persistente Komponenten-Props übernehmen.
