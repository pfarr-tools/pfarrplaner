# Pfarrplaner-Dokumentation

Dieses Verzeichnis enthält die Dokumentation des neuen Root-Repositories.

## Handbücher

Die drei fachlichen Handbücher liegen gemeinsam unter [`manual/`](manual/):

- [`manual/benutzerhandbuch/`](manual/benutzerhandbuch/) — Benutzerhandbuch
- [`manual/administratorhandbuch/`](manual/administratorhandbuch/) —
  Administratorhandbuch
- [`manual/technikhandbuch/`](manual/technikhandbuch/) — Technisches Handbuch
- [`manual/media/`](manual/media/) — gemeinsame Bilder, Lizenzen und Web-Assets

Der bestehende Manual-Build wird aus `src/` gestartet, verwendet aber diesen
gemeinsamen Root-Bestand. Der Pfad kann mit `PFARRPLANER_MANUAL_ROOT`
überschrieben werden. In Docker wird er automatisch als
`/var/www/docs/manual` eingebunden.

```sh
./planer dev manual all --skip-dusk
./planer dev manual site
./planer dev manual deploy --dry-run
```

Die wichtigsten Manual-Aktionen sind auch als eigenständige
Entwicklungsbefehle verfügbar, zum Beispiel `./planer dev manual:build` und
`./planer dev manual:deploy --dry-run`. Die verschachtelte Schreibweise
`./planer dev manual build` bleibt als kompatible Variante erhalten.

Für einen vollständigen Build einschließlich der Browser-Screenshot-Tests wird
`./planer dev manual all` verwendet. Die Veröffentlichung bleibt ein eigener,
bewusst expliziter Schritt:

```sh
./planer dev manual deploy
./planer dev manual source-publish
```

`deploy --dry-run` prüft das Rsync-Ziel ohne Dateien zu übertragen. Die
Variablen `MANUAL_DEPLOY_*` und `MANUAL_SOURCE_*` konfigurieren die jeweiligen
externen Ziele.

## Betriebsdokumentation

Das [Installations-, Migrations- und Betriebshandbuch](admin/installation-und-migration.md)
beschreibt die Docker-Struktur, Backups, Migrationen, Releases und Updates.
