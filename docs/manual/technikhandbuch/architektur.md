[//]: # (TOC: 2. Architektur und Domänen)

# Architektur und Domänen

## Backend-Struktur

Das Projekt folgt im Kern einer Laravel-Struktur, nutzt aber mehrere projektweite Konventionen:

- Modelle liegen domänenorientiert unter `app/Models/`
- Web- und API-Controller sind getrennt
- Geschäftslogik wandert häufig in `app/Actions/`
- Modelle registrieren ihre Routen selbst über `registerRoutes()` und `registerApiRoutes()`

## Zentrale Domänen

- **Gottesdienste und Veranstaltungen**: `Service`, Kalender, Mitwirkende, Werbung, Berichte
- **Kasualien**: Taufen, Trauungen, Beerdigungen
- **Benutzer und Rechte**: Personen, Rollen, Gemeindezuordnungen
- **Orte und Gemeinden**: Kirchengemeinden, Pfarrämter, Veranstaltungsorte, Sitzpläne
- **Urlaub und Vertretung**: Abwesenheiten, Pools, Poolmaster
- **Liturgie**: Bausteine, Lieder, Psalmen, liturgische Texte

## Soft-Delete-Domäne

Für `Service`, `Absence`, `Baptism`, `Funeral`, `Wedding` sowie fachlich abhängige `Replacement`- und `AdConfig`-Datensätze nutzt Pfarrplaner Soft Deletes.

Die fachliche Wirkung ist:

- normales Löschen verschiebt den Datensatz in den administrativen Papierkorb
- Standardabfragen zeigen diese Datensätze nicht mehr an
- bei angemeldeten Löschvorgängen wird das ausführende Benutzerkonto in `deleted_by` protokolliert
- Einträge bleiben 30 Tage im Papierkorb und werden danach täglich automatisch per `trash:prune` endgültig gelöscht
- Wiederherstellung und endgültiges Löschen laufen über eigene Admin-Flows

Bei `Service` werden die abhängigen Kasualien und `AdConfig`-Einträge mit soft-deleted; `Occurence` bleibt dagegen eine technische Projektion und wird beim Löschen entfernt und beim Restore neu aufgebaut.

Bei `Absence` werden die abhängigen `Replacement`-Datensätze mit soft-deleted und beim Restore wiederhergestellt.

Anhänge (`Attachment`) werden selbst nicht soft-deleted. Beim normalen Soft Delete des Elternobjekts bleiben ihre Datenbankeinträge und Dateien unverändert bestehen, damit sie beim Restore sofort wieder verfügbar sind. Beim `forceDelete()` des Elternobjekts werden die zugehörigen Attachment-Datensätze und ihre Dateien entfernt. Für Einträge im Papierkorb bleibt der normale Zugriff auf Anhänge gesperrt.

## Routing-Modell

- Web-Routen werden zentral geladen und um zusätzliche Teilrouten ergänzt.
- API-Routen liegen unter `routes/api/*.php`.
- Modelle können eigene Web- und API-Routen registrieren.

## Frontend-Modell

Das Frontend nutzt Inertia. Viele Seiten werden also serverseitig als Inertia-Responses erzeugt, während Vue die eigentliche Interaktion im Browser übernimmt.

Der Help-Button in der Topbar wird serverseitig mit einer Manual-Zuordnung versorgt und kann dadurch jetzt zwischen Benutzer- und Administratorhandbuch unterscheiden.

## Dokumentenerzeugung

Pfarrplaner erzeugt nicht nur HTML, sondern viele Ausgabeformate:

- PDF
- Word
- Excel
- HTML-Snippets
- Browser-basierte Renderings

Darum gehören Office-Bibliotheken, PDF-Rendering und Chromium-Laufzeit fest zur technischen Gesamtarchitektur.
