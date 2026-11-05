[//]: # (TOC: 0. Codequalität und Entwicklungsworkflow)

# Codequalität und Entwicklungsworkflow

Issue #27 führt verbindliche, Docker-native Prüfungen für neuen und geänderten
Code ein. Die Qualitätsbefehle verwenden dieselbe PHP-, Node- und
Abhängigkeitsumgebung wie die Entwicklung; lokale Installationen sind nicht
erforderlich.

## Befehle

Im Repository-Stamm stehen folgende Befehle zur Verfügung:

- `./planer dev format` formatiert PHP sowie JavaScript/Vue automatisch.
- `./planer dev format:check` prüft Formatierung ohne Dateien zu ändern.
- `./planer dev analyse` führt PHPStan/Larastan auf Level 5 aus.
- `./planer dev lint` führt ESLint mit Vue-Regeln aus.
- `./planer dev docs:check` prüft PHPDoc für den Bestand und JSDoc für
  geänderte JavaScript-/Vue-Dateien.
- `./planer dev complexity:check` gibt nicht blockierende Lesbarkeitshinweise.
- `./planer dev quality` führt alle Prüfungen, isolierte PHPUnit-/Vitest-Tests
  und den Vite-Produktionsbuild schreibgeschützt aus.

## Dokumentationspflicht

Neue oder geänderte PHP-Klassen, Interfaces, Traits, Enums, Funktionen und
Methoden benötigen aussagekräftige PHPDoc-Blöcke. Bei JavaScript und Vue gilt
das für Funktionen, Methoden, Composables, Event-Handler und vergleichbare
Deklarationen. Der Block beschreibt den Zweck und bei Bedarf Parameter,
Rückgabe, Nebenwirkungen und Ausnahmen; native Typen allein ersetzen keine
Beschreibung.

## Baselines und Ausnahmen

Bestehende PHPDoc- und PHPStan-Verstöße sind in `src/quality/` nachvollziehbar
baselined. Die Baseline ist eine abzubauende technische Altlast, keine
Erlaubnis für neue Verstöße. Qualitätsprüfungen auf Pull Requests prüfen die
geänderten Dateien zusätzlich ohne Baseline-Ausnahme. Fremd- und generierter
Code bleibt über die Konfiguration ausgeschlossen.

Weiche Richtwerte für die Review sind 50 Zeilen je Funktion, 400 Zeilen je
Klasse oder Vue-Komponente, höchstens fünf Parameter, drei
Verschachtelungsebenen und zyklomatische Komplexität 10. Eine längere, gut
verständliche Funktion muss nicht künstlich zerlegt werden.

## CI und Datenbanksicherheit

Der Pull-Request-Workflow `.github/workflows/quality.yml` startet die Prüfung
in Docker. PHPUnit verwendet die vom `./planer dev test` gesetzte SQLite-
In-Memory-Datenbank; weder Entwicklungs- noch Produktionsdatenbanken werden
für Qualitätsprüfungen verwendet. Der kombinierte Befehl verändert keine
Dateien. Formatierung ist bewusst ein separater, manueller Schritt.

## Empfohlener Ablauf

1. Verwandten Code und die geltenden AGENTS-Regeln lesen.
2. Änderung mit Test und PHPDoc/JSDoc umsetzen.
3. `./planer dev format` gezielt ausführen und den Diff prüfen.
4. `./planer dev quality` ausführen.
5. Baselines nur für bereits vorhandene, dokumentierte Verstöße aktualisieren
   und die offene Altlast im Issue nachvollziehbar halten.
