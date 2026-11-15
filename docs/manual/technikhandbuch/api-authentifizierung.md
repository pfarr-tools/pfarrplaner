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
