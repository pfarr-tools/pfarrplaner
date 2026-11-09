# Erweiterte Quelltextformatierung für Issue #27 – Implementierungsplan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** CSS/SCSS/Sass und Blade-Templates werden über die bestehenden Docker-Quality-Befehle automatisch formatiert und geprüft.

**Architecture:** Prettier bleibt der Formatter für JavaScript, Vue, Skripte und Stylesheets. `blade-formatter` wird als npm-Entwicklungsabhängigkeit mit eigener Konfiguration und einem getrennten Changed-File-Runner für `*.blade.php` eingebunden. `./planer dev format` und `format:check` orchestrieren beide Formatter; Dokumentation und AGENTS-Regeln beschreiben die Zuständigkeiten.

**Tech Stack:** Node/npm, Prettier 3, blade-formatter, Laravel Docker-Compose, Bash, Markdown.

**Spec:** `docs/superpowers/specs/2026-11-09-issue-27-template-formatting-design.md`

## Global Constraints

- Qualitätswerkzeuge laufen ausschließlich über Docker via `./planer dev`.
- `.env`, Datenbanken, Backups, `vendor/`, `node_modules/`, Laufzeitdaten und generierte Builds dürfen nicht verändert oder committet werden.
- Prettier formatiert nur Quellpfade; `resources/views` bleibt von Prettier ausgeschlossen.
- Blade wird mit PHP 8.4, LF, vier Leerzeichen und maximal 120 Zeichen formatiert.
- Bestehende fachliche Template- und CSS-Strukturen werden nicht manuell umgebaut.

## Review Focus

- Blade-Direktiven und eingebettetes PHP bleiben syntaktisch unverändert; prüfe den Formatter-Lauf und einen repräsentativen Mail-/Print-/Web-Template-Satz.
- Geänderte CSS-/SCSS-Dateien werden im Quality-Lauf erkannt; prüfe den Changed-File-Filter mit einer geänderten und einer unveränderten Datei.
- Unveränderte Branches ohne passende Dateien bestehen die Formatprüfung ohne künstliche Eingabedateien.
- Vollständige Formatierung erfasst auch `public/landing/assets` und `public/css`, nicht nur `resources/`.
- CI und lokale Docker-Prüfung verwenden dieselben npm-Skripte und dieselbe Lockdatei.

### Task 1: Formatter-Abhängigkeit und Konfiguration

**Files:**
- Modify: `src/package.json`
- Modify: `src/package-lock.json`
- Create: `src/.bladeformatterrc.json`
- Modify: `src/.prettierignore`

**Interfaces:**
- Produces npm scripts for full and check-only CSS/Blade formatting.
- Produces the locked `blade-formatter` executable used by later runner tasks.

- [ ] **Step 1: Add the locked development dependency and scripts**

  Add `blade-formatter` to `devDependencies`; add scripts for full Blade write/check runs and include all maintained CSS/SCSS/Sass source directories in the Prettier write command.

- [ ] **Step 2: Add formatter configuration**

  Configure `.bladeformatterrc.json` with LF, four-space indentation, 120-character wrapping, PHP 8.4, no Tailwind sorting, and the project’s Blade component prefixes. Keep Blade paths ignored by Prettier.

- [ ] **Step 3: Verify dependency resolution**

  Run: `./planer dev npm install --package-lock-only`

  Expected: `src/package-lock.json` resolves the dependency without changing application dependencies or unrelated lock entries.

### Task 2: Changed-file formatter runners and orchestration

**Files:**
- Modify: `src/scripts/run-changed-tool.mjs`
- Create: `src/scripts/run-changed-blade-tool.mjs`
- Modify: `src/package.json`
- Modify: `planer`

**Interfaces:**
- `run-changed-tool.mjs` accepts the existing formatter/linter command and filters JS/Vue plus CSS/SCSS/Sass extensions.
- `run-changed-blade-tool.mjs` invokes a supplied command only for changed `*.blade.php` files and returns zero when none are in scope.

- [ ] **Step 1: Extend the generic changed-file runner**

  Include `.css`, `.scss`, and `.sass` while preserving the existing command argument order and no-files success behavior.

- [ ] **Step 2: Implement the Blade changed-file runner**

  Reuse `changedFiles()`; filter `*.blade.php`, invoke the passed command with those paths, inherit stdio, and return the child exit status.

- [ ] **Step 3: Wire check scripts and `./planer`**

  Make the Prettier check use the extended runner, add a Blade check script using the new runner, run both checks from `./planer dev format:check`, and run both write commands from `./planer dev format`.

- [ ] **Step 4: Verify command behavior**

  Run: `./planer dev npm run format:check`

  Expected: the command reports only relevant changed files and exits successfully when the checkout is formatted.

### Task 3: Format source files

**Files:**
- Modify: `src/resources/css/**/*.css`
- Modify: `src/resources/sass/**/*.scss`
- Modify: `src/public/css/**/*.css`
- Modify: `src/public/landing/assets/css/**/*.css`
- Modify: `src/public/landing/assets/scss/**/*.scss`
- Modify: `src/resources/views/**/*.blade.php`

**Interfaces:**
- Produces formatter-normalized source files consumed by the existing application and build.

- [ ] **Step 1: Run the Docker-native full formatter**

  Run: `./planer dev format`

  Expected: only formatter changes in maintained CSS/SCSS/Sass/Blade and already-supported source files.

- [ ] **Step 2: Review the generated diff**

  Run: `git diff --check` and inspect the changed template/style files for altered directives, PHP expressions, URLs, embedded email markup, and print-layout structure.

- [ ] **Step 3: Re-run the formatter in check mode**

  Run: `./planer dev format:check`

  Expected: all changed formatter inputs pass without writes.

### Task 4: Document the standard and verify quality

**Files:**
- Modify: `AGENTS.md`
- Modify: `src/AGENTS.md`
- Modify: `docs/manual/technikhandbuch/codequalitaet-und-workflow.md`
- Modify: GitHub issue #27 comment via `gh`

**Interfaces:**
- Documents the formatter ownership, supported extensions, exclusions, Docker commands, and manual review boundary.

- [ ] **Step 1: Update repository and application standards**

  State that Prettier owns JS/Vue/scripts/CSS/SCSS/Sass, blade-formatter owns Blade, and both formatters are mandatory through `./planer dev format` and `format:check`.

- [ ] **Step 2: Update the technical manual**

  Extend the quality command table and workflow with the new file classes, configuration files, changed-file behavior, and the rule that formatting must not change template semantics.

- [ ] **Step 3: Comment on issue #27**

  Record the implementation scope, formatter choices, and verification results without closing the issue.

- [ ] **Step 4: Run the complete relevant verification**

  Run: `git diff --check`; `./planer dev format:check`; `./planer dev analyse`; `./planer dev lint`; `./planer dev docs:check`; `./planer dev test --no-browser --no-js`; `./planer dev npm run test:js:run`; `./planer dev npm run build`.

  Expected: all commands pass; build warnings are reported separately if they are pre-existing and non-blocking.
