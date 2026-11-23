# PHPStan-Level-10-Pilot zu Issue #149

Stand: 23. November 2026. **Pilot beendet, weitere Umsetzung pausiert. Experimentell, nicht mergefähig.**

- Issue: https://github.com/pfarr-tools/pfarrplaner/issues/149
- Branch: `experiment/149-phpstan-pilot`
- Ausgangscommit: `24df89fc152ea1b099a23d7dc10f2f04b3e7526f`
- Die Änderungen liegen uncommittet im Arbeitsverzeichnis. Kein Push, kein Merge und keine Schließung des Issues.
- Zusätzliche lokale Sicherung: `/home/christoph/.local/share/pfarrplaner/issue-149-pilot/` enthält Patch, diesen Bericht und vollständige Messausgaben. Der Patch ist nur zur Wiederherstellung eines sauberen Checkouts des Ausgangscommits gedacht; nicht auf die bereits geänderten Dateien anwenden.

## Umfang und Grenzen

Fünf PHP-Dateien wurden experimentell typisiert:

- `src/app/Models/AbstractModel.php`: Konfigurationseigenschaften, Routenstrukturen und Standardattribute.
- `src/app/Http/Controllers/AbstractCRUDController.php`: Model-Klassenname, Collections, Action- und Editor-Verträge.
- `src/app/Models/Service.php`: 21 Eloquent-Beziehungen und vier Teilnehmer-Accessors.
- `src/app/Models/Liturgy/Block.php`: Beziehung `items()`.
- `src/app/Models/Sermon.php`: Beziehung `services()`.

Es wurden ausschließlich PHPDoc-Typen sowie dafür benötigte Imports verändert. Die ausführbaren Klassentokens stimmen nach Entfernung von Kommentaren und Leerraum mit dem Ausgangscommit überein. Es wurden keine Datenfelder, Migrationen oder Laufzeitabläufe verändert. DemoBuilder-Anpassungen sind deshalb nicht erforderlich.

`src/phpstan.neon` und `src/phpstan-baseline.neon` sind unverändert. Level 5 bleibt der Standard. Es wurden keine neuen Ignore-Regeln oder Baseline-Einträge angelegt. Die Baseline enthält weiterhin 1.527 unterdrückte Vorkommen.

## Messung

Die Zählung erfolgt mit derselben bestehenden Baseline. „Code“ bezeichnet gemeldete Diagnosen ohne `ignore.*`; es ist keine Zahl unabhängiger Programmfehler.

| Level | Vorher gesamt | Vorher Code | Vorher Baseline-Pflege | Nachher gesamt | Nachher Code | Nachher Baseline-Pflege |
|---|---:|---:|---:|---:|---:|---:|
| 5 | 0 | 0 | 0 | 189 | 27 | 162 |
| 6 | 2.564 | 2.564 | 0 | 2.614 | 2.452 | 162 |
| 10 | 10.800 | 10.679 | 121 | 10.370 | 10.120 | 250 |

Damit sinkt die Zahl der Code-Diagnosen auf Level 10 netto um **559 (rund 5,2 %)**. Auf Level 6 sind es **112 weniger**. Gleichzeitig werden bisher unterdrückte Meldungen obsolet oder ändern ihren Wortlaut; die Gesamtzahl allein bildet den Fortschritt deshalb schlecht ab. Eine nicht mehr passende Baseline-Regel beweist nicht automatisch, dass das ursprüngliche Problem vollständig behoben wurde.

Die Zwischenmessungen `shared*` und `pass1*` sind keine belastbaren isolierten A/B-Vergleiche einzelner Dateien, weil Analyse und weitere Bearbeitung zeitlich überlappten. Maßgeblich sind der reproduzierte Ausgangsstand und die nach Abschluss der Bearbeitung erzeugten `final5`, `final6` und `final10`.

## Was der Pilot zeigt

Gemeinsame Typen haben Wirkung über die bearbeiteten Dateien hinaus. Die Verbesserung ist aber keine rein mechanische Dokumentationsaufgabe:

1. **Experimentelle Verträge müssen noch angepasst werden.** `Collection<int, AbstractModel>` im Basiscontroller passt wegen der Invarianz von Collection-Typen nicht ohne Weiteres zu spezialisierten Unterklassen. Der allgemein annotierte Editor-Parameter lässt in Unterklassen bisher implizite konkrete Model-Typen fehlen. Diese vom Experiment ausgelösten Analyseprobleme dürfen nicht als Laufzeitfehler ausgegeben werden.
2. **Beziehungen müssen entlang ihrer Aufrufketten typisiert werden.** Nach `Service::liturgyBlocks()` wurde etwa die bisher untypisierte Beziehung `Block::items()` sichtbar; deren Typisierung beseitigt wiederum Folgefehler.
3. **Bestehende PHPDoc-Angaben widersprechen teilweise der Implementierung.** Beispiele sind `Service::ministries()` (Collection statt Array), `Service::getPreachersAttribute()` und `Sermon::getList()`.
4. **Mögliche fachliche Fehler benötigen eigene Prüfung.** Die Analyse meldet unter anderem `getPreparationEvent()` auf verschiedenen Kasualtypen, zusätzliche Argumente für `User::fullName()`, `City::$official_title` und Array-/String-Konflikte bei `NameService::fromName()`. Erst Definitionen, Accessors, Laufzeitverträge und passende Tests entscheiden, ob die Annotation oder der Aufrufer korrigiert werden muss.
5. **Pivot-Daten benötigen genaue Typen.** Ein pauschales `$pivot`-Versprechen für jedes User-Objekt wäre falsch; die Information gehört zum jeweiligen Beziehungsergebnis.

## Verifikation

- Vor PHPUnit wurde die gebootete Anwendung geprüft: `APP_ENV=testing`, `database.default=sqlite`, Datenbank `:memory:`, kein Konfigurationscache.
- Fokussierte Featuretests: **32 bestanden, 1 übersprungen, 247 Assertions**. Der übersprungene Team-Indextest verweist auf die bestehende Verwaltung im City-Editor.
- Pint-Prüfung der fünf geänderten PHP-Dateien: bestanden.
- Vergleich der ausführbaren Klassentokens mit dem Ausgangsstand: alle fünf unverändert.
- `git diff --check`: bestanden.
- PHPStan Level 5, 6 und 10: vollständig ausgeführt, jeweils erwarteter Fehlerstatus; Zahlen siehe Tabelle. Insbesondere ist das aktuelle Level-5-Gate **nicht grün**.
- Keine vollständige Testsuite, Browserprüfung oder Gesamt-Quality-Prüfung durchgeführt. Der Pilot ist kein Releasekandidat.

Der normale Formatter konnte zunächst als Containerbenutzer `www-data` nicht in die Hostdateien schreiben. Derselbe Composer-Formatter wurde anschließend im Docker-Container mit der numerischen Host-UID/GID erfolgreich ausgeführt. Dateiberechtigungen wurden nicht aufgeweicht.

Da keine Benutzerabläufe, Betreiber-Schnittstellen oder produktiven Entwicklungsregeln geändert werden, bleiben die drei veröffentlichten Handbücher unverändert. Dieser Bericht dokumentiert den experimentellen Entwicklungsstand.

## Einschätzung des Restaufwands

Die frühere Schätzung von 25–60 aktiven Agentenstunden ist weiterhin nicht als Zusage belastbar. Der Pilot belegt sowohl Folgewirkungen guter Typen als auch notwendige Arbeit an Vererbung, Datenverträgen und Aufrufern. Als vorsichtiges Planungsbudget für eine mergefähige Umstellung ohne neue Baseline-Unterdrückungen sind **40–80 weitere aktive Agentenstunden** sinnvoll; das ist eine fachliche Schätzung, keine aus gemessener Bearbeitungsgeschwindigkeit berechnete Hochrechnung.

Die Arbeit wurde unterbrochen; die verstrichene Kalenderzeit ist kein Aufwandmaß. Die fünf Dateien bilden zudem keine repräsentative Zufallsstichprobe: Sie wurden wegen ihrer gemeinsamen Nutzung ausgewählt. Eine lineare Hochrechnung aus dem Rückgang um 5,2 % wäre irreführend. Der vollständige Abbau der alten Level-5-Baseline ist weiterhin nicht enthalten.

## Wiederaufnahme

1. `git status --short` und `git branch --show-current` prüfen. Erwartet werden dieser Branch, fünf geänderte PHP-Dateien und dieser Bericht. Unabhängige neue Änderungen erhalten.
2. Diesen Bericht und die aktuellen Kommentare in Issue #149 lesen. Den vorhandenen Arbeitsstand verwenden; den Sicherungspatch nicht erneut anwenden.
3. Zuerst die 27 verbleibenden Code-Diagnosen auf Level 5 bearbeiten: Controller-Generics und konkrete Editor-Typen, widersprüchliche PHPDoc-Rückgaben, Beziehungsketten und Pivot-Typen. Fachliche Aufruferprobleme jeweils separat mit fokussierten Tests klären.
4. Erst danach nachweislich obsolete Baseline-Einträge entfernen beziehungsweise verringerte Zähler korrigieren. Keine neuen Verstöße unterdrücken. Das Level-5-Gate muss vor einer Integration wieder grün sein.
5. Level 6 und 10 neu messen; verbleibende Arbeit in fachlich zusammenhängende Pakete teilen. Standardlevel erst nach entsprechender Verifikation erhöhen.
6. Relevante Tests ausschließlich über `./planer dev test` und nach erneuter Prüfung der tatsächlich wirksamen Testdatenbank ausführen. Vor Integration Diff, Dokumentation und Qualitätsgates prüfen.

### Messbefehle

```bash
./planer dev composer analyse -- --level=5 --error-format=json --no-progress
./planer dev composer analyse -- --level=6 --error-format=json --no-progress
./planer dev composer analyse -- --level=10 --error-format=json --no-progress
./planer dev composer format:check -- app/Http/Controllers/AbstractCRUDController.php app/Models/AbstractModel.php app/Models/Liturgy/Block.php app/Models/Sermon.php app/Models/Service.php
./planer dev test --filter='(ServiceFeatureTest|TagFeatureTest|TeamFeatureTest|LocationFeatureTest|ParishFeatureTest|BaptismFeatureTest|WeddingFeatureTest|FuneralFeatureTest)' --no-browser --no-js --without-tty
```

Die Analyseausgaben enthalten vollständige Dateipfade, Zeilen, Meldungen und Identifikatoren. Sie liegen in der lokalen Sicherung; die Datei `metrics.json` enthält die zusammengefassten Zahlen für die Wiederaufnahme.
