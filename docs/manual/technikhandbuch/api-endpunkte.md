[//]: # (TOC: 9. API-Endpunkte)

# API-Endpunkte

Dieses Kapitel beschreibt die aktuell sichtbaren API-Routen aus `routes/api/*.php`. Für neue Integrationen sollten Sie zusätzlich die Controller und nach Möglichkeit die generierte OpenAPI-Spezifikation prüfen.

## Allgemeine Hinweise

- Basispräfix: `/api`
- Antwortformat ist in der Regel JSON.
- Alle `/api`-Endpunkte sind standardmäßig mit `auth:sanctum,api` geschützt.
- Die Anwendung nutzt Sanctum-Sitzungen für Browser und persönliche Sanctum-
  Tokens für externe Clients. Bisherige `api_token`-Zugangsdaten bleiben für
  die Migration kompatibel.
- Die einzige dokumentierte öffentliche Ausnahme ist derzeit `/api/health`.
- Nicht jede Route ist bereits vollständig mit OpenAPI-Attributen beschrieben.

## Health

| Methode | Pfad | Schutz | Zweck |
|---|---|---|---|
| `GET` | `/api/health` | öffentlich | einfacher Health-Check für Anwendung und Datenbank |

## Kalender und Veranstaltungen

| Methode | Pfad | Schutz | Zweck |
|---|---|---|---|
| `GET` | `/api/cal/city/{city}/{date}` | `auth:sanctum,api` | Kalenderdaten für Gemeinde und Monat |
| `GET` | `/api/quick-pick/{date}` | `auth:sanctum,api` | Schnellansicht für ein Datum |
| `GET` | `/api/kalender/monat/{date}` | `auth:sanctum,api` | Monatsansicht |
| `GET` | `/api/kalender/gottesdienst/{service}` | `auth:sanctum,api` | Details zu einem Gottesdienst im Kalenderkontext |
| `GET` | `/api/veranstaltungen/{calendar}/{start}/{end}` | `auth:sanctum,api` | Veranstaltungen in einem Bereich |

## Gottesdienste

| Methode | Pfad | Schutz | Zweck |
|---|---|---|---|
| `GET` | `/api/servicesByDayAndCity/{day}/{city}` | `auth:sanctum,api` | Gottesdienste eines Tages und einer Gemeinde |
| `GET` | `/api/user/{user}/services` | `auth:sanctum,api` | Gottesdienste einer Person |
| `GET` | `/api/service/{service}` | `auth:sanctum,api` | Detailansicht eines Gottesdiensts |
| `PATCH` | `/api/service/{service}` | `auth:sanctum,api` | Gottesdienst aktualisieren |
| `DELETE` | `/api/service/{service:slug}` | `auth:sanctum,api` | Gottesdienst löschen |
| `POST` | `/api/service/{service}/assign` | `auth:sanctum,api` | Personen oder Dienste zuordnen |
| `GET` | `/api/gottesdienste/{date}/{cities}` | `auth:sanctum,api` | Monatsabfrage für mehrere Gemeinden |

## Liturgie und Texte

| Methode | Pfad | Schutz | Zweck |
|---|---|---|---|
| `POST` | `/api/liturgy/tree/save/{service}` | `auth:sanctum,api` | Sortierung des Liturgiebaums speichern |
| `POST` | `/api/liturgy/service/{service}/import/{source}` | `auth:sanctum,api` | Liturgie aus Quelle importieren |
| `POST` | `/api/liturgy/service/{service}/blocks` | `auth:sanctum,api` | Liturgieblock anlegen |
| `PATCH` | `/api/liturgy/block/{block}` | `auth:sanctum,api` | Liturgieblock ändern |
| `DELETE` | `/api/liturgy/block/{block}` | `auth:sanctum,api` | Liturgieblock löschen |
| `POST` | `/api/liturgy/block/{block}/items` | `auth:sanctum,api` | Liturgieelement anlegen |
| `PATCH` | `/api/liturgy/item/{item}` | `auth:sanctum,api` | Liturgieelement ändern |
| `DELETE` | `/api/liturgy/item/{item}` | `auth:sanctum,api` | Liturgieelement löschen |
| `POST` | `/api/liturgy/item/{item}/assign` | `auth:sanctum,api` | Verantwortliche für Element setzen |
| `GET` | `/api/liturgy/texts/list` | `auth:sanctum,api` | Liste liturgischer Texte |
| `POST` | `/api/liturgy/text/import` | `auth:sanctum,api` | Text aus Word importieren |
| `GET` | `/api/liturgy/sources/{serviceId}` | `auth:sanctum,api` | Importquellen für Liturgie |
| `GET` | `/api/texte/liste` | `auth:sanctum,api` | Liste liturgischer Texte |

## Lieder und Gesangbücher

| Methode | Pfad | Schutz | Zweck |
|---|---|---|---|
| `GET` | `/api/liturgy/songs` | `auth:sanctum,api` | Liedliste |
| `GET` | `/api/liturgy/songselect` | `auth:sanctum,api` | reduzierte Liedauswahl |
| `GET` | `/api/liturgy/songs/songbooks` | `auth:sanctum,api` | zugehörige Gesangbücher |
| `PATCH` | `/api/liturgy/songs/{song}` | `auth:sanctum,api` | Lied ändern |
| `GET` | `/api/liturgie/lied/{song}/noten/{verses?}/{lineNumber?}` | `auth:sanctum,api` | Noten-/Musikdarstellung |
| `GET` | `/api/liturgy/song/{songReferenceId}` | `auth:sanctum,api` | einzelnes Lied |
| `POST` | `/api/liturgy/songs` | `auth:sanctum,api` | Lied anlegen |
| `GET` | `/api/songbooks/colors` | `auth:sanctum,api` | Farbzuordnung für Gesangbücher |

## Personen, Teams und Gemeinden

| Methode | Pfad | Schutz | Zweck |
|---|---|---|---|
| `GET` | `/api/people/select` | `auth:sanctum,api` | Personenauswahl |
| `POST` | `/api/people/search/{searchString}` | `auth:sanctum,api` | Personensuche |
| `POST` | `/api/people/email` | `auth:sanctum,api` | Prüfen, ob eine E-Mailadresse bereits einer Person gehört |
| `POST` | `/api/people/activate/{user}/{city}` | `auth:sanctum,api` | Person für Gemeinde aktivieren |
| `GET` | `/api/teams/by-city/{city}` | `auth:sanctum,api` | Teams einer Gemeinde |
| `GET` | `/api/city/{city}/konfiapp-types` | `auth:sanctum,api` | KonfiApp-Typen einer Gemeinde |

## Urlaub, Pools und Vertretung

| Methode | Pfad | Schutz | Zweck |
|---|---|---|---|
| `POST` | `/api/absence/{absence}/set-checked` | `auth:sanctum,api` | Abwesenheit als geprüft markieren |
| `POST` | `/api/absence/{absence}/set-approved` | `auth:sanctum,api` | Abwesenheit genehmigen |
| `DELETE` | `/api/absence/{absence}` | `auth:sanctum,api` | Abwesenheit löschen |
| `GET` | `/api/pools/{pool}/poolmasters/{date}` | `auth:sanctum,api` | zuständige Poolmaster für Datum |
| `GET` | `/api/pools/mastered/{user}/{date}` | `auth:sanctum,api` | von Person betreute Pools |

## Berichte, Tabs und Hilfsdienste

| Methode | Pfad | Schutz | Zweck |
|---|---|---|---|
| `MATCH GET,POST` | `/api/report/{report}/{step}` | `auth:sanctum,api` | Reportschritte |
| `GET` | `/api/tab/{tab}` | `auth:sanctum,api` | Tab-Inhalt |
| `GET` | `/api/tab/{tab}/count` | `auth:sanctum,api` | Zähler für Tab |
| `GET` | `/api/inbox` | `auth:sanctum,api` | Upload- oder Posteingangsübersicht |
| `GET` | `/api/ministries/list` | `auth:sanctum,api` | Dienstekatalog |
| `GET` | `/api/pixabay/query/{query}/{page?}` | `auth:sanctum,api` | Pixabay-Suche |
| `GET` | `/api/rites/find/{query}` | `auth:sanctum,api` | Suche in Kasualien |
| `DELETE` | `/api/occurence/{occurence}` | `auth:sanctum,api` | einzelnes Vorkommen löschen |

## Anmerkungen zur Pflege

- Öffentliche API-Routen dürfen nur mit ausdrücklicher fachlicher Begründung angelegt werden. Standard ist immer `auth:sanctum,api`.
- Die Route-Definitionen allein beschreiben noch nicht alle Validierungsdetails. Für Payloads und Seiteneffekte sind die jeweiligen Controller und Requests maßgeblich.

## Extranet-Schnittstellen (`/extranet`)

Die Extranet-Routen aus `routes/extranet/*.php` liegen unter dem Präfix `/extranet` und verwenden die Middleware-Gruppe `extranet` (`auth:sanctum`, `EnforceExtranetTokenScope`, `RequireTwoFactorRecovery`, `RequireSecurityOnboarding`).

### Persönliche Lese-Endpunkte

Diese Endpunkte stehen Sitzungen und unbeschränkten persönlichen Tokens (`*`) zur Verfügung; gemeindebezogene Schlüssel (`extranet:city:{id}`) und Kalenderfeed-Tokens werden abgewiesen:

| Methode | Pfad | Schutz | Zweck |
|---|---|---|---|
| `GET` | `/extranet/user/services/next` | `auth:sanctum` | kommende eigene Gottesdienste (`P`) |
| `GET` | `/extranet/user/services/past` | `auth:sanctum` | vergangene eigene Gottesdienste (`P`) |
| `GET` | `/extranet/user/services/lastWithSermon` | `auth:sanctum` | eigene Gottesdienste mit verknüpfter Predigt |
| `GET` | `/extranet/user/sermons/latest` | `auth:sanctum` | neueste Predigten der angemeldeten Person |
| `GET` | `/extranet/user/sermons/headers` | `auth:sanctum` | Predigt-Kopfdaten |
| `GET` | `/extranet/sermon/{sermonId}` | `auth:sanctum` | Einzelansicht einer Predigt |

### Gemeindebezogene Veranstaltungs- und Gottesdienstverwaltung

Diese Endpunkte verwalten sowohl Gottesdienste (`event_class = "service"`) als auch allgemeine Veranstaltungen (`event_class = "event"`) inkl. Wiederholungsregeln (`rrule`) und generierter `Occurence`-Einträge. Sie akzeptieren sowohl gemeindebezogene Extranet-Tokens (`extranet:services` + `extranet:city:{id}`) als auch berechtigte Sanctum-Sitzungen/Voll-Tokens im Rahmen der Gemeinderechte:

| Methode | Pfad | Schutz | Zweck |
|---|---|---|---|
| `GET` | `/extranet/services` | `auth:sanctum` | Gottesdienste und Veranstaltungen auflisten/filtern (`city_id`, `event_class=service\|event\|all`, `from`, `to`, `include_hidden`, `per_page`) |
| `POST` | `/extranet/services` | `auth:sanctum` | Gottesdienst oder Veranstaltung anlegen (`201 Created`) |
| `GET` | `/extranet/services/{service}` | `auth:sanctum` | Einzelnen Gottesdienst bzw. Veranstaltung inkl. `occurences` abrufen |
| `PATCH` | `/extranet/services/{service}` | `auth:sanctum` | Partielle Aktualisierung ohne Überschreiben ausgelassener Flags |
| `DELETE` | `/extranet/services/{service}` | `auth:sanctum` | Gottesdienst oder Veranstaltung in den Papierkorb verschieben (Soft-Delete) |
| `POST` | `/extranet/services/{service}/assign` | `auth:sanctum` | Mitwirkende für Dienste (`ministry`, `users`, `exclusive`, `no-toggle`) zuordnen |

#### Wichtige Payload- und Zeitregeln für `/extranet/services`

- **Gemeindezuordnung (`city_id`):** Bei einem auf genau eine Kirchengemeinde beschränkten Token wird `city_id` beim Anlegen (`POST /extranet/services`) automatisch vorbelegt, sofern es im Request ausgelassen wird. Abweichende `city_id`-Werte oder fremde `location_id`-Werte werden mit `403` bzw. `422` abgewiesen.
- **Zeitstempel & UTC:** Alle Zeitpunkte werden in der Datenbank in `UTC` gespeichert. Übergibt der Client ISO-8601-Werte mit Zeitzone (z. B. `2026-11-04T19:30:00+01:00` oder `…Z`), wird exakt nach `UTC` umgerechnet; reine Datums- oder Lokalzeitangaben ohne Offset (z. B. `2026-11-01` + optional `time: "10:00"`) werden als `Europe/Berlin` interpretiert und nach `UTC` konvertiert.
- **Partielle Updates (`PATCH`):** Nicht mitgesendete boolesche Felder (`hidden`, `needs_reservations`, `registration_active`, `is_allday`) sowie Sitzplatzfelder bleiben unverändert erhalten.
