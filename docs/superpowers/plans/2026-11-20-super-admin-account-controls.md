# Super-Admin-Kontosteuerung – Umsetzungsplan

> **Für agentische Ausführung:** Änderungen werden in kleinen testgetriebenen Schritten umgesetzt. Keine Unteragenten.

**Ziel:** Super-Administrator:innen können bei bestehenden Personen 2FA zurücksetzen, Passkeys widerrufen, Webkonten sperren/entsperren und Konten unter Erhalt der Personendaten entfernen.

**Architektur:** Das Sperrkennzeichen wird getrennt vom Status „Person ohne Konto“ gespeichert. Es blockiert Web-Logins, aber ausdrücklich keinen DAV-Zugang. Das Entfernen eines Kontos widerruft alle konto- und sicherheitsbezogenen Anmeldedaten einschließlich DAV-Token und offener Web-Login-Tokens. Jede Mutation wird serverseitig als Super-Admin-Aktion autorisiert; die vorhandene 2FA-Wiederherstellung bleibt bestehen.

**Technik:** Laravel 11, PHP 8.4, Inertia/Vue, PHPUnit 11, bestehende Laravel-Passkeys- und 2FA-Pakete.

**Spezifikation:** Freigegebener Umfang in [GitHub Issue #113](https://github.com/pfarr-tools/pfarrplaner/issues/113) und dessen Umsetzungsplan-Kommentar.

## Globale Vorgaben

- PHP-/Vue-Deklarationen erhalten aussagekräftige PHPDoc-/JSDoc-Blöcke.
- PHPUnit läuft ausschließlich mit isolierter Testdatenbank.
- Deutsche Oberflächentexte und Handbuchtexte verwenden korrekte Umlaute und `ß`.
- Keine Änderungen am Demo-Login oder an DAV-Verhalten bei einer bloßen Kontosperre.
- Keine Anmeldung als Person ohne Konto; Personendaten einschließlich E-Mail bleiben bestehen.

## Review-Fokus

- Gesperrte Konten können weder per E-Mail-Link noch per Passkey eine Websitzung erhalten; DAV bleibt davon unberührt.
- Nicht-Super-Admins und Selbstbedienung dürfen die privilegierten Kontomutationen nicht ausführen.
- Der Kontowiderruf entfernt Passkeys, TOTP-Daten, DAV-Token, API-Tokens, Remember-Token und macht offene E-Mail-/TOTP-Login-Tokens unbrauchbar.
- Bereits angemeldete Websitzungen erhalten nach Sperrung oder Kontowiderruf keinen weiteren Zugriff.
- Rücksetzen erhält die Personendaten und Beziehungen, die nicht zum Konto gehören.

## Dateiübersicht

- `src/database/migrations/`: persistentes Sperrkennzeichen.
- `src/app/Models/People/User.php`, `src/app/Policies/UserPolicy.php`: gesperrten Zustand und Super-Admin-Berechtigungen.
- `src/app/Http/Controllers/Auth/EmailLoginLinkController.php`, `PasskeyLoginController.php`, Auth-Middleware: Web-Login und bestehende Sitzungen absichern.
- `src/app/Http/Controllers/UserController.php`, `src/routes/web/admin/User.php`, neue Security-Action: autorisierte Kontomutationen und Geheimnisbereinigung.
- `src/resources/js/Pages/Admin/User/UserEditor.vue` und gezielte Vue-/Feature-Tests: Aktionen und Statusanzeige.
- `src/tests/Feature/`: Regressionen für Berechtigung, Sperre, DAV-Ausnahme und Kontowiderruf.
- `docs/manual/administratorhandbuch/`: Admin-Ablauf dokumentieren, falls dort noch nicht beschrieben.

## Arbeitsschritte

1. **Testfälle zuerst:** Bestehende Auth-/Admin-Featuretests erweitern: Web-Login gesperrt, DAV bei Sperre unverändert, privilegierte Aktionen ausschließlich für Super-Admins und Konto-Reset mit vollständigem Credential-Widerruf.
2. **Persistenz und Login-Gate:** Sperrkennzeichen migrieren, im User-Modell abbilden und Web-Login/aktive Websitzung serverseitig blockieren; gesperrte Konten dürfen keine neuen E-Mail-Links ausstellen oder bestätigen und kein Passkey-Login abschließen.
3. **Super-Admin-Aktionen:** Autorisierte Endpunkte und transaktionale Aktionen für Sperren/Entsperren, Passkey-Widerruf und Kontorücksetzung ergänzen. Selbstbedienung und fehlende Super-Admin-Rolle ablehnen.
4. **Admin-Oberfläche und Handbuch:** Status und Aktionen im Benutzerkonto-Tab anbieten; bestehende 2FA-Wiederherstellung beibehalten; Betreiberhandbuch passend ergänzen.
5. **Verifikation:** Fokussierte isolierte PHPUnit- und Vue-Tests, relevante Format-/Qualitätsprüfungen, `git diff --check` und vollständige Diff-Prüfung; danach deutscher `fix`-Commit mit `Fixes #113`, Push und Issue-Abschluss.
