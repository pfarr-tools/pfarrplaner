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

