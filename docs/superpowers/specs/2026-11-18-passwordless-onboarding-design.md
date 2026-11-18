# Passwortloser Login und Sicherheits-Onboarding

## Ziel

Pfarrplaner authentifiziert Benutzer:innen ausschließlich mit Passkeys,
E-Mail-Anmeldelinks und dem bestehenden TOTP-Schritt. Passwörter werden nicht
mehr im Login, in der Benutzerverwaltung oder beim erzwungenen Wechsel
verwendet. Ein aufschiebbarer Einrichtungsschritt führt E-Mail-Link-Konten
ohne zusätzlichen Faktor direkt zur Einrichtung.

## Kontoklassifikation

`isOfficialUser` wird als boolesches Datenbankfeld gespeichert. Die Migration
setzt den Wert für vorhandene Konten mit einem nichtleeren Passwort auf `true`
und für die übrigen auf `false`. Außerdem setzt sie `must_change_password`
für alle Konten auf `false`. Bestehende Passwort-Hashes bleiben vorerst als
nicht verwendete Legacy-Werte erhalten; es gibt danach keinen Login-,
Änderungs-, Setz- oder Reset-Pfad dafür.

## Login und Administration

- Login erfolgt mit Passkey oder einmaligem E-Mail-Link. Bei aktiviertem TOTP
  verlangt der E-Mail-Link weiterhin einen gültigen TOTP-Code.
- Passwort-Login, Passwort-Reset und erzwungener Passwortwechsel werden
  entfernt.
- Die Administration behält die explizite Kontoklassifikation
  `isOfficialUser`, verlangt für neue offizielle Konten eine E-Mail-Adresse
  und versendet Anleitungen zur Anmeldung per E-Mail-Link statt Zugangsdaten.
- Passkey-User-Verification und TOTP-Prüfung bleiben wirksam.

## Sicherheits-Onboarding

- Ein zusätzlicher Faktor bedeutet wie in der bestehenden Produktspezifikation
  einen Passkey **oder** bestätigtes TOTP. Entsprechend geschützte Konten
  werden nicht zum zweiten Faktor gedrängt.
- Nach erfolgreichem E-Mail-Link-Login ohne zusätzlichen Faktor wird der
  Onboarding-Zähler erhöht. Der Hinweis erscheint bei den ersten vier solchen
  Logins und danach frühestens 14 Tage nach dem letzten Hinweis.
- Ein authentifiziertes Middleware-Gate leitet normale App-Routen auf eine
  Fullscreen-Seite um. Erlaubt bleiben Onboarding, notwendige Passkey-/TOTP-
  Setup-Aufrufe, Abmelden und „Später erinnern“.
- Die Fullscreen-Seite erklärt Passkeys und Authenticator-Apps verständlich
  und verlinkt Microsoft Authenticator und 2FAS Auth. Beide Einrichtungs-
  prozesse können dort direkt gestartet werden. „Später erinnern“ gibt die
  laufende Sitzung frei und weist auf das Profil hin.
- Bei TOTP wird das Gate erst nach gültiger Codeprüfung freigegeben. Bei
  Passkeys wird es nach erfolgreicher WebAuthn-Registrierung freigegeben.

## Verifikation und Betrieb

Tests decken Migration/Serialisierung, deaktivierte Passwort-Routen,
Admin-Kontoverwaltung ohne Passwortdaten, E-Mail-Link-Zähler und -Kadenz,
Middleware-Bypassversuche, Aufschieben sowie erfolgreiche und fehlgeschlagene
Faktoreinrichtung ab. Die drei Handbücher dokumentieren Login, Administration
und Migration. Produktivmigrationen folgen den Backup- und Rollback-Regeln.
