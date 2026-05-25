[//]: # (TOC: 6. API-Authentifizierung)

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
