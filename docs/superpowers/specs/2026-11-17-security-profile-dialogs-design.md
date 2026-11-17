# Sicherheitsaktionen im Profil – Entwurf

## Ziel

Passwortgeschützte Sicherheitsaktionen sollen direkt aus der Sicherheitsansicht mit einem verständlichen Dialog bestätigt werden. Das Profil bleibt dabei sichtbar; die Passwortbestätigung wird nicht als dauerhaftes Formular in der Seite oder als separate Navigationsseite dargestellt.

## Umfang

- Beim Deaktivieren von TOTP öffnet die Schaltfläche einen Passwortdialog. Das aktuelle Passwort wird nur für die Anfrage verwendet und danach aus dem UI-Speicher gelöscht.
- Wenn der Passkey-Optionsaufruf wegen abgelaufener Passwortbestätigung `423` liefert, öffnet sich derselbe Dialog. Nach erfolgreicher Bestätigung bleibt der Dialog geschlossen und die Person startet die Passkey-Einrichtung mit einem neuen Klick. Dadurch beginnt `navigator.credentials.create()` aus einer aktuellen Nutzeraktion.
- Die bestehenden Rate-Limits, serverseitige Passwortprüfung, Faktor- und Kontoschutzregeln bleiben unverändert.
- Das Sicherheitshinweis-Interstitial bleibt für geeignete Passwort-only-Konten aktiv. In `local` und `dusk` wird es zur Vorschau mit `?show_interstitial=1` sichtbar; ohne diesen Schalter unterdrückt die Anwendung Interstitials in diesen Umgebungen absichtlich.

## Gewählte Umsetzung

Eine wiederverwendbare, zugängliche Passwortdialog-Komponente übernimmt Eingabe, Abbrechen, Fehlerdarstellung, Fokus und Geheimnisbereinigung. `SecurityTab` öffnet sie für TOTP-Widerruf und erneute Passkey-Bestätigung. Der Bestätigungscontroller gibt bei JSON-Anfragen eine JSON-Antwort bzw. einen strukturierten Validierungsfehler zurück und behält den bestehenden Redirect-Ablauf für normale Formularanfragen.

Die Passkey-Einrichtung wird nach einer erfolgreichen Passwortbestätigung nicht automatisch fortgesetzt. Der zusätzliche Klick stellt sicher, dass die Browser-API mit einer frischen Nutzeraktion aufgerufen wird.

## Ansätze

- Passwortfelder dauerhaft in der Profilansicht: verworfen, weil sie die Hauptansicht aufblähen und nicht erst bei der jeweiligen Aktion erscheinen.
- Separate Passwortbestätigungsseite: bestehender Ablauf, verworfen, weil er den Kontext der Sicherheitsansicht verlässt.
- Wiederverwendbarer Dialog für beide Aktionen: gewählt, weil er den Kontext erhält, nur bei Bedarf erscheint und dasselbe zugängliche Interaktionsmuster bietet.

## Erfolgskriterien

- TOTP-Widerruf und Passkey-Reauthentifizierung sind ohne Navigationswechsel möglich.
- Falsche Passwörter, Rate-Limits, Abbrechen, erneutes Öffnen und erfolgreiche Bestätigung sind verständlich und löschen Passwörter aus dem UI-Zustand.
- Nach Passkey-Reauthentifizierung ist ein erneuter ausdrücklicher Klick nötig und ausreichend, um eine neue Registrierung zu starten.
- Das Interstitial bleibt in Produktion an die bereits vereinbarten Passwort-only-, 21-Tage-Aufschub- und Ausschlussregeln gebunden; die lokale Vorschau ist dokumentiert.
- Benutzer-, Administratoren- und Technisches Handbuch werden auf Relevanz geprüft und konsistent aktualisiert.

## Nicht-Ziele

- Keine Änderung an Passkey- oder TOTP-Kryptografie, Recovery- und Rate-Limit-Regeln.
- Keine dauerhafte Ausblendung der Sicherheitsempfehlung und keine Änderung der Laufzeitbedingungen in Produktion.
- Kein automatischer Start der WebAuthn-Browserabfrage direkt nach dem asynchronen Passwort-POST.
