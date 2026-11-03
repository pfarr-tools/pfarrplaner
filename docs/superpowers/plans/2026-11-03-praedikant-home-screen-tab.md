# HomeScreen-Tab für offene Prädikant:innen-Gottesdienste Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Einen geschützten HomeScreen-Tab bereitstellen, in dem berechtigte Nutzer:innen offene zukünftige Gottesdienste sehen, nach Gemeinde filtern und sich als Pfarrer:in eintragen können.

**Architecture:** Der Tab wird wie die bestehenden HomeScreen-Tabs automatisch aus einer PHP-Klasse und einer Vue-Komponente erzeugt. Die serverseitige Query begrenzt Daten immer auf sichtbare Gemeinden; die Vue-Komponente filtert die gelieferte Liste und speichert die Auswahl im bestehenden `homeScreenTabsConfig`-Setting. Ein eigener API-Endpunkt übernimmt die erneute Berechtigungs- und Offenheitsprüfung sowie die atomare Zuordnung.

**Tech Stack:** Laravel/PHP, Eloquent, Spatie Permission, Inertia/Vue 3, PHPUnit 11, Vitest.

**Spec:** `docs/superpowers/specs/2026-11-03-praedikant-home-screen-tab-design.md`

## Global Constraints

- Der Tab ist nur für `Prädikant:in`, `Gottesdienstbearbeiter:in` und `Pfarrer:in` verfügbar.
- Es werden nur zukünftige Gottesdienste in sichtbaren Gemeinden mit `need_predicant = true` und ohne Kategorie `P` geliefert.
- Gespeicherte Gemeinde-IDs dürfen die serverseitig sichtbaren Gemeinden nicht erweitern.
- `need_predicant` wird erst nach erfolgreicher Zuordnung des aktuellen Nutzers als `P` gelöscht.
- Keine Änderung des bestehenden allgemeinen Kalender-Selbsteintrags.

## Review Focus

- Ein Nutzer ohne eine der drei Rollen darf den Tab weder auswählen noch den Übernahme-Endpunkt verwenden; Test in Task 2.
- Ein gespeicherter Filter mit fremden Gemeinde-IDs darf keine fremden Gottesdienste liefern; Test in Task 2.
- Vergangene, bereits besetzte oder nicht als Prädikant:innenbedarf markierte Gottesdienste bleiben ausgeschlossen; Query-Test in Task 2.
- Zwei Übernahmen desselben Gottesdienstes dürfen keine doppelte Zuordnung oder falschen Flag-Zustand erzeugen; Transaktionstest in Task 3.
- Nach dem Filtern und nach erfolgreicher Übernahme müssen UI-Liste, Zähler und Benutzereinstellung konsistent bleiben; Vue-Test in Task 4.

### Task 1: Rolle und Testgrundlagen

**Files:**
- Modify: `src/app/Services/RoleService.php`
- Modify: `src/database/seeds/RoleSeeder.php`
- Modify: `src/app/Console/Commands/Install/InstallSetup.php`
- Create: `src/database/migrations/2026_11_03_000000_add_predicant_role.php`
- Test: `src/tests/Unit/Services/RoleServiceUnitTest.php`

- [ ] **Step 1: Write the failing role test** — `ROLE_PREDICANT` muss `Prädikant:in` liefern.
- [ ] **Step 2: Run the focused unit test and verify it fails.**
- [ ] **Step 3: Add the role constant and make the seeder, installer, and migration idempotently create the role without extra permissions.**
- [ ] **Step 4: Run the focused unit test and migration/seeder checks.**

### Task 2: Tab-Daten und geschützter Übernahme-Endpunkt

**Files:**
- Create: `src/app/HomeScreen/Tabs/PredicantServicesHomeScreenTab.php`
- Create: `src/app/Http/Resources/HomeScreen/PredicantServicesTabResource.php`
- Modify: `src/routes/api/Service.php`
- Modify: `src/app/Http/Controllers/Api/ServiceController.php`
- Test: `src/tests/Feature/PredicantServicesFeatureTest.php`

**Interfaces:**
- Produces tab data with `services`, visible `cities`, `count`, and the configured city filter.
- Produces `POST api.service.claim-predicant/{service}` accepting no user-controlled participant ID.

- [ ] **Step 1: Write failing feature tests** for role-gated tab availability, filtered future-service query, visible-city enforcement, and successful/invalid claim behavior.
- [ ] **Step 2: Run the focused feature test and verify the expected failures.**
- [ ] **Step 3: Implement the tab query and resource using `CalendarServiceResource`; use `Auth::user()->visibleCities` and ignore invalid configured city IDs.**
- [ ] **Step 4: Implement the authenticated claim action in a transaction with row locking, role check, visible-city check, future/open checks, `pastors()->syncWithoutDetaching()`, and `need_predicant = false`.**
- [ ] **Step 5: Run the focused feature test and verify it passes.**

### Task 3: Vue tab, filter persistence, and claim interaction

**Files:**
- Create: `src/resources/js/components/HomeScreen/PredicantServicesTab.vue`
- Create: `src/resources/js/components/Profile/TabConfig/PredicantServicesTabConfig.vue`
- Modify: `src/resources/js/Pages/HomeScreen.vue`
- Modify: `src/resources/js/components/Profile/Tabs/HomeScreenConfigurationTab.vue`
- Test: `src/tests/js/components/HomeScreen/PredicantServicesTab.test.js`

- [ ] **Step 1: Write the failing component tests** for visible services, city filter, setting persistence, and claim-button removal.
- [ ] **Step 2: Run the focused Vitest test and verify it fails.**
- [ ] **Step 3: Implement the tab list, empty state, city filter, and `Übernehmen` action against the dedicated API route.**
- [ ] **Step 4: Add the tab configuration component and register it with the existing configuration-tab mapping.**
- [ ] **Step 5: Register/import the new HomeScreen component and persist the `cities` filter through `setUserSetting('homeScreenTabsConfig', ...)`.**
- [ ] **Step 6: Run the focused Vitest test and verify it passes.**

### Task 4: Full verification and documentation review

**Files:**
- Modify: documentation only if the user-visible workflow is not already covered by the manuals.

- [ ] **Step 1: Run `git diff --check`.**
- [ ] **Step 2: Run the focused PHPUnit and Vitest tests.**
- [ ] **Step 3: Run the relevant full PHP/JS test commands or record environment blockers separately.**
- [ ] **Step 4: Review the three manuals and update only where this new user-visible workflow requires documentation.**
- [ ] **Step 5: Create the final German Conventional Commit referencing Issue #1 and close the issue with `fixed`/`close` in the commit message/body workflow.**
