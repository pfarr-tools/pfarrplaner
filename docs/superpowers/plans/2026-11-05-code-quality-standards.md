# Plan: Verbindliche Qualitätsstandards für Pfarrplaner

**Ziel:** Issue #27 vollständig in drei sequenziellen Phasen umsetzen.

**Ansatz:** Vorhandene Docker-/Laravel-/Vite-Strukturen werden erweitert. Neue
Prüfungen sind reproduzierbar, getrennt von automatischer Formatierung und
schreibgeschützt im kombinierten Befehl. Bestehende Verstöße werden baselined
oder über geänderte Dateien schrittweise abgebaut.

## Phase 1 — Audit und Baselines

- [ ] Werkzeuge, Skripte, CI, Dokumentation und relevante Dateibestände
      inventarisieren.
- [ ] Konfigurationen für Pint, PHPStan/Larastan, ESLint und Prettier ergänzen.
- [ ] PHPDoc-/JSDoc-Prüfungen und Baselines implementieren.
- [ ] Komplexitätsprüfung als Warnung implementieren.
- [ ] Audit und Migrationsstrategie im technischen Handbuch dokumentieren.
- [ ] Root- und Anwendungs-AGENTS um verbindliche Qualitätsregeln ergänzen.

## Phase 2 — Docker und CI

- [ ] Composer-/npm-Abhängigkeiten und Lockdateien aktualisieren.
- [ ] `./planer dev` um format, format:check, analyse, lint, docs und quality
      ergänzen.
- [ ] GitHub-Action für PR-Qualität mit Caching, isolierten Tests und Vite-
      Produktionsbuild ergänzen.
- [ ] Relevante Tests und Builds ausführen; keine Produktions-/Dev-Datenbank
      verwenden.

## Phase 3 — Bereinigung und laufende Verbesserung

- [ ] Reine Stilformatierung getrennt anwenden und prüfen.
- [ ] Sponsoring-Hinweise entfernen und repositoryweit verifizieren.
- [ ] Neue/aktualisierte Baselines prüfen und offene Altlasten dokumentieren.
- [ ] Dokumentation, Diff-Check und Gesamtprüfung abschließen.

## Abschluss

- [ ] Review des Diffs und der Betriebs-/Migrationsdokumentation.
- [ ] Issue #27 im Commit referenzieren.
- [ ] Branch veröffentlichen und Ergebnisse mit frischen Prüfnachweisen
      berichten.
