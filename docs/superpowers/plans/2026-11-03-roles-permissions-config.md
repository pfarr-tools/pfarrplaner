# Rollen und Berechtigungen aus der UI entfernen – Implementierungsplan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Die versionierte Konfiguration soll die zulässigen Rollen und Berechtigungen definieren; die Datenbank wird über einen Artisan-Befehl synchronisiert und die Rollen-CRUD-Oberfläche vollständig entfernt.

**Architecture:** `src/config/roles.php` wird die einzige fachliche Quelle. Ein dedizierter Synchronizer-Service verarbeitet diese Struktur; `roles:sync` stellt ihn als idempotenten Artisan-Befehl mit `--dry-run` bereit. Seeder, Erstinstallation und Bootstrap rufen denselben Mechanismus auf. Bestehende, nicht konfigurierte DB-Einträge bleiben im Standard erhalten.

**Tech Stack:** Laravel/PHP, Spatie Laravel Permission, Inertia/Vue, PHPUnit, Docker-gekapselte Pfarrplaner-Testbefehle über `./planer`.

**Spec:** [Rollen-/Berechtigungskonzept](../specs/2026-11-03-roles-permissions-config-design.md)

## Global Constraints

- Vor Änderungen den sauberen Arbeitsstand erneut prüfen und unabhängige lokale Änderungen erhalten.
- Keine `.env`, Laufzeitdaten, Backups, `vendor/` oder `node_modules/` anfassen.
- PHPUnit nicht direkt gegen die Entwicklungsdatenbank ausführen; Tests ausschließlich mit der vorgegebenen Isolation oder einer separaten Testdatenbank starten.
- Die Super-Administrator-Ausnahme in `AuthServiceProvider` und die Benutzerverwaltung müssen nach Entfernung der Rollenverwaltung unverändert funktionieren.
- Die vereinbarte Planung wird vor der Implementierung als Kommentar in Issue #6 dokumentiert.

## Review Focus

- Sind die Definitionen aus `RoleSeeder` und `InstallSetup` fachlich korrekt zusammengeführt?
- Ist der Abgleich wirklich idempotent und bleibt er standardmäßig nicht destruktiv?
- Wird der Synchronizer bei Erstinstallation, Bootstrap-Import und regulären Updates zuverlässig ausgeführt?
- Sind alle UI-, Route-, Controller-, Policy-, View- und Test-Reste der Rollenverwaltung entfernt oder bewusst aktualisiert?
- Bleiben Rollen-Zuweisungen in der Benutzerverwaltung und die Super-Admin-Gate-Ausnahme erhalten?

## Aufgaben

### 1. Konfigurationsquelle und Sync-Modell festlegen

- [ ] `src/config/roles.php` mit der vollständigen Permission-Liste und den Rollen-Zuordnungen aus der bisherigen fachlichen Struktur anlegen.
- [ ] Die Guard-Namen explizit auf `web` festlegen und die Super-Administrator-Rolle als besondere Gate-Rolle dokumentieren, ohne eine nicht unterstützte Wildcard-Permission einzuführen.
- [ ] Die Rollen-/Permission-Daten aus `RoleSeeder` und `InstallSetup` vergleichen; doppelte oder widersprüchliche Definitionen entfernen.
- [ ] Einen dedizierten Service, z. B. `RoleConfigurationSynchronizer`, mit klarer Ergebnisstruktur für angelegt, aktualisiert, unverändert und übersprungen implementieren.
- [ ] Den Sync so gestalten, dass konfigurierte Rollen ihre konfigurierten Permissions erhalten, nicht konfigurierte DB-Rollen und -Permissions aber nicht automatisch gelöscht werden.

### 2. Artisan-Befehl und Installationspfade

- [ ] `roles:sync` mit `--dry-run` implementieren; die Ausgabe muss Änderungen nachvollziehbar benennen und der Dry-Run darf keine Datenbankänderung durchführen.
- [ ] `RoleSeeder` auf die gemeinsame Konfiguration bzw. den Synchronizer umstellen, damit Seed und Betriebsbefehl nicht auseinanderlaufen.
- [ ] `install:setup`, `pfarrplaner:install` und `scripts/bootstrap-import.sh` auf den gemeinsamen Abgleich umstellen.
- [ ] Den regulären Updatepfad prüfen und den Sync dort an geeigneter Stelle ausführen, ohne `.env` oder Produktionsdaten außerhalb des bestehenden Updateablaufs zu verändern.
- [ ] Initiale Super-Admin-Erzeugung auf die zentrale Rollenkonstante bzw. Konfiguration ausrichten und die bestehende Gate-Ausnahme beibehalten.

### 3. Rollen-UI und nicht mehr benötigte Zugriffspunkte entfernen

- [ ] Den Rollenmenüeintrag aus `AdminModule` entfernen.
- [ ] `routes/web/admin/Roles.php` sowie `RoleController` und `RolePolicy` entfernen, sofern nach der Routen-/Menüprüfung keine Laufzeitreferenzen verbleiben.
- [ ] Inertia-Seiten `Admin/Role/*` und veraltete Blade-Rollenansichten entfernen.
- [ ] Rollenbezogene Browser-, Feature- und Policy-Tests entfernen oder in Tests für den neuen Sync-Befehl und das Fehlen der UI umwandeln.
- [ ] Verbleibende Rollenverwendung in der Benutzerverwaltung ausdrücklich prüfen; dort nur die Auswahl konfigurierter Rollen anbieten, falls die bestehende UI dies bereits voraussetzt.

### 4. Dokumentation und fokussierte Tests

- [ ] Das Administrator-/Betriebshandbuch um `roles:sync`, `--dry-run`, Bootstrap-/Update-Verhalten und den nicht destruktiven Standard ergänzen.
- [ ] Den technischen Abschnitt um die Konfigurationsdatei, Guard `web`, Super-Admin-Ausnahme und den fehlenden UI-Zugriff ergänzen.
- [ ] Unit-/Feature-Tests für Konfigurationsvalidierung, Sync, Idempotenz und Dry-Run schreiben.
- [ ] Tests für installierte Artisan-Kommandos sowie fehlende Rollenrouten und fehlenden Admin-Menüeintrag ergänzen.
- [ ] Mit `git diff --check` und den fokussierten Docker-isolierten Tests verifizieren; anschließend relevante Build-/Bootstrap-Prüfungen ausführen.

### 5. Abschlussprüfung

- [ ] `rg`-Prüfung auf alte Rollen-Definitionsquellen, CRUD-Routen und UI-Referenzen durchführen.
- [ ] Änderungen und Testresultate im Issue #6 kommentieren.
- [ ] Erst nach bestätigter fachlicher/realer Prüfung über Commit, Push und Issue-Abschluss entscheiden; Issue #6 nicht automatisch schließen, bevor der reale Test erfolgt ist.

## Alternativen und Entscheidung

1. **Empfohlen: zentrale Config plus Synchronizer-Service.** Trennt fachliche Daten von Laufzeitlogik, vermeidet die bisherige Doppelpflege und ist testbar.
2. **Nur `RoleSeeder` als Config-Wrapper.** Weniger Dateien, aber der Seeder bleibt als Betriebsmechanismus missverständlich und der Updatepfad wird schwerer explizit zu prüfen.
3. **Migration statt Sync-Befehl.** Für einmalige Datenänderungen geeignet, aber nicht für wiederholbare Bootstrap-/Update-Abgleiche und Dry-Run-Betrieb.

Empfohlen wird Variante 1. Die konkrete Konfigurationsliste bleibt vor der Implementierung der fachlichen Prüfung unterworfen, weil das Repository aktuell unterschiedliche Rollenstände enthält und kein Produktionsdatenbank-Abzug als verlässliche Quelle vorliegt.
