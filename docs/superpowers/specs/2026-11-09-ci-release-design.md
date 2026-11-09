# CI- und Release-Automatisierung — Design

## Ziel

Pull Requests sollen dieselben Format-, Dokumentations-, Analyse-, Lint- und
Testprüfungen ausführen, die lokal über `./planer dev quality` vorgesehen sind.
Ein Release-Tag soll anschließend Handbuch und Docker-Produktionsimage im
GitHub-CI bauen und veröffentlichen, damit die Veröffentlichung nicht mehr von
einem lokalen Docker- und SSH-Arbeitsplatz abhängt.

## Umfang

- PR-Workflow um die vollständige Test-Suite ergänzen.
- Tag-Workflow für Handbuch-Build/-Deployment und Docker-Hub-Image-Push
  ergänzen.
- Lokalen Releasepfad auf Versionierung, Release-Commit und Tag-Erstellung
  begrenzen.
- Administratorhandbuch und technisches Handbuch um den CI-Releasepfad und die
  erforderlichen GitHub-Secrets ergänzen.
- Bestehende Release- und Shell-Tests an die neue Verantwortungsaufteilung
  anpassen.

Nicht im Umfang liegen Produktionsupdates, Datenbankmigrationen, Backups oder
die Veröffentlichung der Handbuchquellen in ein separates Repository.

## Ansätze

1. Die bisherigen lokalen Skripte unverändert lassen und einen Workflow nur als
   Wrapper aufrufen. Das lässt die entscheidenden lokalen Veröffentlichungs-
   abhängigkeiten bestehen.
2. Den Releasepfad in Versionierung/Tag und CI-Veröffentlichung trennen. Der
   bestehende `planer`-Einstieg bleibt erhalten, der Tag-Workflow ruft die
   vorhandenen Build- und Manual-Kommandos auf. Dieser Ansatz wurde gewählt,
   weil er die vorhandene Betriebslogik wiederverwendet und den CI-Übergang
   nachvollziehbar macht.
3. Einen vollständig neuen CI-spezifischen Builder schreiben. Das würde
   Docker-, Manual- und Betriebslogik duplizieren und ist deshalb verworfen.

## Entschiedene Architektur

- `quality.yml` startet bei Pull Requests und Pushes auf den Arbeitsbranch,
  baut die Docker-Entwicklungsumgebung, führt `./planer dev quality` und danach
  `./planer dev test` aus und räumt immer auf.
- `release.yml` startet ausschließlich bei Tags nach `v*`. Es baut das
  Handbuch, veröffentlicht es über die bestehende Rsync-Schnittstelle und
  baut/pusht `pfarrtools/pfarrplaner:<tag>` nach Docker Hub.
- Der Release-Commit und das Tag werden weiterhin lokal durch
  `./planer release` erstellt und nach GitHub gepusht. Der lokale Releasepfad
  führt keine Manual- oder Image-Veröffentlichung mehr aus.
- Secrets werden nicht im Repository abgelegt. Der Workflow benötigt ein
  Docker-Hub-Konto/Token sowie SSH-Schlüssel und Known-Hosts für das
  Handbuchziel.

## Erfolgskriterien

- Der PR-Workflow enthält die vollständige isolierte Test-Suite.
- Der Tag-Workflow ist ohne lokale Docker- oder SSH-Anmeldung ausführbar,
  sofern die dokumentierten Secrets gesetzt sind.
- `git diff --check` und die betroffenen Shell-Tests sind grün.
