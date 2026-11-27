# Sichtbarkeit von Pfarrer:innen im Urlaubsplaner – Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Sekretär:innen mit `pfarrer_urlaub_sehen` sehen im Urlaubsplaner nur Pfarrer:innen aus den ihnen zugewiesenen Pools; Pfarrer:innen behalten und erweitern ihre bestehende Pool-Sichtbarkeit.

**Architecture:** Eine separate `pool_user_visibility`-Pivot-Beziehung trennt Zugriff von Pool-Mitgliedschaft. `User::getViewableAbsenceUsers()` bildet die serverseitige Vereinigungsmenge aus bestehender City-/Rollenlogik, gemeinsamen Pools und zugewiesenen Sichtbarkeitspools. `groupable` bleibt reine Planner-Darstellung.

**Tech Stack:** Laravel/Eloquent, Laravel Permission, Vue/Inertia, PHPUnit, Vitest, Docker über `./planer`.

**Spec:** `docs/superpowers/specs/2026-11-27-pfarrer-urlaub-sichtbarkeit-design.md`

## Global Constraints

- Änderungen an Betreiber-Schnittstellen werden in `docs/admin/installation-und-migration.md` dokumentiert.
- Tests laufen ausschließlich über `./planer dev test` bzw. `./planer dev npm`.
- `user_pool` bleibt Pool-Mitgliedschaft; Zugriff wird ausschließlich über `pool_user_visibility` vergeben.
- `groupable` steuert keine Berechtigung.
- Keine `.env`, Backups oder Laufzeitdaten committen.

## Review Focus

- Ein Nicht-Pfarrer ohne `pfarrer_urlaub_sehen` darf trotz zugewiesenem Pool keine Pfarrer:innen sehen; Test in Task 2.
- Ein berechtigter Benutzer mit mehreren Pools erhält die Vereinigungsmenge ohne Duplikate; Test in Task 2.
- Ein Pfarrer sieht gemeinsame Poolmitglieder zusätzlich zur bestehenden City-Sichtbarkeit; Test in Task 2.
- Eine leere oder entfernte Poolzuweisung verändert keine Pool-Mitgliedschaften; Test in Task 1 und Task 3.
- Benutzereditor und Dokumentation müssen die neue Zugriffsebene klar von `groupable` und Pool-Mitgliedschaft unterscheiden; Prüfung in Task 3 und Task 4.

---

### Task 1: Datenmodell und Berechtigung

**Files:**
- Create: `src/database/migrations/*_create_pool_user_visibility_table.php`
- Modify: `src/app/Models/People/User.php`
- Modify: `src/app/Models/Leave/Pool.php`
- Modify: `src/config/roles.php`
- Test: `src/tests/Feature/RoleConfigurationFeatureTest.php` und passende User/Pool-Feature-Tests

- [ ] Failing tests for the unique user/pool visibility relation, inverse relations, and the `pfarrer_urlaub_sehen` permission.
- [ ] Add the reversible pivot migration with foreign keys, cascade deletes, and a unique `(user_id, pool_id)` constraint.
- [ ] Add typed Eloquent relations `visiblePools()` and `visibilityUsers()` without changing `pools()`.
- [ ] Add `pfarrer_urlaub_sehen` to the permission configuration and assign it only to the explicitly intended role(s), preserving existing role permissions unless the issue requires otherwise.
- [ ] Run the focused Docker tests and verify the migration rollback path.

### Task 2: Server-side absence visibility

**Files:**
- Modify: `src/app/Models/People/User.php` (`getViewableAbsenceUsers`)
- Test: `src/tests/Feature/AbsenceWebFeatureTest.php` or a focused absence visibility feature test

- [ ] Add failing tests for unauthorized secretaries, authorized secretaries with one and multiple visibility pools, deduplication, and pastors sharing a pool.
- [ ] Extend `getViewableAbsenceUsers()` with a server-side union: current user, existing staff/city visibility, existing pastor/city visibility, shared pools for pastors, and assigned visibility pools for users with `pfarrer_urlaub_sehen`.
- [ ] Keep non-pastor access to pastor records gated by `pfarrer_urlaub_sehen`; do not use `groupable` in the query.
- [ ] Load `pools` for the Planner response without exposing unrelated credentials or access data.
- [ ] Run the focused PHP feature tests and the complete relevant PHP suite.

### Task 3: User-editor assignment UI

**Files:**
- Modify: `src/app/Http/Controllers/UserController.php`
- Modify: `src/app/Http/Requests/UserRequest.php`
- Modify: `src/resources/js/Pages/Admin/User/UserEditor.vue`
- Test: User editor feature/JS tests covering load, save, and non-pastor visibility-pool assignment

- [ ] Add a failing editor test proving assigned visibility pools load and save independently of pool membership.
- [ ] Provide available pools and compact selected `visible_pools` IDs to the editor, restricting assignment through the existing user-management authorization.
- [ ] Validate and synchronize `visible_pools` without allowing unauthorized pool IDs or changing `user_pool` membership.
- [ ] Add clear German UI text explaining that this controls which pastors a non-pastor may see in the Urlaubsplaner.
- [ ] Hide or disable the control for pastor targets if the chosen policy is non-pastor-only, and cover that behavior with a test.
- [ ] Run focused PHP and JS tests.

### Task 4: Manuals and operational documentation

**Files:**
- Modify: `docs/manual/benutzerhandbuch/urlaubsplan.md`
- Modify: `docs/manual/administratorhandbuch/berechtigungen.md`
- Modify: `docs/manual/technikhandbuch/architektur.md` or the relevant technical page
- Modify: `docs/admin/installation-und-migration.md`

- [ ] Document the user-visible behavior, including pool-based pastor visibility and the distinction from `groupable` display grouping.
- [ ] Document the new permission and how administrators assign visibility pools to non-pastor users.
- [ ] Document the pivot data model, server-side enforcement, union semantics, and no-access behavior.
- [ ] Document migration execution, rollback considerations, and the fact that existing pool memberships are not changed.
- [ ] Run `./planer dev docs:check` and review all affected manual sections for consistent German terminology.

### Task 5: Final verification and delivery

- [ ] Run `git diff --check`, focused PHP/JS tests, full relevant PHP/JS suites, `./planer dev format:check`, `./planer dev analyse`, `./planer dev lint`, and `./planer dev docs:check`.
- [ ] Review the complete diff for authorization gaps and unintended changes to pool membership.
- [ ] Commit with a German Conventional Commit message referencing #164.
- [ ] Push `main`, comment the implementation and verification on GitHub #164, read the comment back, and close #164 after verification.
