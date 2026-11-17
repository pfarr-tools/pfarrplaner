# Profil-Sicherheitsdialoge Umsetzungsplan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Schritte werden einzeln verifiziert, reviewed und committed.

**Ziel:** TOTP-Deaktivierung und Passkey-Neuregistrierung bei abgelaufener Passwortbestätigung mit einem gemeinsamen Passwortdialog im Profil ermöglichen und die lokale Vorschau des Sicherheitshinweises verständlich dokumentieren.

**Architektur:** Eine zugängliche Vue-Dialogkomponente kapselt Passwortfeld, Formularzustände und Abbrechen. `SecurityTab` nutzt sie für beide Aktionen; der JSON-Pfad des Passwortbestätigungscontrollers gibt maschinenlesbare Erfolgs- und Validierungsantworten zurück, während normale Formularnavigation erhalten bleibt. Die bestehende lokale Interstitial-Unterdrückung wird nicht verändert; `?show_interstitial=1` wird als Vorschauweg dokumentiert.

**Tech Stack:** Laravel 11, Inertia.js, Vue, Axios, PHPUnit, Vitest.

**Spezifikation:** `docs/superpowers/specs/2026-11-17-security-profile-dialogs-design.md`

## Globale Vorgaben

- Bestehende Passwortprüfung, Rate-Limits, Berechtigungen, 21-Tage-Aufschub und Produktionsbedingungen bleiben erhalten.
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
- Sicherheitsinterstitial bleibt für ausstehendes TOTP, erzwungenen Passwortwechsel, aktive Faktoren und gültigen Aufschub unterdrückt.

---

### Etappe 1: JSON-Antwort für Passwortbestätigung

**Dateien:**
- Ändern: `src/app/Http/Controllers/Auth/ConfirmPasswordController.php`
- Testen: `src/tests/Feature/PasskeyManagementFeatureTest.php`

- [ ] Featuretests für korrekte und falsche JSON-Passwortbestätigung ergänzen; Erfolg erwartet JSON `confirmed: true` und `auth.password_confirmed_at`, Fehler erwartet `422`, Feldfehler und keinen Zeitstempel.
- [ ] Isolierte Tests vor der Implementierung ausführen und den Fehlernachweis festhalten.
- [ ] `store()` für JSON-Anfragen um strukturierte Erfolgs-/Validierungsantworten ergänzen; Redirect- und Sessionfehler-Verhalten normaler Formulare unverändert lassen.
- [ ] Fokussierte Featuretests, Formatprüfung und `git diff --check` ausführen; Senior Review, Issue-Kommentar und Commit.

### Etappe 2: Wiederverwendbarer Passwortdialog und Profilabläufe

**Dateien:**
- Erstellen: `src/resources/js/components/Profile/PasswordConfirmationDialog.vue`
- Ändern: `src/resources/js/components/Profile/Tabs/SecurityTab.vue`
- Testen: `src/tests/js/components/Profile/SecurityTab.test.js`

- [ ] Vitest-Fälle für Öffnen/Abbrechen/Geheimnisbereinigung, TOTP-Widerruf mit Dialog, fehlerhaftes Passwort, Throttling sowie Passkey-`423`-Bestätigung und ausdrücklichen Neustart ergänzen.
- [ ] Tests isoliert vor der UI-Änderung ausführen und Fehlernachweis festhalten.
- [ ] Dialog mit beschriftetem Passwortfeld, Fokussteuerung, Escape/Abbrechen, Ladezustand und Fehleranzeige erstellen; Passwort direkt nach Submit oder Abbruch löschen.
- [ ] Inline-TOTP-Passwortfeld entfernen und Widerrufsschaltfläche an den Dialog koppeln.
- [ ] Passkey-`423` öffnet den Dialog; erfolgreicher JSON-POST schließt ihn und zeigt den bestätigten Zustand. Die Passkey-Erstellung läuft erst nach erneutem Klick.
- [ ] Fokussierte Vitest-Suite, Format-/Lintprüfung und `git diff --check` ausführen; Senior Review, Issue-Kommentar und Commit.

### Etappe 3: Handbücher und Interstitial-Vorschau

**Dateien:**
- Prüfen/ändern: Benutzer-, Administratoren- und Technisches Handbuch in `docs/`
- Prüfen/ändern: `docs/superpowers/plans/2026-11-15-passwortverwaltung.md`
- Testen: `src/tests/Unit/Services/InterstitialServiceUnitTest.php`, `src/tests/Feature/InterstitialFeatureTest.php`, falls bestehende Abdeckung den Vorschaupfad nicht bereits belegt

- [ ] Bestehende Tests zur lokalen Unterdrückung und zu `?show_interstitial=1` prüfen; nur bei fehlender Abdeckung einen gezielten Test ergänzen.
- [ ] Benutzerhandbuch um Dialogabläufe und den erneuten Klick zum Starten der Passkey-Abfrage ergänzen.
- [ ] Administratorenhandbuch auf geänderte Bedien- oder Betriebsanforderungen prüfen und die Nichtänderung begründen, falls nichts angepasst werden muss.
- [ ] Technisches Handbuch um JSON-Passwortbestätigung und den lokalen Vorschauparameter ergänzen.
- [ ] `./planer dev docs:check`, relevante fokussierte Tests, `git diff --check` und passende Formatprüfung ausführen; Senior Review, vollständigen Issue-Bericht und Commit.

## Umsetzungshinweise

- Den JSON-Pfad am bestehenden `expectsJson()` festmachen; Redirectanfragen müssen ihr bisheriges Verhalten behalten.
- Die Passkey-Schaltfläche nach bestätigtem Passwort wieder fokussieren, damit der notwendige nächste Klick klar und per Tastatur erreichbar ist.
- Keine Passwortwerte in Fehlermeldungen, Ereignisse, Logs oder persistente Komponenten-Props übernehmen.
