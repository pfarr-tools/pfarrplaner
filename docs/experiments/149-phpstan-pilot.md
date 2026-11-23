# PHPStan-Level-10-Pilot zu Issue #149

Historischer Pilotstand vom 23. November 2026. **Der Pilot wurde als `0fa26155` committed; die anschließende Fortsetzung ist unten dokumentiert.**

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


## Fortsetzung nach Freigabe

Der Benutzer hat das Committen und die weitere Umsetzung freigegeben. Der
Pilotcommit ist `0fa26155`; es erfolgt kein Push oder Merge.

Das erste Folgepaket stellt das Level-5-Gate wieder her. Gemeinsame CRUD- und
API-Controller geben ihren Model-Typ an konkrete Unterklassen weiter; Beziehungen,
Rückgaben und Pivot-Daten sind genauer beschrieben. Der verschlüsselte Cast
verspricht kein Array mehr, wenn tatsächlich Klartext oder ein Legacy-Wert
zurückgegeben wird. Sein `mixed` ist eine reale Grenze, die auf höheren Levels
weiterhin fachlich eingegrenzt werden muss.

Zwei Verhaltensfehler wurden durch zuerst fehlschlagende Regressionstests
abgesichert: Kalenderlöschungen verwenden nun dieselbe Zusatzereignis-Schnittstelle
und Jahrestagsoption wie die Synchronisierung; die Kontaktspalte des
Predigtbegleitblatts gibt den Gemeindenamen einmal statt zusammen mit einer
zusätzlichen Leerzeile aus. Die Änderungen stellen erwartetes Verhalten wieder
her, ohne neue Benutzerabläufe oder Betreiber-Schnittstellen einzuführen.
DemoBuilder benötigt keine Anpassung, da Datenmodell und Anonymisierung unverändert
bleiben.

Die PHPDoc-Prüfung verlor Dokumentationsblöcke vor PHP-Attributen. Vier isolierte
Regressionstests prüfen verschachtelte Attributargumente, Klassen, Methoden und
die weiterhin erforderliche Dokumentation. Der Scanner überspringt jetzt komplette
Attributgruppen, ohne Dokumentation auf nachfolgende Deklarationen zu übertragen.

Baseline-Pflege: 227 obsolete PHPStan-Muster entfernt, bei sechs verbleibenden
Mustern die erwartete Anzahl um insgesamt 11 reduziert. Keine Muster hinzugefügt.
Die PHPDoc-Baseline wurde anhand identischer Deklarationszeilen verschoben;
68 Einträge erhielten neue Zeilennummern, 89 bereits dokumentierte oder entfernte
Deklarationen entfielen. Keine neue PHPDoc-Ausnahme wurde aufgenommen.

Ruling: Die bestehenden veröffentlichten Benutzer- und Administratorenhandbücher
bleiben unverändert, weil nur erwartetes Verhalten wiederhergestellt wird. Das
Technikhandbuch erklärt die generischen Controller, Beziehungstypen und die
Grenzen des verschlüsselten Casts. Diese Entscheidung würde eine zusätzliche
Handbuchänderung erfordern, falls später tatsächlich ein Benutzerablauf geändert
wird.

## Messung nach dem ersten Folgepaket

Alle Werte wurden nach demselben Verfahren wie im Pilot gemessen. Die
bestehende Baseline blieb aktiv; ihre verbliebenen 846 Muster unterdrücken
weiterhin Altlasten.

| Level | Vor dem Pilot | Nach dem Pilot | Nach dem Folgepaket |
|---|---:|---:|---:|
| 5 | 0 | 189 | 0 |
| 6 | 2.564 | 2.614 | 2.406 |
| 10 | 10.800 | 10.370 | 10.138 |

Auf Level 10 entfallen 84 der 10.138 Meldungen auf die Baseline-Pflege
(74 `ignore.unmatched`, 10 `ignore.count`). Die eigentlichen Code-Diagnosen
sinken damit von 10.679 vor dem Pilot auf 10.054 nach dem Folgepaket, also um
625 (rund 5,9 %). Gegenüber dem Pilotstand sind es 66 weniger. Die 2.406
Meldungen auf Level 6 verteilen sich auf 514 Dateien, die 10.054
Code-Diagnosen auf Level 10 auf 672 Dateien.

Das Level-5-Gate ist wieder grün. Die Prüfung umfasst 1.114 Dateien; die
verbleibenden Meldungen auf Level 6 und 10 sind erwartungsgemäß und noch nicht
abgearbeitet. Häufigste Identifikatoren auf Level 6 bleiben fehlende
Parameter-, Iterable-, Rückgabe-, Property- und Generics-Typen. Auf Level 10
dominieren `argument.type`, `offsetAccess.nonOffsetAccessible` und
`method.nonObject`; sie zeigen, wo präzisere gemeinsame Verträge noch fehlen.

## Nächste Pakete

1. Die 2.406 Level-6-Meldungen fachlich gruppieren und mit gemeinsam genutzten
   Typen beginnen; nach jedem Paket Level 5 und 6 frisch prüfen.
2. Level 10 erst danach erneut messen und in Pakete nach Domänen schneiden.
3. Weitere Verhaltenskorrekturen nur mit zuerst fehlschlagendem Regressionstest
   und anschließender Prüfung der Handbuchauswirkungen.
4. Standardlevel erst nach vollständiger Verifikation und grünem Level-5-Gate
   erhöhen.

Der Stand bleibt lokal auf `experiment/149-phpstan-pilot`. Es erfolgt kein Push
und kein Merge; Issue #149 bleibt offen.

## Zweites Folgepaket: Eloquent-Typverträge

Die gemeinsamen Modellverträge sind jetzt vollständig generisch: Beziehungen
nennen Zielmodell und Herkunftsmodell, Scopes tragen `Builder<static>`,
`HasFactory` nennt die konkrete Factory. Das `HasCityScopes`-Trait ist ebenfalls
typisiert und wirkt damit auf alle fünf verwendenden Modelle. Pivot-Daten sind
an den betroffenen Modellen als `@property-read` dokumentiert.

| Level | Nach dem ersten Folgepaket | Nach dem zweiten Folgepaket |
|---|---:|---:|
| 5 | 0 | 0 |
| 6 | 2.406 | **2.189** |

Die Level-6-Befunde sinken um 217 (rund 9 %). Davon entfallen 181 auf
`missingType.generics`, die jetzt vollständig aus `app/Models` verschwunden sind;
der Rest auf Ergebnis- und Parametertypen des gemeinsamen Traits. Weitere 174
veraltete Baseline-Muster sind durch PHPStan selbst entfernt worden, nachdem die
präziseren Verträge die alten Muster unbrauchbar gemacht haben. Die PHPDoc-Baseline
wurde an 141 verschobene Deklarationszeilen angepasst; 36 veraltete Einträge
entfielen, keine neuen Ausnahmen kamen hinzu.

Durch die präziseren Verträge wurden echte Fehler sichtbar und behoben:
`Carbon::addYear(1)` beziehungsweise `addDay(1)` sind jetzt `addYears(1)` und
`addDays(1)`; `getSubscriptionType()` dokumentiert den tatsächlichen
Rückgabetyp; die Ankündigungsverwaltung setzt die verknüpfte Veranstaltung über
`setRelation()` statt über eine schreibende Eigenschaft und übergibt an
`groupBy()` keinen wirkungslosen zweiten Closure mehr.

### Verifikation

- PHPStan Level 5: 0 Fehler; Level 6: 2.189 erwartete Befunde, keine
  Baseline- oder sonstige technische Fehler.
- `./planer dev quality`: bestanden, einschließlich vollständiger PHP- und
  JavaScript-Testsuite sowie Format-, PHPDoc- und JSDoc-Prüfung.
- `git diff --check`: bestanden.

### Verbleibender Weg bis Level 6

| Kategorie | Befunde |
|---|---:|
| `missingType.parameter` | 631 |
| `missingType.iterableValue` | 565 |
| `missingType.return` | 532 |
| `missingType.property` | 326 |
| `missingType.generics` | 92 |
| `argument.templateType` | 43 |

Level 6 ist erreicht, sobald diese 2.189 Befunde fachlich aufgelöst sind. Die
Arbeit liegt überwiegend in einzelnen Deklarationen und ist daher deutlich
aufwändiger als die beiden gemeinsamen Typpakete. Die Schätzung von 40–80
weiteren aktiven Agentenstunden bleibt bestehen; sie schließt den Abbau der
alten Level-5-Baseline nicht ein.

## Drittes Folgepaket: Collection- und Template-Typen

Alle verbleibenden `missingType.generics`- und `argument.templateType`-Befunde
sind abgearbeitet. Factory-Klassen nennen ihr Modell, `HasCityScopes`,
`TracksDeletedByTrait` und die HomeScreen-Abfragen tragen konkrete Typen,
Collection-Parameter und -Rückgaben sind in Diensten, Berichten und
Liturgieblättern präzisiert. Bei dynamischen Werten aus Requests, Cache und DOM
wurden örtliche Typangaben beziehungsweise explizite
String-Konvertierungen ergänzt; das Laufzeitverhalten bleibt unverändert.

| Level | Nach dem zweiten Folgepaket | Nach dem dritten Folgepaket |
|---|---:|---:|
| 5 | 0 | 0 |
| 6 | 2.189 | **2.051** |

`missingType.generics` und `argument.templateType` stehen jetzt beide auf 0.
Zwölf obsolete PHPStan-Muster und 43 veraltete PHPDoc-Baseline-Einträge wurden
entfernt, 92 Einträge an verschobene Zeilen angepasst; keine neuen Ausnahmen.

### Verifikation

- PHPStan Level 5: 0 Fehler; Level 6: 2.051 erwartete Befunde.
- `./planer dev quality`: vollständig grün (1.201 PHP-Tests, 528 JavaScript-Tests,
  PHPStan, Format-, PHPDoc- und JSDoc-Prüfung).
- `git diff --check`: bestanden.

### Verbleibender Weg bis Level 6

| Kategorie | Befunde |
|---|---:|
| `missingType.parameter` | 631 |
| `missingType.iterableValue` | 563 |
| `missingType.return` | 531 |
| `missingType.property` | 326 |

Damit sind nur noch die vier einfachen Fehlertypen übrig. Sie verteilen sich auf
viele einzelne Deklarationen und lassen sich nicht weiter durch gemeinsame
Verträge abkürzen. Für jedes Paket gilt weiterhin: zuerst fokussierter Test,
dann Typangabe, danach Level 5 und 6 frisch prüfen.
