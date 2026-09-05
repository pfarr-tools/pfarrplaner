# Pfarrplaner Docker

This repository contains the Docker-based Pfarrplaner runtime and operations
layer. The Laravel application lives under `src/` and is maintained as an
imported subtree of the original Pfarrplaner source repository.

## Development

```sh
./planer bootstrap
./planer up
```

See [the installation and migration manual](docs/admin/installation-und-migration.md)
for development, production, backups, upgrades, data synchronization, and
migration from an existing installation.
