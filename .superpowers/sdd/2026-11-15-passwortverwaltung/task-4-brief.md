# Task 4 — E-Mail- und TOTP-Zwei-Faktor-Authentifizierung

## Scope

Nach erfolgreichem Passwortlogin soll optional eine zweite, begrenzte
Challenge verlangt werden. E-Mail-Challenges sind einmalig und ablaufend;
TOTP-Geheimnisse werden verschlüsselt gespeichert, Recovery-Codes nur gehasht.
Aktivierung, Bestätigung, Deaktivierung, Recovery und Rate-Limits werden mit
Re-Authentifizierung und bestehenden Rollen abgesichert.

## Vorprüfung

- Vor Änderungen werden `composer.json`, `package.json` und die vorhandenen
  Auth-/Mail-Dienste auf gepflegte, Laravel-11-kompatible Bibliotheken geprüft.
- Eine neue Abhängigkeit wird nur mit Lizenz-, Wartungs- und
  Sicherheitsbegründung aufgenommen.
- Der technische und administrative Betriebsumfang (Mail, Uhrzeit, Backup,
  Recovery) wird vor Migrationen dokumentiert.

## Acceptance criteria

- Aktivierung und Deaktivierung benötigen eine aktuelle Re-Authentifizierung.
- TOTP-Geheimnisse werden verschlüsselt, Recovery-Codes gehasht gespeichert.
- Challenge-Ablauf, Einmalverwendung, Zweckbindung, Replay-Schutz und
  Rate-Limit sind serverseitig getestet.
- Fehlermeldungen verraten keine Konto- oder Challenge-Details.
- Login, E-Mail-Challenge, TOTP, Recovery und Admin-Recovery sind als
  isolierte PHPUnit-/Mail-/Browser-Szenarien abgedeckt.
- Benutzer-, Administratoren- und Technisches Handbuch werden nachgeführt.

## Review boundary

Senior Review prüft Abhängigkeitsentscheidung, Migrationen und Rollback,
Geheimnisspeicherung, Challenge-Lifecycle, Rate-Limits, Recovery und alle
betroffenen Betriebsdokumente.
