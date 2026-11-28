[//]: # (TOC: 3. Architektur und Domänen)

# Architektur und Domänen

## Stack-Struktur

Der technische Rahmen besteht aus zwei Compose-Varianten:

- `compose.yaml` bindet den Laravel-Quellbaum für Entwicklung, Vite, Tests und den Manual-Container ein.
- `compose.production.yaml` verwendet ein versioniertes Anwendungsimage, Octane/Swoole, einen Asset-Snapshot und getrennte interne Netzwerke.

In beiden Varianten sind MariaDB, Redis, MinIO, Horizon und der Scheduler zentrale Abhängigkeiten. Caddy nimmt HTTP entgegen; Soketi ergänzt die WebSocket-Kommunikation. Serviceänderungen werden als schlanke Invalidierungen auf privaten Gemeinde-Kanälen übertragen. Der Browser lädt die betroffene Service-Repräsentation anschließend einzeln über die autorisierte API; nicht registrierte Ansichten verwenden einen Inertia-Fallback. Liturgische Jahreskalender werden bei fehlendem `liturgy/<yyyy>.json` beim ersten Zugriff automatisch von der Kirchenjahr-API geladen und im konfigurierten Storage abgelegt. Das [Installationskapitel](installation-docker.md) beschreibt den vollständigen Startablauf.

## Backend-Struktur

Das Projekt folgt im Kern einer Laravel-Struktur, nutzt aber mehrere projektweite Konventionen:

- Modelle liegen domänenorientiert unter `app/Models/`
- Web- und API-Controller sind getrennt
- Geschäftslogik wandert häufig in `app/Actions/`
- Modelle registrieren ihre Routen selbst über `registerRoutes()` und `registerApiRoutes()`

## Zentrale Domänen

- **Gottesdienste und Veranstaltungen**: `Service`, Kalender, Mitwirkende, Werbung, Berichte
- **Kasualien**: Taufen, Trauungen, Beerdigungen
- **Benutzer und Rechte**: Personen, versionierte Rollen-/Berechtigungskonfiguration und Gemeindezuordnungen

Die fachliche Rollenstruktur liegt in `config/roles.php`. Der Befehl
`roles:sync` gleicht die konfigurierten Rollen und Berechtigungen idempotent mit
der Datenbank ab. Der Standard ist nicht destruktiv: unbekannte bestehende
Einträge werden nicht automatisch gelöscht. Die Rolle
`Super-Administrator:in` bleibt als Gate-Ausnahme erhalten.
- **Orte und Gemeinden**: Kirchengemeinden, Pfarrämter, Veranstaltungsorte, Sitzpläne
- **Urlaub und Vertretung**: Abwesenheiten, Pools, Poolmaster
- **Liturgie**: Bausteine, Lieder, Psalmen, liturgische Texte

## Sichtbarkeitsmodell des Urlaubsplaners

Die Pool-Mitgliedschaft (`pool_user`) und die Leseberechtigung für
Pfarrer:innen sind bewusst getrennt. Die zusätzliche Pivot-Tabelle
`pool_user_visibility` verbindet ein Benutzerkonto mit den Pools, deren
Pfarrer:innen es im Urlaubsplan sehen darf. Beide Fremdschlüssel löschen ihre
Pivotzeilen beim Löschen des jeweiligen Kontos oder Pools; die Kombination aus
`user_id` und `pool_id` ist eindeutig.

`User::getViewableAbsenceUsers()` setzt die serverseitige Vereinigungsmenge
zusammen. Sie umfasst die eigene Person, die bestehende Sichtbarkeit von
Mitarbeitenden und Gemeinden, gemeinsame Pools für Pfarrer:innen und
Diakon:innen sowie – bei `pfarrer_urlaub_lesen` – die Pfarrer:innen aus den
explizit zugewiesenen `visiblePools`. Ohne `manage_absences` wird weiterhin
keine Urlaubsplanliste geladen. Die Abfrage verwendet `groupable` nicht;
dieses Feld ist ausschließlich ein Darstellungsmerkmal des Vue-Urlaubsplaners.

Die Benutzerverwaltung synchronisiert `visiblePools` separat von `pools` und
filtert die Auswahl auf den Verwaltungsbereich des ausführenden Kontos. Die
API-Antwort des Urlaubsplaners enthält anschließend nur die autorisierten
Benutzer:innen und lädt deren normale Poolmitgliedschaften für die Anzeige.

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
