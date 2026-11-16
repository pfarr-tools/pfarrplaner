# Design: Wiederherstellung verlorener Zwei-Faktor-Geräte

## Ziel und Anlass

Ein Benutzer, der sein Authenticator-Gerät und alle Recovery-Codes verloren hat, soll nach einer dokumentierten Identitätsprüfung durch einen Super-Admin wieder Zugang erhalten. Die Wiederherstellung ist ein eigener Vorgang; sie setzt das Benutzerpasswort nicht zurück und ist unabhängig davon, ob die hinterlegte E-Mail erreichbar ist.

## Aktueller Stand

- TOTP wird über die Profil-Sicherheitsseite ein- und ausgeschaltet.
- Der bestehende Passwortreset ist Super-Admin-beschränkt, setzt ein temporäres Passwort und erzwingt dessen Änderung. Er entfernt den TOTP-Faktor nicht.
- Login-E-Mail-Codes liegen in `two_factor_login_challenges` und sind zeitlich begrenzt sowie einmalig verwendbar.
- TOTP-Änderungen und Login-Challenges sperren den jeweiligen Benutzer-Datensatz mit `lockForUpdate()`.
- Die dokumentierte Beispielkonfiguration verwendet `SESSION_DRIVER=database`; Laravel unterstützt daneben weitere Treiber, bei denen aktive Sitzungen nicht über die `sessions`-Tabelle widerrufen werden können.

## Ansätze

1. **Eigener Super-Admin-Wiederherstellungsvorgang (gewählt):** Faktor widerrufen, offene Login-Codes verbrauchen und ein dauerhaftes Audit-Ereignis erfassen. Das Passwort bleibt unverändert. Das entspricht der vom Auftraggeber gewählten Variante und funktioniert ohne E-Mail-Zustellung.
2. **Mit Passwortreset koppeln:** bestehende Passwortreset-Aktion setzt auch 2FA zurück. Das bindet die Recovery an den E-Mail-Versand, obwohl verlorener E-Mail-Zugang ein möglicher Grund für den Supportfall ist, und vermischt zwei getrennte Adminaktionen.
3. **Nur manuelle Datenbank-/Supportanleitung:** keine UI und kein Anwendungs-Audit. Das ist weniger sicher und schlechter nachvollziehbar als eine autorisierte, protokollierte Aktion.

## Vorgeschlagener Ablauf

1. Support prüft die Identität außerhalb der Anwendung anhand eines bereits bekannten, organisationskontrollierten Kontaktwegs. Eine eingehende E-Mail allein gilt nicht als ausreichender Nachweis, da der E-Mail-Code selbst ein Login-Faktor ist.
2. Ein Super-Admin öffnet den betroffenen Benutzer in der Administration, bestätigt die Wiederherstellung, gibt sein aktuelles Passwort erneut ein und trägt eine kurze Supportfall-Referenz ein. Das Feld soll keine personenbezogenen Details aufnehmen. Die Passwortprüfung erhält ein eigenes Rate-Limit.
3. Die Anwendung verweigert die Aktion für normale Administratoren und für den angemeldeten Super-Admin selbst. Ein anderer Super-Admin darf auch ein Super-Admin-Konto wiederherstellen.
4. In einer Transaktion wird der Benutzer gesperrt; aktiver oder ausstehender TOTP-Zustand wird entfernt, offene E-Mail-Challenges werden als verbraucht markiert und ein unveränderlicher Audit-Datensatz mit handelndem Super-Admin, Zielkonto, Referenz, Zeitpunkt und IP-Adresse geschrieben.
5. Persönliche API-Tokens und aktive Datenbank-Sitzungen des Zielkontos werden widerrufen. Weil andere Session-Treiber nicht zuverlässig benutzerbezogen auflistbar sind, wird die UI-Aktion nur bei `SESSION_DRIVER=database` angeboten; die Betriebsanleitung muss den Treiber als Voraussetzung für diese Wiederherstellungsfunktion benennen.
6. Passwort und bestehender Passwortwechselstatus bleiben erhalten. Nach dem nächsten Passwort-Login darf der Benutzer ausschließlich eine eigene Wiederherstellungsseite, die erforderlichen TOTP-Einrichtungs-/Bestätigungsaktionen und Logout verwenden. Alle normalen Web- und API-Zugriffe bleiben gesperrt, bis TOTP erfolgreich neu eingerichtet und bestätigt wurde. Es gibt keinen neuen E-Mail-Code- oder Passwortversand.
7. Die Administration zeigt frühere Wiederherstellungen für dieses Konto mit Zeitpunkt, handelndem Super-Admin und Supportfall-Referenz an. Geheime Faktoren, Codes und Passwörter erscheinen weder in der Oberfläche noch im Audit.

## Umfang

- Neue CSRF-geschützte POST-Route, explizite Super-Admin-Policy und frische Passwort-Re-Authentifizierung des handelnden Super-Admins.
- Reversible Migration und dauerhaftes Audit-Modell ohne Faktor- oder Passwortdaten.
- Transaktionale Recovery-Action mit Sperrung, Faktorwiderruf, Verbrauch offener Challenges, Token-/Sessionwiderruf und Audit.
- Bestätigungsdialog mit Passwort und Supportfall-Referenz im Admin-Benutzereditor; eingeschränkte Audit-Historie.
- Temporärer Zugriffsschutz mit eigener Wiederherstellungsseite; Freigabe erst nach erfolgreicher Neueinschreibung und Bestätigung von TOTP.
- PHPUnit- und Vue-Tests für Berechtigung, Selbst-Recovery-Sperre, Passwort-Re-Authentifizierung und Rate-Limit, Faktorzustände, Zugriffssperre und -freigabe, offene/abgelaufene Challenges, Tokens/Sitzungen und Audit.
- Aktualisierung von Benutzer-, Administratoren- und Technischem Handbuch sowie Installations-/Betriebsdokumentation.

## Nicht-Ziele

- Selbstbedienungs-Recovery per E-Mail.
- Passwort zurücksetzen, E-Mail-Adresse ändern oder eine Anmeldeberechtigung per Recovery-Aktion erteilen.
- Erzwingen von TOTP für Konten, die nicht explizit eine Wiederherstellung durchlaufen.
- Passkey-Registrierung oder -Login; dies bleibt eine eigene spätere Etappe.
- Ersetzen des dokumentierten Identitätsprüfungsprozesses durch eine Prüfung innerhalb der Anwendung.

## Sicherheits- und Betriebsgrenzen

- Audit-Einträge enthalten ausschließlich IDs, Supportfall-Referenz, Zeit und IP, keine Geheimnisse oder Freitext zur Person.
- Identitätsprüfung, Berechtigung und Recovery sind getrennte Schritte; die Anwendung kann die externe Identitätsprüfung nicht selbst beweisen.
- Die Session-Widerrufsgarantie gilt für den dokumentierten Datenbank-Session-Treiber. Abweichende Session-Treiber müssen vor der Recovery auf den Datenbanktreiber umgestellt werden.
- Das Passwort bleibt gültig. Ein Super-Admin darf die Aktion daher nur nach dem dokumentierten Identitätsnachweis und frischer Passwort-Re-Authentifizierung ausführen.
- Die Anwendung beschränkt das Zielkonto nach dem Reset serverseitig auf den Neueinschreibungsablauf; eine reine Passwortanmeldung gewährt keinen normalen Zugriff.

## Festlegungen aus dem Builder-Check

- Der handelnde Super-Admin muss unmittelbar vor der Wiederherstellung sein aktuelles Passwort bestätigen.
- Das Zielkonto muss TOTP erneut einrichten und bestätigen, bevor normale Anwendungsfunktionen wieder freigegeben werden.
- Tabellen-/Routen-/Policy-Namen und vorhandenes Admin-Bestätigungsdialogmuster gemäß Repository-Konvention auswählen.
- Bei der Migration Fremdschlüssel-Löschverhalten so wählen, dass das Audit bei Kontoentfernung nachvollziehbar bleibt, ohne Kontolöschungen unnötig zu blockieren.
- Vor Umsetzung das Verhalten des installierten Laravel-Datenbank-Sessionhandlers und Sanctum-Tokenmodells mit Tests verifizieren.
