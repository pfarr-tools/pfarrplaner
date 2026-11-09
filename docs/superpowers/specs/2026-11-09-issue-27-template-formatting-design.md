# Erweiterter Formatierungsstandard für Issue #27

## Zweck

Issue #27 soll neben PHP und JavaScript/Vue auch die bislang nicht automatisch
formatierbaren Stylesheets und Blade-Templates verbindlich abdecken. Ziel ist
ein reproduzierbarer, Docker-nativer Formatierungs- und Prüfpfad für alle
maintainbaren Quelltexte, ohne generierte Dateien oder Laufzeitdaten in den
Formatierungslauf einzubeziehen.

## Ziele

- Prettier formatiert CSS, SCSS, Sass, JSON und YAML zusätzlich zu JavaScript,
  Vue und Skripten.
- `blade-formatter` formatiert `src/resources/views/**/*.blade.php` mit
  projektspezifischer Konfiguration.
- Schreib- und Prüfmodus werden über `./planer dev format` bzw.
  `./planer dev format:check` und die bestehenden Quality-Skripte erreichbar.
- Der technische Qualitätsstandard und die Repository-Regeln nennen die
  Zuständigkeit jedes Formatierers und den verbindlichen Ablauf.
- Die bestehende Prüfung geänderter Dateien bleibt erhalten und wird um CSS,
  SCSS, Sass und Blade ergänzt.

## Nichtziele

- Keine manuelle Umformatierung von Markdown, YAML, XML oder generierten
  Artefakten.
- Keine fachliche Umstrukturierung von Templates oder Stylesheets über die
  von den Formatierern erzeugten Änderungen hinaus.
- Keine Formatierung von `public/build`, `vendor`, `node_modules`,
  Dokumentationsmedien oder Laufzeitdateien.

## Ansätze

1. **Prettier plus `blade-formatter` (gewählt):** Prettier übernimmt die
   Stylesheet-Dateien und die bereits abgedeckten Frontend-Dateien;
   `blade-formatter` übernimmt Blade und respektiert dessen Direktiven. Die
   Werkzeuge werden als npm-Entwicklungsabhängigkeiten gelockt und über die
   Docker-Entwicklungsbefehle ausgeführt.
2. **Nur Prettier:** Stylesheets könnten sofort ergänzt werden, Blade wäre
   wegen seiner PHP-/Direktivensyntax aber nicht zuverlässig abdeckbar. Das
   erfüllt den gewünschten Vollformatierer nicht.
3. **PHP-basierter Blade-Formatter:** Würde die Node-basierte Frontend-
   Qualitätskette um eine zweite Paketverwaltung erweitern und passt damit
   schlechter zum bestehenden Docker-/npm-Formatierungsablauf.

## Umsetzung

- `src/package.json` und `src/package-lock.json` erhalten die neue
  Entwicklungsabhängigkeit sowie Formatierungs- und Prüfskripte.
- Die Prettier-Dateiauswahl wird um die vorhandenen CSS-/SCSS-/Sass-Pfade
  erweitert; Blade bleibt von Prettier ausgeschlossen.
- `.bladeformatterrc.json` legt LF, vier Leerzeichen, 120 Zeichen Zeilenlänge,
  PHP 8.4 und die projektspezifischen Blade-Komponentenpräfixe fest.
- Ein geänderter-Dateien-Runner für Blade stellt sicher, dass
  `format:check` im normalen Quality-Lauf nur betroffene Templates prüft,
  während der manuelle Formatierungsbefehl alle Quellen bearbeiten kann.
- `docs/manual/technikhandbuch/codequalitaet-und-workflow.md`, die Root-
  `AGENTS.md`-Regeln und `src/AGENTS.md` beschreiben den Standard und die
  Ausschlüsse.
- Vor dem Abschluss werden die Formatter auf allen betroffenen Quellen,
  `format:check`, Analyse, Lint, JavaScript-Tests und Build ausgeführt; ein
  vollständiger PHPUnit-Lauf wird über den bestehenden Docker-Quality-Befehl
  berücksichtigt.

## Sicherheits- und Kompatibilitätsgrenzen

Die Formatierer dürfen keine `.env`-Dateien, Datenbanken, Backups,
Abhängigkeiten oder generierte Builds verändern. Vor dem Commit wird der Diff
auf unbeabsichtigte fachliche Template- oder CSS-Änderungen geprüft.
