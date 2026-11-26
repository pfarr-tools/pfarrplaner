# Pfarrer:innen im Urlaubsplaner nach Pools gruppieren – Umsetzungsplan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Pfarrer:innen im Urlaubsplaner anhand ausdrücklich als gruppierbar markierter Pools in eigene Abschnitte einteilen.

**Architecture:** Pools erhalten ein persistiertes Boolean-Feld `groupable`, das über die bestehende Pool-Administration gepflegt wird. Der Planner lädt die Gruppierungsinformation über die bereits ausgelieferte Pool-Relation und erzeugt daraus zusätzliche Pfarrer:innen-Abschnitte; Pfarrer:innen ohne gruppierbaren Pool bleiben in einem allgemeinen Abschnitt.

**Tech Stack:** Laravel 11, PHP 8.4, MariaDB/SQLite-Migrationen, Inertia.js, Vue 3, PHPUnit/Vitest, Docker-Runner `./planer`.

**Spec:** GitHub Issue #163 – Pfarrer:innen im Urlaubsplaner nach Pools gruppieren.

## Global Constraints

- Neue oder geänderte PHP-Deklarationen erhalten aussagekräftige PHPDoc-Blöcke.
- Neue oder geänderte Vue-/JavaScript-Deklarationen erhalten JSDoc-Blöcke.
- Tests laufen ausschließlich über `./planer` und niemals gegen die Entwicklungsdatenbank.
- Deutsche Benutzertexte verwenden korrekte Umlaute und `ß`.
- Änderungen an der Pool-Administration und am Planner werden durch fokussierte Tests abgesichert.

## Review Focus

- Ein Pool ohne Gruppierungskennzeichen darf keine neue Planner-Gruppe erzeugen.
- Eine Person ohne gruppierbaren Pool muss weiterhin sichtbar bleiben.
- Mehrfachzugehörigkeit zu gruppierbaren Pools darf keine unkontrollierten Endlosschleifen oder fehlerhaften Schlüssel erzeugen.
- Die bestehende Sichtbarkeits-/Pin-Logik für Pfarrer:innen und Mitarbeitende muss erhalten bleiben.
- Bestehende Pools müssen nach der Migration mit dem bisherigen Verhalten kompatibel bleiben.

### Task 1: Pool-Gruppierung persistieren und administrieren

**Files:**
- Create: `src/database/migrations/*_add_groupable_to_pools.php`
- Modify: `src/app/Models/Leave/Pool.php`
- Modify: `src/resources/js/Pages/Admin/Pool/Editor.vue`
- Test: `src/tests/Feature/PoolWebFeatureTest.php` (oder bestehender passender Pool-Feature-Test)

- [ ] Failing test: Pool-Editor akzeptiert `groupable` und speichert es; der Default neuer Pools ist `false`.
- [ ] Test rot ausführen.
- [ ] Migration, Fillable-/Validierungsdefinition und Checkbox im Editor ergänzen.
- [ ] Fokussierten Pool-Test grün ausführen.

### Task 2: Gruppierte Planner-Abschnitte erzeugen

**Files:**
- Modify: `src/resources/js/Pages/Absences/Planner.vue`
- Modify: `src/tests/Feature/AbsenceWebFeatureTest.php`
- Test: `src/tests/js/Pages/Absences/Planner.test.js`

- [ ] Failing test: `sortUsers` erzeugt für gruppierbare Pools benannte Abschnitte, lässt nicht gruppierbare Pools außen vor und hält nicht zugeordnete Pfarrer:innen sichtbar.
- [ ] Test rot ausführen.
- [ ] Pool-Abschnitte deterministisch aus den gelieferten Pool-Daten erzeugen und vorhandene Pin-/Sichtbarkeitslogik daran anschließen.
- [ ] Fokussierten JavaScript- und Feature-Test grün ausführen.

### Task 3: Gesamtprüfung und Dokumentation

**Files:**
- Modify: `docs/superpowers/plans/2026-11-26-pfarrerinnen-nach-pools-gruppieren.md`

- [ ] `git diff --check` ausführen.
- [ ] Relevante Docker-Tests sowie `./planer dev format:check`, `./planer dev lint` und `./planer dev docs:check` ausführen.
- [ ] Nur falls sich ein dokumentierter Benutzer- oder Administrationsablauf ändert, die passenden Handbuchstellen ergänzen.
