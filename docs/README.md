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
./planer npm run manual:all:server
./planer npm run manual:site
```

## Betriebsdokumentation

Das [Installations-, Migrations- und Betriebshandbuch](admin/installation-und-migration.md)
beschreibt die Docker-Struktur, Backups, Migrationen, Releases und Updates.
