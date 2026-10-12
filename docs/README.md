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
./planer manual all --skip-dusk
./planer manual site
./planer manual deploy --dry-run
```

Für einen vollständigen Build einschließlich der Browser-Screenshot-Tests wird
`./planer manual all` verwendet. Die Veröffentlichung bleibt ein eigener,
bewusst expliziter Schritt:

```sh
./planer manual deploy
./planer manual source-publish
```

`deploy --dry-run` prüft das Rsync-Ziel ohne Dateien zu übertragen. Die
Variablen `MANUAL_DEPLOY_*` und `MANUAL_SOURCE_*` konfigurieren die jeweiligen
externen Ziele.

## Betriebsdokumentation

Das [Installations-, Migrations- und Betriebshandbuch](admin/installation-und-migration.md)
beschreibt die Docker-Struktur, Backups, Migrationen, Releases und Updates.
