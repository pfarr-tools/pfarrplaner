[//]: # (TOC: 8. API-Authentifizierung)

# API-Authentifizierung

Die API ist standardmäßig vollständig mit `auth:api` abgesichert. Öffentliche API-Routen sind nur noch dort zulässig, wo die Öffentlichkeit fachlich eindeutig beabsichtigt ist.

## Was das praktisch bedeutet

- Grundsätzlich braucht jeder `/api`-Endpunkt eine Anmeldung.
- Alle schreibenden API-Aufrufe sind geschützt.
- Auch lesende Endpunkte gelten standardmäßig als intern oder benutzerbezogen und sind deshalb ebenfalls geschützt.
- Aktuell ist nur der Health-Check unter `/api/health` öffentlich erreichbar.

## Projektbezug

Im Projekt sind unter anderem diese Bausteine sichtbar:

- Guard `api` aus `config/auth.php` mit Token-Authentifizierung
- Middleware `auth:api` als Standard für die komplette API
- Web-Authentifizierung für normale Oberflächenrouten
- interne Vue-Komponenten verwenden für API-Aufrufe bevorzugt `this.$api()`, damit das Benutzer-Token konsistent als Bearer-Token gesendet wird

## Zwei-Faktor-Authentifizierung im Web-Login

Die Web-Benutzertabelle enthält die vier nullable Zustandsfelder der
Zwei-Faktor-Authentifizierung. Das TOTP-Paket wird ausschließlich über das
`App\Models\People\User`-Modell eingebunden:

- `two_factor_secret` wird über den Eloquent-Cast `encrypted` verschlüsselt.
- `two_factor_recovery_codes` verwendet standardmäßig den Hashing-Modus des
  Pakets; die Klartext-Codes werden nur bei der Ausgabe nach der Erzeugung
  bereitgestellt.
- `two_factor_confirmed_at` kennzeichnet die aktivierte Einschreibung.
- `two_factor_last_used_timestep` schützt gegen die Wiederverwendung eines
  erfolgreichen TOTP-Zeitfensters.

Die Migration zielt unveränderlich auf `users` und hat absichtlich keinen
automatischen Rückwärtsweg. Vor Migrationen gelten daher die Backup- und
Restore-Regeln des Administratorenhandbuchs; ein Image-Rollback ohne passenden
Datenbank-Restore ist nur für die expandierte, noch kompatible Schemaänderung
zulässig.

Die geschützten Web-Endpunkte für die persönliche Einschreibung sind:

- `POST /profil/zwei-faktor/start`: erfordert eine authentifizierte Sitzung,
  sperrt die User-Zeile und gibt die Provisionierungs-URI sowie die einmaligen
  Recovery-Codes zurück.
- `POST /profil/zwei-faktor/bestaetigen`: prüft einen 6- bis 8-stelligen TOTP-
  Code, akzeptiert die vom Paket vorgesehenen Leerzeichen oder Bindestriche
  und aktiviert den Faktor nur bei einer laufenden Einschreibung.
- `POST /profil/zwei-faktor/deaktivieren`: erfordert eine authentifizierte Sitzung
  und löscht den Faktor unter derselben Zeilensperre.

Alle drei Routen liegen in der authentifizierten CSRF-geschützten Web-Gruppe.
Codefehler werden ohne Konto- oder Zustandsdetails gemeldet; TOTP-Rate-Limits
gelten pro Benutzerkonto und liefern bei einer temporären Sperre HTTP 429. E-Mail-Link-Anforderungen werden nach Quell-IP und normalisierter E-Mailadresse begrenzt. Die Antworten enthalten weder den TOTP-Secretwert als eigenes Feld noch die sensiblen User-Modellattribute.

Das Middleware-Gate für Sicherheits-Onboarding läuft auch in den API- und Extranet-Gruppen. Nach dem passenden E-Mail-Link-Login können API-Anfragen daher bis zur Faktoreinrichtung oder einer aktuellen Sitzungsfreigabe auf die Onboarding-Seite umgeleitet werden. `APP_URL` wird automatisch als Sanctum-Stateful-Domain ergänzt, damit gleich-originige Browseranfragen die Sitzung verwenden. Externe Bearer-Token-Clients müssen die Einrichtung im Browser abschließen.

Passkey- und TOTP-Verwaltungsrouten setzen eine authentifizierte Sitzung voraus.
Sie lehnen Stellvertretersitzungen ab.
Passkey-Registrierung bleibt an eine frische WebAuthn-Challenge und User
Verification gebunden; der TOTP-Faktor wird erst nach erfolgreicher Codeprüfung
aktiviert.

Das separate Sperrkennzeichen `users.is_locked` wird von Web-Anmeldungen und
dem authentifizierten Web-Middleware-Gate ausgewertet. DAV verwendet weiterhin
separat gespeicherte API-Zugangsdaten und bleibt bei einer Web-Kontosperre
unverändert. Beim Zurücksetzen eines Kontos auf eine Person ohne Konto werden
dagegen `api_token`, persönliche API-Tokens, Passkeys, TOTP-Faktoren und offene
Web-Anmeldungen widerrufen.

Der Super-Admin-Endpunkt `POST /admin/benutzer/{user}/login-link` erzeugt für
ein aktives, nicht gesperrtes Nicht-Super-Admin-Konto einen 256-Bit-Nonce und
verbraucht vorherige offene Login-Links dieses Kontos. In der Tabelle
`passwordless_login_links` wird nur der SHA-256-Hash gespeichert; die Antwort
enthält einmalig die Login-URL und den UTC-Ablaufzeitpunkt nach 60 Minuten. Die
Antwort ist nicht cachebar, wird nicht per E-Mail versendet und der Endpunkt
wird von Telescope ausgeschlossen. Die URL führt durch denselben Bestätigungs-
und TOTP-Ablauf wie ein per E-Mail versendeter Link.
## Empfehlung für Integrationen

- Neue produktive Integrationen dürfen nicht von implizit öffentlichen Endpunkten ausgehen.
- Integrationen sollten immer ein Benutzer-API-Token mitsenden und nicht auf Cookie-Sitzungen vertrauen.
- Vor dem Einsatz sind Route, Controller, erwartete Rechte und Datenumfang zu prüfen.
- Für dokumentierte Schnittstellen sollte zusätzlich die generierte OpenAPI-Spezifikation bereitgestellt werden.

## Typische geschützte Bereiche

- Kalenderdetails und Schnellauswahlen
- Gottesdienst-CRUD und Personenzuordnungen
- Liturgie-Bearbeitung, Lied- und Textsuche
- Teams, Gemeinden, Pools, Berichte und Inbox-Funktionen

## Typische öffentliche oder halböffentliche Bereiche

- Health-Check unter `/api/health`

Wenn ein neuer API-Endpunkt öffentlich bleiben soll, muss das in Route, Controller und Dokumentation ausdrücklich erkennbar sein. Ein „historisch gewachsener“ öffentlicher GET-Endpunkt ist kein ausreichender Grund.
