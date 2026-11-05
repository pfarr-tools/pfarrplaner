# Design: Verbindliche Qualitätsstandards für Pfarrplaner

## Ziel

Issue #27 erhält eine Docker-native Qualitätsstrecke, die bestehende Altlasten
nachvollziehbar erfasst und neue Verstöße bei geänderten Dateien verhindert.
Die Anwendung bleibt fachlich unverändert; Architektur- und Formatierungsarbeit
werden getrennt behandelt.

## Gewählte Lösung

- Composer: Laravel Pint mit Laravel-Preset sowie PHPStan/Larastan auf einem
  dokumentierten, zunächst konservativen Analyselevel.
- npm: ESLint mit Vue-Regeln und Prettier; die Konfiguration prüft neue und
  geänderte Dateien zuverlässig und hält bestehende Legacy-Bestände separat.
- Repository-eigene Prüfskripte: PHPDoc/JSDoc- und weiche
  Komplexitätsprüfung mit klarer Ausgabe und Baseline-Dateien. Dadurch bleibt
  die Pflicht für neue Deklarationen unabhängig von optionalen IDE-Plugins.
- `./planer dev`: getrennte Formatierungsbefehle, reine Checks, Analyse und
  ein schreibgeschützter kombinierter Check.
- GitHub Actions: Pull-Request-Workflow mit Dependency-Cache, isolierten
  Testdatenbanken und Produktionsbuild.
- Technisches Handbuch und beide AGENTS-Dateien beschreiben Workflow,
  Ausnahmen und Baseline-Pflege.
- Echte Sponsoring-Hinweise aus Quelltext, Vorlagen und versionierten
  Artefakten werden entfernt; externe Paket-Sponsoring-URLs in Lockdateien
  bleiben als maschinelle Lizenz-/Paketmetadaten erhalten.

## Sequenz

1. Audit, Baselines und Konfiguration dokumentieren.
2. Abhängigkeiten, Docker-Befehle, Qualitätsprüfungen und CI integrieren.
3. Stilistisch sichere Bereinigung, Sponsoring-Entfernung, Tests und
   Verifikation durchführen.

## Nicht-Ziele

- Keine fachfremde Architekturmodernisierung.
- Keine TypeScript-Einführung.
- Keine globale Testabdeckungsquote.
- Keine Änderungen an Entwicklungs- oder Produktionsdatenbanken durch die
  Qualitätsbefehle.
