# Moderne Format-Indexseiten Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Ein- und Ausgabeformate in einer einheitlichen, kompakten Werkzeugliste mit Suche und klarer Auswahl darstellen.

**Architecture:** Eine gemeinsame Vue-Komponente kapselt Kopfzeile, Suchfeld, kompakte Listenzeilen und getrennte leere Zustände. `Inputs/Index.vue` und `Reports/Index.vue` liefern weiterhin ihre bestehenden Daten und behalten ihre jeweiligen Routen-/Inertia-Entscheidungen. Das Backend bleibt unverändert.

**Tech Stack:** Vue 2, Inertia.js, Bootstrap-Klassen, Material Design Icons, Vitest und Vue Test Utils.

**Spec:** `docs/superpowers/specs/2026-11-11-format-index-pages-design.md`

## Global Constraints

- Keine neuen Backend-Endpunkte oder Datenbankänderungen.
- Bestehende Inertia-/Vue-Datenstrukturen und Routen weiterverwenden.
- Die Zeilen sind vollständig klickbare Links mit sichtbarem Fokuszustand.
- Deutsche UI-Texte verwenden korrekte Umlaute und `ß`.
- Änderungen bleiben uncommitted zur visuellen Prüfung.

## Review Focus

- Leere Suchergebnisse müssen verständlich angezeigt werden; Test in der gemeinsamen Komponente.
- Gruppierte Ausgabeformate dürfen durch Suche nicht ihre Gruppierung verlieren; Test in `Reports/Index`.
- Ausgabeformate ohne Inertia müssen weiterhin über die normale URL öffnen; Test in `Reports/Index`.
- Assistenten müssen weiterhin ihre bestehende Setup-Route aufrufen; Test in `Inputs/Index`.
- Zeilen müssen eine zugängliche Linkstruktur und einen sichtbaren Fokuszustand behalten; Test in der gemeinsamen Komponente.

### Task 1: Gemeinsame Formatlisten-Komponente

**Files:**
- Create: `src/resources/js/components/Ui/FormatIndex.vue`
- Test: `src/tests/js/components/Ui/FormatIndex.test.js`

**Interfaces:**
- Consumes: `title`, `subtitle`, `searchPlaceholder`, `groups` und `onSelect` als Props bzw. Callback.
- Produces: gefilterte, gruppierte Zeilen mit `data-format-row` und differenziertem Leerzustand.

- [ ] **Step 1: Write the failing test**

  Teste Suche über Titel und Beschreibung, beide Leerzustände, gruppierte Listendarstellung sowie `href`/Tastaturfokus der Zeilen.

- [ ] **Step 2: Run test to verify it fails**

  Run: `./planer dev test:js --run src/tests/js/components/Ui/FormatIndex.test.js`

  Expected: FAIL, weil die Komponente noch nicht existiert.

- [ ] **Step 3: Implement `FormatIndex.vue`**

  Erzeuge die gemeinsame Kopfzeile, kompakte zweispaltige Listendarstellung, klickbare Zeilen mit Icon/Titel/Beschreibung und verständliche Meldungen ohne verfügbare Einträge bzw. ohne Suchtreffer. Die Komponente filtert die übergebenen Gruppen ausschließlich clientseitig.

- [ ] **Step 4: Run test to verify it passes**

  Run: `./planer dev test:js --run src/tests/js/components/Ui/FormatIndex.test.js`

  Expected: PASS.

### Task 2: Assistenten-Index auf gemeinsame Darstellung umstellen

**Files:**
- Modify: `src/resources/js/Pages/Inputs/Index.vue`
- Test: `src/tests/js/Pages/Inputs/Index.test.js`

**Interfaces:**
- Consumes: `FormatIndex` mit einer Gruppe aus `inputs`.
- Produces: Auswahl über `route('inputs.setup', input.id)`.

- [ ] **Step 1: Write the failing test**

  Teste, dass die Eingabedaten als Karten angezeigt werden und ein Klick weiterhin die bestehende Setup-Route über Inertia aufruft.

- [ ] **Step 2: Run test to verify it fails**

  Run: `./planer dev test:js --run src/tests/js/Pages/Inputs/Index.test.js`

  Expected: FAIL, weil die Seite noch die alte Dataset-Struktur rendert.

- [ ] **Step 3: Implement the minimal page integration**

  Ersetze Dataset/Search/Pagination durch `FormatIndex`, mappe die vorhandenen Felder und halte die bestehende `createInput`-Route samt Inertia-Navigation.

- [ ] **Step 4: Run test to verify it passes**

  Run: `./planer dev test:js --run src/tests/js/Pages/Inputs/Index.test.js`

  Expected: PASS.

### Task 3: Ausgabeformat-Index auf gemeinsame Darstellung umstellen

**Files:**
- Modify: `src/resources/js/Pages/Reports/Index.vue`
- Test: `src/tests/js/Pages/Reports/Index.test.js`

**Interfaces:**
- Consumes: bestehende gruppierte `reports`-Prop und `FormatIndex`.
- Produces: Inertia-Navigation für Inertia-Reports sowie normale URL-Navigation für klassische Reports.

- [ ] **Step 1: Write the failing test**

  Teste Gruppierung, Suche über Titel/Beschreibung und beide bestehenden Navigationswege anhand der `inertia`-Eigenschaft.

- [ ] **Step 2: Run test to verify it fails**

  Run: `./planer dev test:js --run src/tests/js/Pages/Reports/Index.test.js`

  Expected: FAIL gegen die neue gemeinsame Komponentenschnittstelle.

- [ ] **Step 3: Implement the minimal page integration**

  Liefere die vorhandenen Gruppen an `FormatIndex`, verwende die bestehende `createReport`-Logik und entferne nicht mehr benötigte lokale Karten-/Gruppenmarkup- und Importlogik.

- [ ] **Step 4: Run test to verify it passes**

  Run: `./planer dev test:js --run src/tests/js/Pages/Reports/Index.test.js`

  Expected: PASS.

### Task 4: Gesamtprüfung

**Files:**
- Modify: `docs/superpowers/specs/2026-11-11-format-index-pages-design.md` only if implementation decisions require clarification.

- [ ] **Step 1: Run focused JavaScript tests**

  Run: `./planer dev test:js:run src/tests/js/components/Ui/FormatIndex.test.js src/tests/js/Pages/Inputs/Index.test.js src/tests/js/Pages/Reports/Index.test.js`

- [ ] **Step 2: Run formatting, lint and documentation checks**

  Run: `./planer dev format:check`, `./planer dev lint`, and `./planer dev docs:check`.

- [ ] **Step 3: Run diff validation**

  Run: `git diff --check` and inspect `git status --short` to confirm only intended uncommitted files changed.

- [ ] **Step 4: Hand off for visual review**

  Report changed files and verification results; do not commit or push.
