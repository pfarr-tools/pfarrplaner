# SDD ledger — plan: docs/superpowers/plans/2026-11-15-passwortverwaltung.md

Pre-flight: Task 1 produces the central password validation/service contract consumed by Tasks 2 and 3; the authentication session/challenge decisions in Tasks 3 and 4 are consumed by Task 5. The plan keeps these boundaries explicit and requires each preceding task to be reviewed before the next begins.

Task 1 — Passwortregel und sichere Initialkennungen: abgeschlossen.

- Erstimplementierung: `4e23ec18`.
- Senior Review 1: nicht freigegeben; zentrale Pfade, Autorisierung, Tests und Dokumentation nachgeschärft.
- Korrekturcommit: `9680f4d3`.
- Senior Review 2: nicht freigegeben; normalisierte Boolean-Werte, Installationspfade, Reset-Flag, exaktes Initialmuster, PHPStan und Handbücher nachgeschärft.
- Korrekturcommit: `2d207449`.
- Senior Review 3: APPROVED; keine Critical-, Important- oder Minor-Findings.
- Verifikation: 42 isolierte PHPUnit-Tests / 123 Assertions, Formatprüfung, `git diff --check`, PHPStan und Doku-/JSDoc-Prüfung bestanden.

Task 2 — Admin-Passwort setzen und Initialpasswort vorschlagen: abgeschlossen.

- Task-2-Brief: Commit `108f312f`.
- Umsetzung: `f279888d`; der Benutzereditor zeigt einen bestätigbaren Vorschlag,
  der Server validiert, hasht, `must_change_password` setzt und die Kontodaten-
  Mail versendet.
- Senior Review 1: nicht freigegeben; `user.store`-Autorisierung,
  Pflichtwert bei `createAccount` und Browserabdeckung fehlten.
- Korrekturcommit: `7f816811`; Autorisierung, Pflichtvalidierung, Testdaten-
  schutz und fokussierter Dusk-Test ergänzt.
- Senior Review 2: nicht freigegeben; Dusk-Test suchte im unsichtbaren Tab und
  prüfte keinen echten Speicherklick.
- Korrekturcommit: `c3b57d2e`; sichtbarer Tab, Vorschlag/Erneuern, Speichern
  und Geheimnisgrenze im Browserablauf geprüft.
- Senior Review 3: APPROVED; keine offenen Findings.
- Verifikation: Dusk 7 Tests / 17 Assertions, PHP 16 Tests / 85 Assertions,
  PHPStan, Format- und Dokuprüfung bestanden; TTY-Hinweis nicht blockierend.

Task 3 — Nicht umgehbares Passwortwechsel-Interstitial und Passwort-Reset:
abgeschlossen.

- Planbrief: `fb924f9c`.
- Erstimplementierung: `be037095`; zentrale `RequirePasswordChange`-
  Middleware, eigenes Passwortwechselziel, erlaubte Logout-/CSRF-/Resetpfade,
  Proxy-Ausnahme und Handbuchnachführung.
- Senior Review 1: nicht freigegeben; vollständige Bypass- und
  Switch-Lifecycle-Tests sowie engere Proxy-Validierung fehlten.
- Korrekturcommit: `9c252726`; Tests für erfolgreichen Wechsel, Logout,
  CSRF-Keepalive, Inertia, echten Switch/Switch-back-Lifecycle und gültige
  Proxy-Autorisierung ergänzt; Sessionausnahme validiert.
- Senior Review 2: APPROVED; keine offenen Findings.
- Verifikation: 27 isolierte PHPUnit-Tests / 130 Assertions, PHPStan,
  Format-, Diff- und Doku-/JSDoc-Prüfungen bestanden.

Task 4 — E-Mail- und TOTP-Zwei-Faktor-Authentifizierung: Paketbasis abgeschlossen.

- Dependency-Audit dokumentiert und als Kommentar in Issue #80 veröffentlicht.
- `roundly-consulting/two-factor-for-laravel:^2.0` ergänzt; Composer löst
  genau drei notwendige MIT-Transitivabhängigkeiten auf, ohne bestehende
  Pakete zu aktualisieren.
- Senior Review Installationsschritt: APPROVED; Laravel 13/PHP 8.4,
  Lockfile-Konsistenz und konkrete Commit-Referenzen geprüft.
- Offener Nebenbefund: `composer audit` meldet bestehende Advisories in elf
  anderen Paketen; kein neues Roundly-Paket ist betroffen. Das ist ein
  separater Release-Gate und wird nicht durch diesen Commit verborgen.
- Verifikation: `composer validate --strict` mit den bestehenden Schema-
  Warnungen, Plattform-/Lockfile-Prüfung, `git diff --check` bestanden.

- Migration/User-Modell-Integration: Commit `a0ea4032`.
- Senior Review 1: CHANGES REQUESTED; At-Rest-Assertions, unveränderliches
  `users`-Ziel, sicherer Rollback-Schutz, Restore-Dokumentation und PHPDoc
  nachgeschärft.
- Senior Review 2: CHANGES REQUESTED; der Test prüfte zunächst nur Cast-Namen
  und umging mit `setRawAttributes()` die Cast-Pipeline.
- Korrektur: echter Enrolment-Test liest Secret und Recovery-Codes aus der
  Testdatenbank zurück und prüft Verschlüsselung beziehungsweise Hashes.
- Senior Review 3: APPROVED; keine offenen Findings.
- Verifikation: 2 isolierte PHPUnit-Tests / 20 Assertions, Formatprüfung und
  `git diff --check` bestanden. Der vollständige PHP-/JS-/Build-Lauf meldete
  weiterhin den unabhängigen Container-Dateirechtefehler bei `chmod()`; die
  PHP-Tests und Vitest liefen durch.

- TOTP-Endpunkte: Commit `1467d6df`; Aktivierung, Bestätigung und Deaktivierung
  mit Re-Authentifizierung, Rate-Limits, Zeilensperren und generischen Fehlern.
- Senior Reviews: drei CHANGES REQUESTED-Runden; Re-Auth-Limits, Sperrverhalten,
  formatierte Codes, Paketfehlerbehandlung und Tests nachgeschärft. Ein Review
  wurde versehentlich auf dem Parent-Commit ausgeführt und verworfen.
- Folgeprüfung: `63831c1d` ergänzt Authentifizierungs- und Rate-Limit-Tests;
  `8c03bb29` korrigiert die Handbuchaussagen zur noch nicht vorhandenen UI.
- Verifikation: isolierte PHPUnit-Tests zuletzt 9 / 66 Assertions, PHPStan
  1088 Dateien und Formatprüfung bestanden. `docs:check` meldet bestehende
  PHPDoc-Altlasten in unveränderten Methoden von `User.php`.
- Senior Review zum Endpoint-Bereich fand P2-Lücken bei echtem CSRF-419,
  Parallelprozess-Tests und Policy-Ablehnung. Die Endpunkte sind in der
  CSRF-geschützten authentifizierten Webgruppe; echte parallele HTTP- und
  Browser-CSRF-Abdeckung bleibt bei der Gesamtprüfung offen.

- Profiloberfläche: Commit `8cfcc0f1`; Statusanzeige, Einrichtung, manuelle
  TOTP-Schlüsseleingabe, einmalige Recovery-Codes, Bestätigung und Deaktivierung.
  Erfolgreiche Bestätigung entfernt Schlüssel und Codes aus dem DOM. Das
  Passwortfeld wird nach jeder Anfrage geleert. Ausstehende Einrichtung kann
  neu gestartet werden; aktive Faktoren können dadurch nicht ersetzt werden.
- Senior Review 1: CHANGES REQUESTED; Geheimnisse blieben nach Bestätigung
  sichtbar, Passwort blieb für Folgeaktionen stehen und Anleitung versprach
  Scannen ohne QR-Ausgabe. Korrekturen ergänzt und erneut geprüft.
- Senior Review 2: APPROVED; keine offenen Findings.
- Verifikation: PHPUnit 9 Tests / 66 Assertions, Vitest 4 Tests, PHPStan
  1088 Dateien, Prettier-Formatcheck, `git diff --check` und Vite-Build
  bestanden. ESLint meldet keine Fehler; es verbleiben bestehende Vue-
  Template-Formatwarnungen. Der Build meldet bestehende große Chunks und
  veraltete Browserslist-Daten.

Task 4 — Login-Challenge und E-Mail-Fallback: Umsetzung im Review.

- Planabschnitt und Abnahmekriterien vor dem Code im Issue #80 kommentiert:
  `https://github.com/pfarr-tools/pfarrplaner/issues/80#issuecomment-6903587730`.
- Login-Gate trennt Passwortprüfung von Faktorbestätigung. Pending-Zustand
  liegt fünf Minuten in der Session; Anmeldung und Remember-Cookie entstehen
  erst nach gültigem TOTP-, Recovery- oder E-Mail-Code. Laravel-Validierung,
  Lockout-/Attempting-/Failed-Events, 200-ms-Zeitbox und Passwort-Rehashing
  bleiben erhalten; Kontozustand wird während der Entscheidung gesperrt.
- E-Mail-Codes werden zufällig generiert, gehasht gespeichert, auf fünf
  Minuten begrenzt, je Code auf fünf Fehlversuche beschränkt und atomar
  einmalig verbraucht. E-Mail-Anforderungen haben ein eigenes Fünf-Minuten-
  Limit. TOTP- und Recovery-Prüfungen laufen über die Paket-API.
- Challenge-Seite, Abbrechen, E-Mail-Anforderung und Fehlermeldungen ergänzt;
  Benutzer-, Administratoren-, Technik- und Betriebshandbücher nachgeführt.
- Senior Reviews 1–4 fanden zusammen neun P2-Befunde zu Ablauf, veraltetem
  Pending-Zustand, Laravel-Login-Metadaten, Eingabevalidierung, fehlender
  Challenge-Ansicht, paralleler Faktorzustandsänderung, Lockout-Event,
  Timing-Schutz und Passwort-Rehashing. Alle bisher gefundenen Punkte wurden
  korrigiert; abschließendes Review folgt.
- Senior Review 5 fand fehlende direkte Request-Payload-Abdeckung im Telescope-
  Test. Request-, verschachtelte Session- und JSON-Response-Werte werden nun
  durch die Methoden des installierten `RequestWatcher` geprüft. Senior Review
  6: APPROVED; keine offenen Findings zu Redaktion, Sperrreihenfolge, TOTP-
  Faktorzustand oder E-Mail-Code-Verbrauch.
- Verifikation nach den Korrekturen: PHP **1105 bestanden, 5 erwartete Skips,
  4088 Assertions**; Vue **3 Tests bestanden**; PHPStan **1090 Dateien ohne
  Fehler**; Pint und Prettier bestanden; `git diff --check` bestanden.
  `docs:check` findet nur die bereits bestehenden PHPDoc-Lücken in
  unveränderten Methoden von `src/app/Models/People/User.php`.
- Der aktuelle Vite-Build konnte nicht abgeschlossen werden: Der Vite-Prozess
  darf das root-eigene generierte Verzeichnis `src/public/build/assets` nicht
  entfernen; ein zusätzlicher Vitest-Aufruf aus der Dev-Orchestrierung hatte
  ebenso einen Schreibfehler unter `node_modules/.vite-temp`. Der gezielte
  Challenge-Vitest-Lauf lief separat erfolgreich. Build-/Containerrechte sind
  als Betriebsproblem festzuhalten und wurden nicht durch Anwendungsänderungen
  kaschiert.
- Der zweite Task-4-Punkt zu Geräte-/Recovery-Verlust und Super-Admin-Recovery
  ist ausdrücklich noch offen; Task 4 und Issue #80 sind daher nicht fertig.

Nächste Etappe — Entwurf zur Super-Admin-2FA-Wiederherstellung:

- Auftraggeber wählte eine eigene, protokollierte 2FA-Wiederherstellung ohne
  Passwortreset und ohne Abhängigkeit von E-Mail-Zustellung.
- Der Builder-Check fragte nach frischer Passwortbestätigung des Super-Admins
  und verpflichtender TOTP-Neueinschreibung des Zielkontos. Beide Punkte
  wurden bestätigt und in den Entwurf aufgenommen.
- Entwurf: `docs/superpowers/specs/2026-11-16-two-factor-recovery-design.md`.
  Der Auftraggeber hat ihn freigegeben. Implementierungsplan:
  `docs/superpowers/plans/2026-11-16-two-factor-recovery.md`; die Planprüfung
  durch den Auftraggeber steht vor der ersten Codeänderung noch aus.

