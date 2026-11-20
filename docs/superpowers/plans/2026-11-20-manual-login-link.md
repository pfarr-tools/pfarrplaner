# Manuellen Super-Admin-Anmeldelink erstellen – Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. No subagents.

**Goal:** Super-Admins können für ein anderes aktives Konto einen einmaligen Anmeldelink erzeugen, kopieren und innerhalb einer Stunde verwenden.

**Architecture:** Der Admin-Endpunkt erzeugt einen kryptografisch zufälligen Token, speichert ausschließlich dessen SHA-256-Hash mit Ablaufzeit in `passwordless_login_links` und gibt die Login-URL einmalig als nicht cachebare JSON-Antwort zurück. Die bestehende Link-Bestätigung, Sperrprüfung und TOTP-Abfrage bleiben unverändert; der Endpunkt sendet keine E-Mail. Telescope zeichnet weder den Erzeugungsaufruf noch dessen URL-Antwort auf.

**Tech Stack:** Laravel 11 / PHP 8.4, Inertia/Vue, bestehende `passwordless_login_links`-Tabelle und PHPUnit/Vitest.

**Spec:** [GitHub Issue #125](https://github.com/pfarr-tools/pfarrplaner/issues/125)

## Global Constraints

- Nur Super-Admins dürfen Links für andere Nicht-Super-Admin-Konten erzeugen.
- Gesperrte oder nicht mehr aktive Konten erhalten keinen Link.
- Der Link ist einmalig; offene ältere Login-Links desselben Kontos werden beim Erzeugen verbraucht.
- Ablaufzeit ist exakt 60 Minuten nach Erzeugung und wird UTC-basiert gespeichert.
- Klartext-Token wird weder gespeichert noch per E-Mail versendet oder in Telescope aufgezeichnet.
- Vorhandene TOTP-Prüfung und sichere Login-Bestätigung bleiben erhalten.
- PHPUnit läuft nur auf einer vorab geprüften SQLite-`:memory:`-Verbindung.

## Review Focus

- Unberechtigte, eigene, Super-Admin-, gesperrte und entfernte Konten: Erzeugung muss verweigert werden.
- 60-Minuten-Ablauf: DB-Zeitstempel muss exakt eine Stunde nach Erzeugung liegen.
- Einmalverwendung und parallele ältere Links: alte Links werden verbraucht; ein Link kann nur eine Anmeldung abschließen.
- TOTP-geschützte Konten: der Link muss weiterhin in die bestehende TOTP-Challenge führen.
- Klartext-Token: nur die direkte, nicht cachebare Antwort enthält ihn; Datenbank, E-Mail und Telescope-Aufzeichnung nicht.
- UI-Fehlerfälle: fehlgeschlagene Erzeugung oder Clipboard-Zugriff zeigt eine verständliche Rückmeldung.

## Dateiübersicht

- `src/app/Actions/Security/`: transaktionale und autorisierte Link-Erzeugung.
- `src/app/Http/Controllers/UserController.php`, `src/routes/web/admin/User.php`: geschützter JSON-Endpunkt.
- `src/app/Providers/TelescopeServiceProvider.php`: sensible Erzeugungsantwort aus Telescope ausschließen.
- `src/resources/js/Pages/Admin/User/ManualLoginLinkControl.vue`: Erzeugen, einmaliges Anzeigen, Ablaufhinweis und Kopieren.
- `src/resources/js/Pages/Admin/User/UserEditor.vue`: Kontrolle im Abschnitt „Kontosicherheit“ einbinden.
- `src/tests/Feature/ManualLoginLinkFeatureTest.php`, `src/tests/js/Pages/Admin/User/ManualLoginLinkControl.test.js`: Backend- und UI-Regressionen.
- `docs/manual/administratorhandbuch/sicherheit.md`, `docs/manual/technikhandbuch/api-authentifizierung.md`: Ablauf und Sicherheitsgrenzen dokumentieren.

## Tasks

### Task 1: Autorisierte Link-Erzeugung

- [x] Schreibe Feature-Tests für erfolgreiche Erstellung mit 60-Minuten-Ablauf, Hash-Speicherung, Verbrauch älterer Links und ohne Mailversand.
- [x] Schreibe Ablehnungstests für Nicht-Super-Admin, Selbstbedienung, Ziel-Super-Admin, gesperrtes und konto-loses Ziel.
- [x] Prüfe den Red-Lauf mit explizit `APP_ENV=testing`, SQLite und `DB_DATABASE=:memory:`.
- [x] Implementiere eine Security-Action, die das Ziel unter Zeilensperre erneut prüft, frühere offene Links verbraucht und den neuen Nonce-Hash mit UTC-Ablaufzeit speichert.
- [x] Ergänze den Super-Admin-Endpunkt mit `Cache-Control: no-store`, `Pragma: no-cache` und `Referrer-Policy: no-referrer`; verknüpfe ihn mit der Aktion und gib nur die URL sowie Ablaufzeit zurück.
- [x] Schließe den Erzeugungsendpunkt von Telescope aus und verberge das JSON-Feld mit der URL zusätzlich in Response-Aufzeichnungen.
- [x] Führe die fokussierten Feature-Tests erneut in der isolierten Datenbank aus.

### Task 2: UI mit Copy-Funktion

- [x] Schreibe Vitest-Tests für Erzeugen, Anzeige von URL/Ablaufzeit, Kopieren und verständliche Fehlerbehandlung.
- [x] Bestätige den Red-Lauf, bevor die Komponente erstellt wird.
- [x] Implementiere `ManualLoginLinkControl.vue` mit Button, schreibgeschütztem Linkfeld, Ablaufhinweis, Clipboard-API und manuell auswählbarer Fallback-Anzeige.
- [x] Binde die Komponente im `UserEditor` unter „Kontosicherheit“ für berechtigte, aktive Konten ein.
- [x] Führe den fokussierten Vitest-Lauf und den Vite-Build aus.

### Task 3: Dokumentation und Abschlussprüfung

- [x] Ergänze Admin- und Technikhandbuch um Ablauf, Einmalverwendung, 60-Minuten-Frist und fortbestehende TOTP-/Sperrprüfungen.
- [x] Prüfe die geänderten Dateien mit Pint, Prettier, JSDoc-Prüfung, PHPStan und `git diff --check`.
- [x] Führe die relevanten isolierten PHPUnit- und Vitest-Suites aus und prüfe den vollständigen Diff.
- [ ] Committe mit deutschem Conventional Commit und `Fixes #125`, pushe den Branch und schließe Issue #125 nach erfolgreicher Verifikation.
