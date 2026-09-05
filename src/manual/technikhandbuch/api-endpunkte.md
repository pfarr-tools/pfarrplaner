[//]: # (TOC: 7. API-Endpunkte)

# API-Endpunkte

Dieses Kapitel beschreibt die aktuell sichtbaren API-Routen aus `routes/api/*.php`. Für neue Integrationen sollten Sie zusätzlich die Controller und nach Möglichkeit die generierte OpenAPI-Spezifikation prüfen.

## Allgemeine Hinweise

- Basispräfix: `/api`
- Antwortformat ist in der Regel JSON.
- Alle `/api`-Endpunkte sind standardmäßig mit `auth:api` geschützt.
- Die einzige dokumentierte öffentliche Ausnahme ist derzeit `/api/health`.
- Nicht jede Route ist bereits vollständig mit OpenAPI-Attributen beschrieben.

## Health

| Methode | Pfad | Schutz | Zweck |
|---|---|---|---|
| `GET` | `/api/health` | öffentlich | einfacher Health-Check für Anwendung und Datenbank |

## Kalender und Veranstaltungen

| Methode | Pfad | Schutz | Zweck |
|---|---|---|---|
| `GET` | `/api/cal/city/{city}/{date}` | `auth:api` | Kalenderdaten für Gemeinde und Monat |
| `GET` | `/api/quick-pick/{date}` | `auth:api` | Schnellansicht für ein Datum |
| `GET` | `/api/kalender/monat/{date}` | `auth:api` | Monatsansicht |
| `GET` | `/api/kalender/gottesdienst/{service}` | `auth:api` | Details zu einem Gottesdienst im Kalenderkontext |
| `GET` | `/api/veranstaltungen/{calendar}/{start}/{end}` | `auth:api` | Veranstaltungen in einem Bereich |

## Gottesdienste

| Methode | Pfad | Schutz | Zweck |
|---|---|---|---|
| `GET` | `/api/servicesByDayAndCity/{day}/{city}` | `auth:api` | Gottesdienste eines Tages und einer Gemeinde |
| `GET` | `/api/user/{user}/services` | `auth:api` | Gottesdienste einer Person |
| `GET` | `/api/service/{service}` | `auth:api` | Detailansicht eines Gottesdiensts |
| `PATCH` | `/api/service/{service}` | `auth:api` | Gottesdienst aktualisieren |
| `DELETE` | `/api/service/{service:slug}` | `auth:api` | Gottesdienst löschen |
| `POST` | `/api/service/{service}/assign` | `auth:api` | Personen oder Dienste zuordnen |
| `GET` | `/api/gottesdienste/{date}/{cities}` | `auth:api` | Monatsabfrage für mehrere Gemeinden |

## Liturgie und Texte

| Methode | Pfad | Schutz | Zweck |
|---|---|---|---|
| `POST` | `/api/liturgy/tree/save/{service}` | `auth:api` | Sortierung des Liturgiebaums speichern |
| `POST` | `/api/liturgy/service/{service}/import/{source}` | `auth:api` | Liturgie aus Quelle importieren |
| `POST` | `/api/liturgy/service/{service}/blocks` | `auth:api` | Liturgieblock anlegen |
| `PATCH` | `/api/liturgy/block/{block}` | `auth:api` | Liturgieblock ändern |
| `DELETE` | `/api/liturgy/block/{block}` | `auth:api` | Liturgieblock löschen |
| `POST` | `/api/liturgy/block/{block}/items` | `auth:api` | Liturgieelement anlegen |
| `PATCH` | `/api/liturgy/item/{item}` | `auth:api` | Liturgieelement ändern |
| `DELETE` | `/api/liturgy/item/{item}` | `auth:api` | Liturgieelement löschen |
| `POST` | `/api/liturgy/item/{item}/assign` | `auth:api` | Verantwortliche für Element setzen |
| `GET` | `/api/liturgy/texts/list` | `auth:api` | Liste liturgischer Texte |
| `POST` | `/api/liturgy/text/import` | `auth:api` | Text aus Word importieren |
| `GET` | `/api/liturgy/sources/{serviceId}` | `auth:api` | Importquellen für Liturgie |
| `GET` | `/api/texte/liste` | `auth:api` | Liste liturgischer Texte |

## Lieder und Gesangbücher

| Methode | Pfad | Schutz | Zweck |
|---|---|---|---|
| `GET` | `/api/liturgy/songs` | `auth:api` | Liedliste |
| `GET` | `/api/liturgy/songselect` | `auth:api` | reduzierte Liedauswahl |
| `GET` | `/api/liturgy/songs/songbooks` | `auth:api` | zugehörige Gesangbücher |
| `PATCH` | `/api/liturgy/songs/{song}` | `auth:api` | Lied ändern |
| `GET` | `/api/liturgie/lied/{song}/noten/{verses?}/{lineNumber?}` | `auth:api` | Noten-/Musikdarstellung |
| `GET` | `/api/liturgy/song/{songReferenceId}` | `auth:api` | einzelnes Lied |
| `POST` | `/api/liturgy/songs` | `auth:api` | Lied anlegen |
| `GET` | `/api/songbooks/colors` | `auth:api` | Farbzuordnung für Gesangbücher |

## Personen, Teams und Gemeinden

| Methode | Pfad | Schutz | Zweck |
|---|---|---|---|
| `GET` | `/api/people/select` | `auth:api` | Personenauswahl |
| `POST` | `/api/people/search/{searchString}` | `auth:api` | Personensuche |
| `POST` | `/api/people/activate/{user}/{city}` | `auth:api` | Person für Gemeinde aktivieren |
| `GET` | `/api/teams/by-city/{city}` | `auth:api` | Teams einer Gemeinde |
| `GET` | `/api/city/{city}/konfiapp-types` | `auth:api` | KonfiApp-Typen einer Gemeinde |

## Urlaub, Pools und Vertretung

| Methode | Pfad | Schutz | Zweck |
|---|---|---|---|
| `POST` | `/api/absence/{absence}/set-checked` | `auth:api` | Abwesenheit als geprüft markieren |
| `POST` | `/api/absence/{absence}/set-approved` | `auth:api` | Abwesenheit genehmigen |
| `DELETE` | `/api/absence/{absence}` | `auth:api` | Abwesenheit löschen |
| `GET` | `/api/pools/{pool}/poolmasters/{date}` | `auth:api` | zuständige Poolmaster für Datum |
| `GET` | `/api/pools/mastered/{user}/{date}` | `auth:api` | von Person betreute Pools |

## Berichte, Tabs und Hilfsdienste

| Methode | Pfad | Schutz | Zweck |
|---|---|---|---|
| `MATCH GET,POST` | `/api/report/{report}/{step}` | `auth:api` | Reportschritte |
| `GET` | `/api/tab/{tab}` | `auth:api` | Tab-Inhalt |
| `GET` | `/api/tab/{tab}/count` | `auth:api` | Zähler für Tab |
| `GET` | `/api/inbox` | `auth:api` | Upload- oder Posteingangsübersicht |
| `GET` | `/api/ministries/list` | `auth:api` | Dienstekatalog |
| `GET` | `/api/pixabay/query/{query}/{page?}` | `auth:api` | Pixabay-Suche |
| `GET` | `/api/rites/find/{query}` | `auth:api` | Suche in Kasualien |
| `DELETE` | `/api/occurence/{occurence}` | `auth:api` | einzelnes Vorkommen löschen |

## Anmerkungen zur Pflege

- Öffentliche API-Routen dürfen nur noch mit ausdrücklicher fachlicher Begründung angelegt werden. Standard ist immer `auth:api`.
- Die Route-Definitionen allein beschreiben noch nicht alle Validierungsdetails. Für Payloads und Seiteneffekte sind die jeweiligen Controller und Requests maßgeblich.
