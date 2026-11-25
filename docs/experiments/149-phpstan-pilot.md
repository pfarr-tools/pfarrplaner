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

## Level-6-Gate und Baseline-Ratchet

Der Prüfmaßstab wird auf Level 6 angehoben. Die beim Umstieg verbleibenden 2.051
Befunde wurden in einer vollständig neu erzeugten Baseline erfasst: 2.934
Meldungen in 2.710 Mustern. Die vorherige Baseline ging dabei nicht verloren,
sondern wurde mit den neuen Mustern zusammengeführt.

Der Stand wird in `main` übernommen; der Experimentbranch bleibt erhalten und
dient als Arbeitsbranch für die weitere Reduktion. Bei Bedarf wird `main` in
diesen Branch gemergt, ein fachliches Paket bearbeitet und anschließend wieder
nach `main` zurückgeführt. Die Baseline schützt dabei vor Rückschritten, weil
sowohl neue als auch weggefallene Vorkommen eines Musters als Fehler gemeldet
werden. Neue Unterdrückungen sind unzulässig.

Issue #149 bleibt offen, bis das ursprüngliche Ziel Level 10 erreicht ist. Das
Technikhandbuch beschreibt den Ratchet-Modus und die Messung ohne Baseline.

## Erste Reduktionspakete nach dem Gate

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 2.934 | 2.858 |
| Baseline-Muster | 2.710 | 2.665 |

`OccurenceResource` enthielt eine falsche `@var`-Angabe für `$this`; dadurch
wurden weitergeleitete Modelleigenschaften als unbekannt gemeldet. Die Angabe
wurde durch `@property-read`-Deklarationen ersetzt, außerdem sind die drei
Farb-Helfer typisiert.

`RowBasedSeatFinder` und die beiden Basis-Klassen sind vollständig typisiert.
Dabei wurden echte Fehler sichtbar: die Sortiereigenschaft hieß `prioriy`
statt `priority`, `str_pad()` erhielt einen Integer statt eines Strings, und
Sitzplatzzahlen wurden als String zugewiesen. Zwei wirkungslose Zuweisungen an
eine undefinierte Eigenschaft und an eine schreibgeschützte Relation wurden
entfernt.

45 obsolete PHPStan-Muster und 8 veraltete PHPDoc-Baseline-Einträge wurden
entfernt, 9 Einträge an verschobene Zeilen angepasst. Keine neuen Ausnahmen.
`./planer dev quality` ist vollständig grün (1.201 PHP-Tests, 528
JavaScript-Tests, PHPStan, Formatter, PHPDoc/JSDoc).

## Zweites Reduktionspaket: ABCMusic

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 2.858 | 2.811 |
| Baseline-Muster | 2.665 | 2.621 |

Alle 49 Befunde in `ABCMusic` sind behoben. Neben Typangaben für Verse,
Notenlinien und Bildpfade wurden echte Fehler korrigiert: `makeRefrain()` war
eine Instanzmethode, wurde aber statisch aufgerufen; beim Aufruf in
`imagesMerged()` fehlte ein Argument und die Farbe wurde mit dem Tag
verkettet; ein Index verwies auf `$verseNumber` statt `$verseNumbers`.
`getVerses()` enthält keine wirkungslose `is_array()`-Prüfung mehr, und
`getVerse()` gibt bei fehlendem Treffer ausdrücklich `null` zurück.

44 obsolete PHPStan-Muster und 10 veraltete PHPDoc-Baseline-Einträge wurden
entfernt, einer verschoben. `./planer dev quality` bleibt vollständig grün.

## Drittes Reduktionspaket: NameService

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 2.811 | 2.774 |
| Baseline-Muster | 2.621 | 2.584 |

Alle 37 Befunde in `NameService` sind behoben. Die Ergebnisse der
Namensidentifikation, zerlegte Namensteile, Kandidaten und bewertete
Kandidaten sind als lokale PHPStan-Typaliase beschrieben und in den
Methodendokumentationen verwendet. Die Namensfelder sind `string`-Eigenschaften.

37 obsolete PHPStan-Muster und 17 veraltete PHPDoc-Baseline-Einträge wurden
entfernt, 10 verschoben; keine neuen Ausnahmen. `./planer dev quality` bleibt
vollständig grün.

## Viertes und fünftes Reduktionspaket: AbstractLiturgySheet und AbsenceController

| Messwert | Vor dem Gate | Nach AbsenceController |
|---|---:|---:|
| Rohbefunde ohne Baseline | 2.934 | 2.591 |
| Baseline-Muster | 2.710 | 2.419 |

Die gemeinsame Liturgieblatt-Basis ist vollständig typisiert; ihre präziseren
Verträge lösten 92 baselined Muster in abgeleiteten Blättern auf. Der
`AbsenceController` und die beteiligten Modelle und Traits sind ebenfalls
typisiert. Dabei wurden echte Fehler korrigiert: nicht mehr gültige
Carbon-Einzelmodifikatoren, die Zuweisung an eine Relationseigenschaft statt
`setRelation()` sowie eine falsch dokumentierte Anforderungsklasse.

In Summe wurden 165 obsolete PHPStan-Muster und 22 veraltete PHPDoc-Einträge
entfernt, 25 Einträge verschoben. Keine neuen Ausnahmen; `./planer dev quality`
bleibt vollständig grün.

## Sechstes Reduktionspaket: SongPPTLiturgySheet

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 2.591 | 2.564 |
| Baseline-Muster | 2.419 | 2.395 |

Alle 31 Befunde in `SongPPTLiturgySheet` sind behoben. Neben Typangaben für
Farbsätze, Bildlisten und Hilfsdokumente wurden echte Fehler korrigiert:
ein falsch qualifizierter `FreetextItemHelper`, eine Operator-Rangfolge bei
`??`, nicht mehr gültige Carbon-Einzelmodifikatoren, der Zugriff auf
`$service->start` statt `$service->date` und mehrere Integer-Konvertierungen
für die Presentation-Bibliothek.

24 obsolete PHPStan-Muster wurden entfernt; keine neuen Ausnahmen.
`./planer dev quality` bleibt vollständig grün.

## Siebtes Reduktionspaket: Service

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 2.564 | 2.524 |
| Baseline-Muster | 2.395 | 2.356 |

Alle 53 Befunde im `Service`-Modell sind behoben. Modelleigenschaften,
Gültigkeitsbereiche, Teilnehmer-Schnittstellen und Hilfsmethoden sind
typisiert. Dabei wurden echte Fehler korrigiert: ein fehlender Import von
`MinistryService`, eine falsch typisierte `wherePivotIn()`-Option,
`setCategoryId()` mit Integer, eine nicht mehr gültige
Carbon-Einzelmodifikation und eine fehlende Rückgabe in
`syncParticipantsByCategory()`. Ein doppelter Eintrag in `$appends` entfällt.

40 obsolete PHPStan-Muster wurden entfernt; keine neuen Ausnahmen.
`./planer dev quality` bleibt vollständig grün.

## Achtes Reduktionspaket: Attachment-Traits

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 2.524 | 2.473 |
| Baseline-Muster | 2.356 | 2.305 |

Die Attachment-Traits sind typisiert; `attachImage()` gibt den
Zurückfall explizit zurück. Die shared Verträge lösen 51 baselined Muster in
den verwendenden Controllern auf. Keine neuen Ausnahmen;
`./planer dev quality` bleibt vollständig grün.

## Neuntes Reduktionspaket: AbstractModule

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 2.473 | 2.411 |
| Baseline-Muster | 2.305 | 2.243 |

Eigenschaften und Konfigurationsmethoden des gemeinsamen UI-Moduls sind
typisiert; die Verträge lösen 62 baselined Muster in den abgeleiteten Modulen
auf. Keine neuen Ausnahmen; `./planer dev quality` bleibt vollständig grün.

### Stand nach diesen Paketen

Seit dem Level-6-Gate sind die Rohbefunde von 2.934 auf 2.411 und die
Baseline-Muster von 2.710 auf 2.243 gesunken. Die verbleibenden Befunde
liegen weiterhin über viele einzelne Deklarationen verteilt; die Reduktion
wird in weiteren fachlichen Paketen fortgesetzt.

## Zehntes Reduktionspaket: User

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 2.411 | 2.361 |
| Baseline-Muster | 2.243 | 2.195 |

Alle 50 Befunde im `User`-Modell sind behoben. Neben Typangaben für
Einstellungen, Berechtigungen, Abwesenheiten und Teamzuordnungen wurden echte
Fehler korrigiert: `getSetting()` übergab einen nicht existierenden
Unserialize-Parameter, `createIfNotExists()` dokumentierte eine falsche
Rückgabe, und zwei `pluck()`-Aufrufe luden Modelle statt direkt Werte ab.
`./planer dev quality` bleibt vollständig grün.

## Elftes Reduktionspaket: Announcements

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 2.361 | 2.318 |
| Baseline-Muster | 2.195 | 2.152 |

Alle 45 Befunde im `Announcements`-Modell sind behoben. Neben Typangaben für
Absätze, Rückrufe und Trenner wurden echte Fehler korrigiert: ein nicht
definierter `$item` im Schlusslied, eine ungültige Carbon-Modifikation, eine
immer falsche `is_object()`-Prüfung und ein Aufruf einer nicht existierenden
`renderOfferingsArray()`-Methode.

43 obsolete PHPStan-Muster und 28 veraltete PHPDoc-Baseline-Einträge entfernt,
8 verschoben; keine neuen Ausnahmen. `./planer dev quality` bleibt grün.

## Zwölftes Reduktionspaket: Absence

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 2.318 | 2.295 |
| Baseline-Muster | 2.152 | 2.130 |

Alle 27 Befunde im `Absence`-Modell sind behoben. Neben Typangaben für
Zeiträume, Vertretungen und Kalenderzuordnungen wurden drei ungültige
Carbon-Einzelmodifikatoren korrigiert. Die korrigierten Rückgabetypen lösen
zusätzlich zwei `foreach`-Fehler in der Kalendersynchronisierung auf.

22 obsolete PHPStan-Muster und 12 veraltete PHPDoc-Baseline-Einträge entfernt,
3 verschoben; keine neuen Ausnahmen. `./planer dev quality` bleibt grün.

## Dreizehntes Reduktionspaket: DefaultWordDocument

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 2.295 | 2.252 |
| Baseline-Muster | 2.130 | 2.087 |

Alle 31 Befunde im `DefaultWordDocument` sind behoben. Konfiguration,
Schriftdokumente und Render-Methoden sind typisiert. Dabei wurden echte Fehler
korrigiert: eine nicht existierende `NO_INDENT`-Konstante, eine immer falsche
`is_array()`-Prüfung, ein `void`-Rückgabewert in `renderNormalText()` und eine
fehlende Rückgabe in `getParagraphStyle()`.

43 obsolete PHPStan-Muster und 8 veraltete PHPDoc-Baseline-Einträge entfernt,
5 verschoben; keine neuen Ausnahmen. `./planer dev quality` bleibt grün.

Die Reduktionspakete ab diesem Punkt liegen ausschließlich auf dem
Experimentbranch; `main` bleibt auf dem Level-6-Gate-Commit `2f4ccf6c`.

## Vierzehntes Reduktionspaket: SermonHandoutLiturgySheet

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 2.252 | 2.223 |
| Baseline-Muster | 2.087 | 2.067 |

Alle 29 Befunde im Predigtbegleitblatt sind behoben. Render-Methoden und
Podcast-Helfer sind typisiert, PhpWord-Geometriewerte werden ganzzahlig
übergeben. `addColumnBreak()` existiert in PhpWord nicht und wurde durch
`addPageBreak()` ersetzt; eine redundante `null`-Prüfung nach `isset()` entfällt.

20 obsolete PHPStan-Muster entfernt; keine neuen Ausnahmen.
`./planer dev quality` bleibt grün.

## Fünfzehntes Reduktionspaket: BulletinReport

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 2.223 | 2.195 |
| Baseline-Muster | 2.067 | 2.055 |

Alle 28 Befunde im Gemeindebriefbericht sind behoben. Formate, Dienstliste
und Geometrieparameter sind typisiert. Dabei wurden echte Fehler korrigiert:
zeichenfolgenbasierte Zentimeterangaben, ein immer wahrer Indexvergleich,
nicht definierte `$service`-Variablen am Ende der Render-Methoden und
mehrere Gleitkommawerte an ganzzahlige PhpWord-Parameter.

12 obsolete PHPStan-Muster entfernt; keine neuen Ausnahmen.
`./planer dev quality` bleibt grün.

## Sechzehntes Reduktionspaket: PublicController

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 2.195 | 2.167 |
| Baseline-Muster | 2.055 | 2.028 |

Alle 28 Befunde im öffentlichen Controller sind behoben. Argumente und
Rückgaben sind typisiert. Zwei echte Fehler wurden korrigiert: vertauschte
Argumente bei `is_a()` in `nextStream()` und eine wirkungslose
`is_numeric()`-Prüfung auf einem bereits als `User` typisierten Parameter.

27 obsolete PHPStan-Muster entfernt; keine neuen Ausnahmen.
`./planer dev quality` bleibt grün.

## Siebzehntes Reduktionspaket: UserController

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 2.167 | 2.149 |
| Baseline-Muster | 2.028 | 2.015 |

Alle 23 Befunde im `UserController` sind behoben. Formularparameter,
Weiterleitungen und Hilfsmethoden sind typisiert; die wirkungslose
Null-Prüfung in `doJoin()` entfällt, und die dynamischen Eigenschaften
`duplicates` und `fullNameText` sind am `User`-Modell dokumentiert.

13 obsolete PHPStan-Muster entfernt; keine neuen Ausnahmen.
`./planer dev quality` bleibt grün.

## Achtzehntes Reduktionspaket: HomeScreen-Basisklassen

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 2.149 | 2.015 |
| Baseline-Muster | 2.015 | 1.881 |

`AbstractHomeScreenTab` und `HomeScreenTabFactory` sind vollständig typisiert;
die gemeinsamen Verträge lösen 134 baselined Muster in allen Tabs auf. Dabei
wurden echte Fehler korrigiert: falsch qualifizierte View-Typen, ein
ungültiger `@var`-Block, zu enge Tab-Index-Typen und eine `is_a()`-Prüfung
auf einem Array im Tab-API-Controller.

82 obsolete PHPStan-Muster und 24 veraltete PHPDoc-Baseline-Einträge entfernt,
5 verschoben; keine neuen Ausnahmen. `./planer dev quality` bleibt grün.

## Neunzehntes Reduktionspaket: Funeral

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 2.015 | 1.954 |
| Baseline-Muster | 1.881 | 1.822 |

Alle 23 Befunde im `Funeral`-Modell sind behoben. Setter, Altersberechnung
und Zusatztermine sind typisiert; zwei ungültige Carbon-Modifikatoren und
nicht erreichbare `break`-Anweisungen entfallen. Die gemeinsame
Validierungsregel-Deklaration in `AbstractModel` wurde präzisiert.

59 obsolete PHPStan-Muster und 6 veraltete PHPDoc-Baseline-Einträge entfernt,
2 verschoben; keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und die
vollständige PHP-Testsuite sind grün.

Hinweis: Im Arbeitsverzeichnis liegen unversionierte Dateien aus anderer
Arbeit (Passkey-Registrierung); sie wurden nicht verändert oder committet.

## Mergebasis und zwanzigstes Reduktionspaket: Kalendersynchronisierung

`origin/main` wurde nach dem Laststand eingeholt und in den Experimentbranch
gemergt (Merge-Commit `4563262d`). Die Passkey-Änderungen aus `main` sind
damit enthalten; `./planer dev quality` blieb danach grün (1.205 PHP-Tests).

Alle 19 Befunde in `AbstractSyncEngine` und den zugehörigen Kalenderbasen
sind behoben. Nicht statische Nutzung von `$prefix`, fehlende Methodenverträge
auf den abstrakten Kalenderklassen und die nicht typisierte
`CalendarConnectionEntry`-Relation sind korrigiert. Die gemeinsamen Verträge
entfernen 17 veraltete Baseline-Muster.

Rohbefunde 1.954 → 1.934, Baseline-Muster 1.822 → 1.805. PHPStan, PHPDoc,
PHP-Tests und `git diff --check` sind grün.

## Einundzwanzigstes Reduktionspaket: AnnouncementsReport

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1.934 | 1.913 |
| Baseline-Muster | 1.805 | 1.784 |

Alle 21 Befunde im Ankündigungsbericht sind behoben. Konfiguration,
Rückrufe und Render-Methoden sind typisiert. Dabei wurden echte Fehler
korrigiert: ein Property-Zugriff statt `locationText()`, ein
Großbuchstaben-`DESC`-Argument und eine Operator-Rangfolge bei `??`.

21 obsolete PHPStan-Muster entfernt; keine neuen Ausnahmen. PHPStan,
PHPDoc-Prüfung und die vollständige PHP-Testsuite sind grün.

## Zweiundzwanzigstes Reduktionspaket: IDML

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1.913 | 1.893 |
| Baseline-Muster | 1.784 | 1.764 |

Alle 20 Befunde im IDML-Dateiformat sind behoben. Konstruktor, Pfade,
XML- und Render-Methoden sind typisiert. Echte Fehler: fehlende
Rückgabe in `tempdir()`, undefinierte `$file`-Variable in `removeFile()`,
möglicherweise undefiniertes `$dir_handle`, ungenutzter MIME-Typ-Parameter
und unerreichbarer Code nach einem `exit`.

20 obsolete PHPStan-Muster entfernt; keine neuen Ausnahmen. PHPStan,
PHPDoc-Prüfung und die vollständige PHP-Testsuite sind grün.

## Dreiundzwanzigstes Reduktionspaket: AbstractSeatFinder

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1.893 | 1.871 |
| Baseline-Muster | 1.764 | 1.742 |

Alle 18 Befunde in der Sitzplatzfinder-Basis sind behoben. Eigenschaften,
Abfragen und Listenformate sind typisiert; ungenutzter Konstruktorparameter
wird gespeichert, leere Basismethoden liefern sinnvolle Standardwerte.
Der zu enge Parameter `$data` wurde in der Basisklasse und beiden
Implementierungen vereinheitlicht.

22 obsolete PHPStan-Muster und 13 veraltete PHPDoc-Baseline-Einträge entfernt,
7 verschoben; keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und die
vollständige PHP-Testsuite sind grün.

## Vierundzwanzigstes Reduktionspaket: DAVCalendarItem

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1.871 | 1.863 |
| Baseline-Muster | 1.742 | 1.735 |

Alle 18 Befunde im DAV-Kalenderelement sind behoben. Konfiguration,
Kategorien, URIs und Kalenderausgabe sind typisiert; das dynamische
VObject-Element erhält eine lokale Typangabe, und der Rückgabetyp von
`fromUri()` entspricht jetzt der tatsächlichen Signatur.

7 obsolete PHPStan-Muster und 3 veraltete PHPDoc-Baseline-Einträge entfernt;
keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und die vollständige
PHP-Testsuite sind grün.

## Fünfundzwanzigstes Reduktionspaket: UserResource

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1.863 | 1.846 |
| Baseline-Muster | 1.735 | 1.719 |

Alle 17 Befunde in `UserResource` sind behoben. Die weitergeleiteten
Eigenschaften und Methoden des User-Modells sind als `@property`- und
`@method`-Verträge dokumentiert, die Ausgabe ist `array<string, mixed>`.

16 obsolete PHPStan-Muster entfernt; keine neuen Ausnahmen. PHPStan,
PHPDoc-Prüfung und die vollständige PHP-Testsuite sind grün.

## Sechsundzwanzigstes Reduktionspaket: ReadingAndAnnouncementsLiturgySheet

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1.846 | 1.824 |
| Baseline-Muster | 1.719 | 1.700 |

Alle 23 Befunde im Schriftlesungs-Blatt sind behoben. Render-Methoden,
Kategorien und Zeilentexte sind typisiert; PhpWord-Geometriewerte werden
ganzzahlig übergeben. Der Freitext-Helfer erhält eine lokale Typangabe,
weil `getText()` nur auf konkreten Helpern existiert.

19 obsolete PHPStan-Muster und 15 veraltete PHPDoc-Baseline-Einträge entfernt,
5 verschoben; keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und die
vollständige PHP-Testsuite sind grün.

## Siebenundzwanzigstes Reduktionspaket: PapierkorbController

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1.824 | 1.804 |
| Baseline-Muster | 1.700 | 1.687 |

Alle 21 Befunde im Papierkorb-Controller sind behoben. Die fünf möglichen
Modelltypen werden über `mixed` dokumentiert, Moduleinstellungen und
Rückgaben sind typisiert. Eine redundante letzte `match`-Bedingung und
ein Methodenaufruf auf `class-string|object` sind korrigiert.

13 obsolete PHPStan-Muster und 1 veralteter PHPDoc-Baseline-Eintrag entfernt,
8 verschoben; keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und die
vollständige PHP-Testsuite sind grün.

## Achtundzwanzigstes Reduktionspaket: BillBoardReport

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1.804 | 1.785 |
| Baseline-Muster | 1.687 | 1.670 |

Alle 19 Befunde im Schaukastenbericht sind behoben. Dokumenteigenschaft,
Render-Methoden und Namenslisten sind typisiert; PhpWord-Geometriewerte
werden ganzzahlig übergeben. 17 obsolete PHPStan-Muster entfernt; keine
neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und die vollständige PHP-Testsuite
sind grün.

## Neunundzwanzigstes Reduktionspaket: BulletinBLReport

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1.785 | 1.766 |
| Baseline-Muster | 1.670 | 1.653 |

Alle 20 Befunde im Balinger Gemeindebriefbericht sind behoben. Spalten-,
Tag- und Story-Daten sind typisiert. Echte Fehler: falsch deklarierte
Spalten-/Tag-Arrays, großgeschriebene Sortierrichtung, `get()->first()`
statt `first()`, eine leere `$specialServices`-Variable trotz Befüllung von
`$specialServices2` und ein `array_first()` auf einer Collection.

17 obsolete PHPStan-Muster entfernt; keine neuen Ausnahmen. PHPStan,
PHPDoc-Prüfung und die vollständige PHP-Testsuite sind grün.

## Dreißigstes Reduktionspaket: ServiceController

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1.766 | 1.754 |
| Baseline-Muster | 1.653 | 1.642 |

Alle 17 Befunde im Service-Controller sind behoben. Rückgaben,
Anfrageparameter und Editor-Helfer sind typisiert. Echte Fehler: die
Kommentar-Relation im `HasCommentsTrait` hatte keinen generischen Vertrag,
der iCal-View wurde ohne `render()` in `str_replace()` übergeben, und
Sortierrichtungen waren großgeschrieben.

11 obsolete PHPStan-Muster und 5 veraltete PHPDoc-Baseline-Einträge entfernt,
1 verschoben; keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und die
vollständige PHP-Testsuite sind grün.

## Einunddreißigstes Reduktionspaket: KonfiAppIntegration

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1.754 | 1.739 |
| Baseline-Muster | 1.642 | 1.627 |

Alle 17 Befunde in der KonfiApp-Integration sind behoben. Anfragen,
Antworten und QR-Code-Methoden sind typisiert. Echte Fehler: ein
Relationsobjekt wurde statt des API-Schlüssels übergeben, das Datum
verwendete eine ungültige Carbon-Einzelmodifikation, und mehrere
Rückgabetypen waren falsch dokumentiert.

15 obsolete PHPStan-Muster und 1 veralteter PHPDoc-Baseline-Eintrag entfernt;
keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und die vollständige
PHP-Testsuite sind grün.

## Zweiunddreißigstes Reduktionspaket: EncryptedAttribute

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1.739 | 1.722 |
| Baseline-Muster | 1.627 | 1.610 |

Alle 15 Befunde im verschlüsselten Cast sind behoben. Schlüssel,
Entschlüsselungshelfer und Verschlüsselung sind typisiert; ein ungenutzter
privater Entschlüsselungshelfer wurde entfernt. Der Cast-Vertrag bleibt
bewusst `mixed`, weil neben Klartext auch wiederhergestellte Legacy-Werte
auftreten können.

17 obsolete PHPStan-Muster entfernt; keine neuen Ausnahmen. PHPStan,
PHPDoc-Prüfung und die vollständige PHP-Testsuite sind grün.

## Dreiunddreißigstes Reduktionspaket: ImageHelper

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1.722 | 1.707 |
| Baseline-Muster | 1.610 | 1.597 |

Alle 15 Befunde im Bild-Helfer sind behoben. Dateipfade, Größen und
IPTC-Daten sind typisiert. `floor()`-Ergebnisse werden ganzzahlig
zurückgegeben, fehlende IPTC-Daten als leeres Array statt `false`.

13 obsolete PHPStan-Muster entfernt; keine neuen Ausnahmen. PHPStan,
PHPDoc-Prüfung und die vollständige PHP-Testsuite sind grün.

## Parallele Reduktionspakete per Subagenten

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1.707 | 1.629 |
| Baseline-Muster | 1.597 | 1.540 |

Sechs dateidisjunkte Pakete wurden von drei Subagenten in getrennten
Worktrees vorbereitet und nacheinander in den Experimentbranch
übernommen: `ObjectFile`, `PredicantsReport`, `DownloadController`,
`SongItemHelper`, `ServiceThemesReport` und die zentrale
Baselinebereinigung.

Neben Typangaben wurden echte Fehler behoben: `get()->first()` statt
`first()`, großgeschriebene Sortierrichtung, String-Zentimeterangaben,
tote `is_array()`-Zweige, ein wirkungsloses drittes Argument in zehn
Aufrufen und fehlende PHPDoc-Blöcke auf Gettern/Setzern.

57 obsolete PHPStan-Muster und 36 veraltete PHPDoc-Baseline-Einträge
entfernt; keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und die
vollständige PHP-Testsuite sind grün.

Hinweis: Die Worktree-Compose-Läufe hatten den gemeinsamen Redis-Container
ohne Passwort neu erstellt; er wurde mit der korrekten `.env` wiederhergestellt.

## Zweite Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1.629 | 1.568 |
| Baseline-Muster | 1.540 | 1.480 |

Drei weitere dateidisjunkte Pakete per Subagenten: `AbstractAttachment`,
`LiturgyEditorController`, `ImportableSchedule`. Echte Fehler: Null-Seitenname
an `Inertia::render()`, Property-Zugriff statt `locationText()`, tote
Quellenabfrage, `get()->pluck()` statt Query-`pluck()` und ein falsch
dokumentierter `toZip()`-Rückgabetyp.

60 obsolete PHPStan-Muster und 20 veraltete PHPDoc-Baseline-Einträge entfernt;
keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests
grün.

## Dritte Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1.568 | 1.521 |
| Baseline-Muster | 1.480 | 1.444 |

Drei weitere Pakete: `SimpleText`, `Liturgy/Item`, `EventListReport`.
Echte Fehler: falsch qualifizierte `Tab`-Werte, `subDay(1)`, undefinierte
`NO_INDENT`-Konstante, undefinierte Property `$block` und die nicht
typisierte `Block::service()`-Relation.

36 obsolete PHPStan-Muster und 11 veraltete PHPDoc-Baseline-Einträge entfernt;
keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests
grün. Zwischenzeitlich war die Partition voll; nach Bereinigung der
temporären Analyseausgaben liefen alle Tests wieder sauber.

## Vierte Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1.521 | 1.481 |
| Baseline-Muster | 1.444 | 1.408 |

Drei Pakete: `AbstractCalendarItem`, `SermonController`, `SongController`.
Echte Fehler: falsch typisierte `abort_unless()`-Bedingung im
Attachment-Trait, unbekannte `SongVerse`-Klasse und eine falsch
dokumentierte `getCategories()`-Rückgabe. Zusätzlich wurden viele
fehlende PHPDoc-Blöcke auf Gettern/Setzern und Controller-Klassen ergänzt.

36 obsolete PHPStan-Muster und 36 veraltete PHPDoc-Baseline-Einträge entfernt;
keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests
grün.

## Fünfte Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1.481 | 1.446 |
| Baseline-Muster | 1.408 | 1.369 |

Drei Pakete: `SingleMinistryReport`, `CalwerPredigtenStudyHelper`,
`Calendar/Day`. Echte Fehler: String statt int bei `getRowDimension()`,
`get()->pluck()` statt Query-`pluck()`, undefinierte DOM-Methoden,
nicht initialisierte Record-Variablen, `is_a()` mit String und ungültige
Carbon-Einzelmodifikatoren.

35 obsolete PHPStan-Muster und 8 veraltete PHPDoc-Baseline-Einträge entfernt;
keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests
grün.

## Sechste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1.446 | 1.405 |
| Baseline-Muster | 1.369 | 1.328 |

Drei Pakete: `AbstractSkeletonBuilder`, `CalendarBackend`, `AbstractSngFile`.
Echte Fehler: fehlende Rückgaben, `addHeader('Version', 3)` mit Integer
statt String und ein unsicherer `guessModel()`-Aufruf. Zusätzlich wurden
fehlende PHPDoc-Blöcke auf Gettern/Setzern und `boot()` ergänzt.

41 obsolete PHPStan-Muster und 23 veraltete PHPDoc-Baseline-Einträge entfernt;
keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests
grün.

## Siebte Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1.405 | 1.373 |
| Baseline-Muster | 1.328 | 1.304 |

Drei Pakete: `UpdateYoutubeStream`, `Wedding`, `RitesReport`. Echte Fehler:
Youtube-Properties am falschen Modell, nicht initialisierte `$last`-Variable,
String statt Boolean bei Auto-Start/Stop, Carbon-Modifikatoren, unerreichbare
`break`s, String-Zentimeterangaben und ein zu enger By-Ref-Parameter.

24 obsolete PHPStan-Muster und 7 veraltete PHPDoc-Baseline-Einträge entfernt;
keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests
grün.

## Achte Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1.373 | 1.339 |
| Baseline-Muster | 1.304 | 1.273 |

Drei Pakete: `CalendarService`, `ColorService`, `LiturgyService`. Echte
Fehler: ungültiger `@throws`-Typ, `subSecond(1)`, `explode()` mit Integer,
großes `ASC`, undefinierte RGB-Variablen, `dechex()` mit Float und eine
Zuweisung an die undefinierte Property `participantText`.

31 obsolete PHPStan-Muster und 2 veraltete PHPDoc-Baseline-Einträge entfernt;
keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests
grün.

## Neunte Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1.339 | 1.294 |
| Baseline-Muster | 1.273 | 1.229 |

Drei Pakete: `PMWueStudyHelper`, `AbstractCalendarLink`,
`AbstractLocalEventCalendar`. Echte Fehler: DOMNode statt DOMElement,
ein nicht initialisierter Titel, `preg_replace()`-Rückgabe und eine zu
enge `toArray()`-Deklaration. Zusätzlich fehlende PHPDoc-Blöcke auf
Konstruktoren und Gettern.

44 obsolete PHPStan-Muster und 18 veraltete PHPDoc-Baseline-Einträge entfernt;
keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests
grün.

## Zehnte Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1.294 | 1.269 |
| Baseline-Muster | 1.229 | 1.207 |

Drei Pakete: `DAVCalendarItem`, `PPTUnitsHelper`, `CalendarController`.
Echte Fehler: Magic-Offset-Zugriff auf Sabre-VObject-Properties, ungültige
Carbon-Modifikatoren, `explode()`/`mktime()` mit falschen Typen und ein
nicht auflösbarer View-Name. 22 obsolete PHPStan-Muster und 1 veralteter
PHPDoc-Baseline-Eintrag entfernt; keine neuen Ausnahmen. PHPStan, PHPDoc
und vollständige PHP-Tests grün.

## Elfte Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1.269 | 1.240 |
| Baseline-Muster | 1.207 | 1.178 |

Drei Pakete: `PrincipalBackend`, `CommuniAppIntegration`, `BibleText`.
Echte Fehler: eine leere `return;` mit falsch dokumentiertem Typ, zwei
ungenutzte Hook-Methoden, eine bereits migrierte `communiapp_id`-Property
und ein falsch dokumentierter `findByUri()`-Rückgabetyp.

29 obsolete PHPStan-Muster und 17 veraltete PHPDoc-Baseline-Einträge entfernt;
keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests
grün.

## Zwölfte Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1.240 | 1.165 |
| Baseline-Muster | 1.178 | 1.104 |

Drei Pakete: `ServiceTableReport`, `AbstractStudyHelper`,
`SongSheetLiturgySheet`. Echte Fehler: String statt int bei
`getRowDimension()`, eine überflüssige Null-Prüfung nach `isset()` und
ein unbekannter Helper-Typ. Die gemeinsamen Study-Helper-Verträge lösen
zusätzlich viele baselined Muster in kleinen Helfern auf.

74 obsolete PHPStan-Muster und 17 veraltete PHPDoc-Baseline-Einträge entfernt;
keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests
grün.

## Dreizehnte Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1.165 | 1.136 |
| Baseline-Muster | 1.104 | 1.078 |

Drei Pakete: `DefaultLabelSheetDocument`, `CalController`,
`SongReference`. Echte Fehler: nicht initialisierte `$row`, Carbon-
Modifikatoren, großes `ASC` und nicht erkannte Relationen in
`SongReference`. PHPDoc-Blöcke auf Klasse und Konstruktor ergänzt.

26 obsolete PHPStan-Muster und 10 veraltete PHPDoc-Baseline-Einträge entfernt;
keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests
grün.

## Vierzehnte Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1.136 | 1.109 |
| Baseline-Muster | 1.078 | 1.054 |

Drei Pakete: `SendApprovalNotification`, `Broadcast`, `FileNameService`.
Echte Fehler: falscher Event-Typ und nicht existierendes Mailable im
Listener, nicht initialisierte Variablen, ein String statt Boolean bei
YouTube und zu enge Parametertypen in `FileNameService`.

24 obsolete PHPStan-Muster und 1 veralteter PHPDoc-Baseline-Eintrag entfernt;
keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests
grün.

## Fünfzehnte Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1.109 | 1.079 |
| Baseline-Muster | 1.054 | 1.024 |

Drei Pakete: `AbstractSeatingModel`, `StringTool`, `PreventOrphans`-
Migration. Echte Fehler: undefinierte `$title`-Property und ein
unerreichbares `return`. Fehlende PHPDoc-Blöcke auf Gettern/Setzern
und Tool-Methoden ergänzt.

30 obsolete PHPStan-Muster und 14 veraltete PHPDoc-Baseline-Einträge entfernt;
keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests
grün.

## Sechzehnte Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1.079 | 1.055 |
| Baseline-Muster | 1.024 | 1.003 |

Drei Pakete: `DuskRunCommand`, `ODP`, `LiturgicalTextsController`.
Echte Fehler: nicht existierendes `--no-ansi`, unerreichbarer Code,
`setAttributeNS()` auf `DOMNameSpaceNode|DOMNode|false` und
`get()->pluck()` statt Query-`pluck()`. Fehlende PHPDoc-Blöcke auf
Konstruktoren und Helfern ergänzt.

21 obsolete PHPStan-Muster und 13 veraltete PHPDoc-Baseline-Einträge entfernt;
keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests
grün.

## Siebzehnte Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1.055 | 1.027 |
| Baseline-Muster | 1.003 | 976 |

Drei Pakete: `TemplateController`, `AbstractInput`, `FullTextLiturgySheet`.
Echte Fehler: falscher `Day`-Import, `get()->first()` statt `first()`,
großgeschriebene Sortierrichtung, ein `int`-Cast für PhpWord und ein
unklarer Helper-Typ. Fehlende PHPDoc-Blöcke auf Klassen und Konstruktoren
ergänzt.

27 obsolete PHPStan-Muster und 23 veraltete PHPDoc-Baseline-Einträge entfernt;
keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests
grün.

## Achtzehnte Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1.027 | 1.000 |
| Baseline-Muster | 976 | 952 |

Drei Pakete: `AbstractPronounSet`, `MinistryRequest`, `Replacement`.
Echte Fehler: undefinierte `Poolmaster::$user`-Zugriffe und eine fehlende
Relationstypisierung. Fehlende PHPDoc-Blöcke auf Klassen und `boot()`
ergänzt. 24 obsolete PHPStan-Muster und 7 veraltete PHPDoc-Baseline-
Einträge entfernt; keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und
vollständige PHP-Tests grün.

Die Baseline liegt jetzt bei genau 1.000 Rohbefunden.

## Neunzehnte Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1.000 | 977 |
| Baseline-Muster | 952 | 937 |

Drei Pakete: `Baptism`, `QuarterlyEventsReport`, `AttachmentFactory`.
Echte Fehler: Carbon-Modifikatoren, unerreichbare `break`s, String-
Zentimeterangaben, parameterlose `addText()`-Aufrufe und zu enge
Collection-Typen. Fehlende PHPDoc-Blöcke auf Klassen und Methoden ergänzt.

15 obsolete PHPStan-Muster und 6 veraltete PHPDoc-Baseline-Einträge entfernt;
keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests
grün.

## Zwanzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 977 | 956 |
| Baseline-Muster | 937 | 917 |

Drei Pakete: `PDF`, `FuneralController`, `HomeController`. Echte Fehler:
nullsafe-Aufruf auf non-nullable Typ, überflüssiger Null-Coalesce,
falsche Response-Klassen, ein View-String statt gerendertem Text und
zu enge Rückgabetypen. Fehlende PHPDoc-Blöcke auf Konstruktoren und
Methoden ergänzt.

20 obsolete PHPStan-Muster und 4 veraltete PHPDoc-Baseline-Einträge entfernt;
keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests
grün.

## Einundzwanzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 956 | 935 |
| Baseline-Muster | 917 | 896 |

Drei Pakete: `DefaultAddressLabelSheetDocument`, `EmbedController`,
`ReadingItemHelper`. Echte Fehler: undefinierte Variable im
Reading-Helper, ungültiger Carbon-Modifikator und fehlende PHPDoc-Blöcke
auf Konstruktoren. 21 obsolete PHPStan-Muster und 8 veraltete
PHPDoc-Baseline-Einträge entfernt; keine neuen Ausnahmen. PHPStan,
PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Zweiundzwanzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 935 | 914 |
| Baseline-Muster | 896 | 876 |

Drei Pakete: `StreamingTroubleshooterController`, `PlanningInput`,
`Sermon`. Echte Fehler: Nonnenzuweisung typisierter Parameter,
`get()->pluck()` statt Query-`pluck()`, ein zu enger `save()`-Rückgabetyp
und eine undefinierte `$name`-Property. Fehlende PHPDoc-Blöcke auf
Klassen und Methoden ergänzt. 20 obsolete PHPStan-Muster und 9 veraltete
PHPDoc-Baseline-Einträge entfernt; keine neuen Ausnahmen. PHPStan, PHPDoc
und vollständige PHP-Tests grün.

## Dreiundzwanzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 914 | 890 |
| Baseline-Muster | 876 | 861 |

Drei Pakete: `A4WordSpecificLiturgySheet`, `SongBeamer/AbstractFile`,
`SongPolicy`. Echte Fehler: PhpWord-Gleitkommawerte, undefinierte Helper-
Methoden und sieben `@return mixed`-Angaben, die dem nativen `bool`
widersprachen. Fehlende PHPDoc-Blöcke auf Klassen und Zugriffsmethoden
ergänzt. 15 obsolete PHPStan-Muster und 9 veraltete PHPDoc-Baseline-
Einträge entfernt; keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und
vollständige PHP-Tests grün.

## Vierundzwanzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 890 | 869 |
| Baseline-Muster | 861 | 846 |

Drei Pakete: `SongbookPolicy`, `AbstractCSVReport`, `EKDStatsReport`.
Echte Fehler: sieben `@return mixed`-Widersprüche zu nativem `bool`,
ein ungenutztes `cellAddress()`, eine ungültige `Carbon::createFromDate()`-
Signatur und ein unzureichender Schutz bei Options-Keys. Fehlende
PHPDoc-Blöcke auf Klassen ergänzt. 15 obsolete PHPStan-Muster und 4
veraltete PHPDoc-Baseline-Einträge entfernt; keine neuen Ausnahmen.
PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Fünfundzwanzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 869 | 850 |
| Baseline-Muster | 846 | 829 |

Drei Pakete: `AbstractPDFDocumentReport`, `ICalController`,
`ReportsController`. Echte Fehler: `View`-Objekte statt gerenderter Strings
in `str_replace()` und fehlende Parametertypen. Fehlende PHPDoc-Blöcke
auf Gettern/Setzern und `resolveReport()` ergänzt. 17 obsolete PHPStan-
Muster und 10 veraltete PHPDoc-Baseline-Einträge entfernt; keine neuen
Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Sechsundzwanzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 850 | 832 |
| Baseline-Muster | 829 | 814 |

Drei Pakete: `BaptismsHomeScreenTab`, `FuneralsHomeScreenTab`,
`Api/LiturgyController`. Echte Fehler: undefinierte `$query`-Property,
ungültige Carbon-Modifikatoren, großgeschriebene Sortierrichtung und
ein falscher `@param Service`. Fehlende PHPDoc-Blöcke auf Tabs und
Controller-Methoden ergänzt. 15 obsolete PHPStan-Muster und 20 veraltete
PHPDoc-Baseline-Einträge entfernt; keine neuen Ausnahmen. PHPStan,
PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Siebenundzwanzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 832 | 814 |
| Baseline-Muster | 814 | 797 |

Drei Pakete: `Api/RitesController`, `SettingsService`, `MinistryService`.
Echte Fehler: undefinierte Property-Namen durch Literal-Typen, verwaistes
`@param`, zu enger Rückgabetyp und `get()->pluck()` statt Query-`pluck()`.
Fehlende PHPDoc-Blöcke auf Konstruktoren und Methoden ergänzt. 17 obsolete
PHPStan-Muster und 9 veraltete PHPDoc-Baseline-Einträge entfernt; keine
neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Achtundzwanzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 814 | 796 |
| Baseline-Muster | 797 | 781 |

Drei Pakete: `EventCalendarImport`, `OPEventsImport`,
`SendRejectionNotification`. Echte Fehler: falscher `StreamInterface`-
Rückgabetyp, unerreichbarer Code, `env()` außerhalb der Konfiguration,
eine nicht existente Mailable-Klasse und ein ungültiger Carbon-Aufruf.

16 obsolete PHPStan-Muster und 1 veralteter PHPDoc-Baseline-Eintrag entfernt;
keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests
grün.

## Neunundzwanzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 796 | 778 |
| Baseline-Muster | 781 | 763 |

Drei Pakete: `MinistryRequestFilled`, `CalendarConnection`, `Vacations`.
Echte Fehler: `env()` außerhalb der Konfiguration, ungültiger Carbon-
Aufruf, unerreichbarer Code und ein impliziter Null-Rückfall. Der
VACATION_URL-Key wurde zentral in `config/calendar.php` ergänzt, damit
die Laufzeitkonfiguration erhalten bleibt. 18 obsolete PHPStan-Muster
und 7 veraltete PHPDoc-Baseline-Einträge entfernt; keine neuen Ausnahmen.
PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Dreißigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 778 | 761 |
| Baseline-Muster | 763 | 746 |

Drei Pakete: `InterstitialService`, `QueryLogService`,
`CasesHomeScreenTab`. Echte Fehler: ein ungültiger Array-Default bei
`SettingsService::get()`, eine immer falsche `is_a()`-Prüfung und
großgeschriebene Sortierrichtung. Fehlende PHPDoc-Blöcke auf Tabs und
Methoden ergänzt. 17 obsolete PHPStan-Muster und 6 veraltete
PHPDoc-Baseline-Einträge entfernt; keine neuen Ausnahmen. PHPStan,
PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Einunddreißigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 761 | 741 |
| Baseline-Muster | 746 | 729 |

Drei Pakete: `NormalizesBaptismData` (zwei Kontexte), `DAVCalendarItem`,
`Api/LiturgicalTextsController`. Echte Fehler: fehlende Array-Wertetypen,
undefinierte Zeitstempel-Properties, die nicht existierende Klasse
`ElementTest` und eine fehlende `return`-Anweisung. Fehlende PHPDoc-Blöcke
auf Klasse und Konstruktor ergänzt. 17 obsolete PHPStan-Muster und 5
veraltete PHPDoc-Baseline-Einträge entfernt; keine neuen Ausnahmen.
PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Zweiunddreißigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 741 | 716 |
| Baseline-Muster | 729 | 704 |

Drei Pakete: `NormalizesFuneralData`, `NormalizesWeddingData`,
`Api/SongController`. Echte Fehler: fehlende Array-Wertetypen in den
Normalisierungs-Traits und ein `push()` gemischter Zeilen auf eine
`Collection<int, SongReference>`. Fehlende PHPDoc-Parameter typisiert.
25 obsolete PHPStan-Muster und 6 veraltete PHPDoc-Baseline-Einträge
entfernt; keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige
PHP-Tests grün.

## Dreiunddreißigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 716 | 701 |
| Baseline-Muster | 704 | 690 |

Drei Pakete: `Auth/LoginController`, `ServiceController`, `UserController`.
Echte Fehler: großgeschriebene Sortierrichtung, zu enge Rückgabetypen und
eine `void`-Methode mit Redirect-Return. Die durch die `Role`-Typisierung
sichtbar gewordene, locker typierte Spatie-`permissions`-Relation wurde
lokal mit einem präzisen `@var` abgesichert. 14 obsolete PHPStan-Muster
und 6 veraltete PHPDoc-Baseline-Einträge entfernt; keine neuen Ausnahmen.
PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Vierundzwanzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 701 | 686 |
| Baseline-Muster | 690 | 675 |

Drei Pakete: `ServiceRequest`, `UserRequest`, `YoutubeIntegration`.
Echte Fehler: ein ungültiger Carbon-Aufruf und ein falsches `array|void`
bei `validated()`. Fehlende Array-Wertetypen und Parametertypen ergänzt.
15 obsolete PHPStan-Muster und 7 veraltete PHPDoc-Baseline-Einträge
entfernt; keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige
PHP-Tests grün.

## Fünfunddreißigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 686 | 671 |
| Baseline-Muster | 675 | 660 |

Drei Pakete: `PsalmItemHelper`, `SongBeamerLiturgySheet`, `PronounSets`.
Echte Fehler: ein falscher Helper-FQCN, fehlende Rückgabetypen und
Wertetypen. Fehlende PHPDoc-Blöcke auf Klasse und `render()` ergänzt.
15 obsolete PHPStan-Muster und 18 veraltete PHPDoc-Baseline-Einträge
entfernt; keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige
PHP-Tests grün.

## Sechsunddreißigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 671 | 655 |
| Baseline-Muster | 660 | 644 |

Drei Pakete: `Subscription`, `CustomAdChannelPolicy`, `TeamPolicy`.
Echte Fehler: nicht kovarianter `$fillable`-Typ, `env()` außerhalb der
Konfiguration, eine nicht existierende Modellklasse und fehlende
Policy-Rückgabetypen. Fehlender PHPDoc-Block auf der Policy-Klasse ergänzt.
16 obsolete PHPStan-Muster und 6 veraltete PHPDoc-Baseline-Einträge
entfernt; keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige
PHP-Tests grün.

## Siebenunddreißigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 655 | 640 |
| Baseline-Muster | 644 | 629 |

Drei Pakete: `AbstractWordDocumentReport`, `EmbedWebBuilderReport`,
`ServiceExcelTableReport`. Echte Fehler: falscher `JsonResponse`-
Rückgabetyp bei `setup()` und fehlende Property-/Array-Typen. Fehlende
PHPDoc-Blöcke auf Konstruktor und Hilfsmethoden ergänzt. 15 obsolete
PHPStan-Muster und 11 veraltete PHPDoc-Baseline-Einträge entfernt; keine
neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Achtunddreißigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 640 | 626 |
| Baseline-Muster | 629 | 615 |

Drei Pakete: `SeatingValidators`, `RedirectorService`,
`BaptismLettersAddressLabelsAttachment`. Fehlende Parameter-, Rückgabe-
und Property-Typen ergänzt; fehlende PHPDoc-Blöcke auf Klassen und
`download()` nachgeholt. 14 obsolete PHPStan-Muster und 10 veraltete
PHPDoc-Baseline-Einträge entfernt; keine neuen Ausnahmen. PHPStan,
PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Neununddreißigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 626 | 614 |
| Baseline-Muster | 615 | 603 |

Drei Pakete: `PfefferBibleInlayAttachment`, `CityEventsCalendarLink`,
`OrderScope`. Echte Fehler: ungültiger Carbon-Aufruf und fehlende Typen
bei PhpWord-Parametern, Properties und Konstruktoren. Fehlende PHPDoc-
Blöcke auf Methoden ergänzt; die `Builder<covariant Model>`-Signatur von
`OrderScope::apply()` ist mit dem Scope-Interface kompatibel. 12 obsolete
PHPStan-Muster und 6 veraltete PHPDoc-Baseline-Einträge entfernt; keine
neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Vierzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 614 | 602 |
| Baseline-Muster | 603 | 591 |

Drei Pakete: `GetLiturgyInfo`, `Handler`, `AbsencesHomeScreenTab`.
Echte Fehler: überflüssiger Null-Coalescing-Fall, nicht kovariante
Property-Typen im Exception-Handler und ein ungültiger Carbon-Aufruf.
Fehlende PHPDoc-Blöcke auf Klasse und Methoden des Home-Screen-Tabs
ergänzt. 12 obsolete PHPStan-Muster und 6 veraltete PHPDoc-Baseline-
Einträge entfernt; keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und
vollständige PHP-Tests grün.

## Einundvierzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 602 | 590 |
| Baseline-Muster | 591 | 579 |

Drei Pakete: `AbstractHomeScreen`, `AdminHomeScreen`, `Api/EventController`.
Echte Fehler: ungültige Carbon-Aufrufe und undefinierte Variablen in
`compact()`. Fehlende Parameter-, Rückgabe- und Array-Typen ergänzt.
12 obsolete PHPStan-Muster und 1 veralteter PHPDoc-Baseline-Eintrag
entfernt; keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige
PHP-Tests grün.

## Zweiundvierzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 590 | 577 |
| Baseline-Muster | 579 | 567 |

Drei Pakete: `Api/PoolController`, `PoolController`,
`SendAbsenceWorkflowNotification`. Echte Fehler: fehlender Relation-
Docblock im `Pool`-Modell, ungültiger Event-Typ `OrderShipped` und eine
möglich undefinierte `$recipients`-Variable. Fehlende PHPDoc-Blöcke auf
`boot()` und `getResourcesForEditor()` ergänzt. 12 obsolete PHPStan-
Muster und 7 veraltete PHPDoc-Baseline-Einträge entfernt; keine neuen
Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Dreiundvierzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 577 | 567 |
| Baseline-Muster | 567 | 559 |

Drei Pakete: `FuneralSermonTextLiturgySheet`, `SermonTextLiturgySheet`,
`SongPPTLiturgySheet`. Echte Fehler: redundante Null-Prüfungen bei
typisiertem `$service`, fehlende Rückgabetypen und ein unvollständig
typisierter Union-Property-Typ. 8 obsolete PHPStan-Muster und 4 veraltete
PHPDoc-Baseline-Einträge entfernt; keine neuen Ausnahmen. PHPStan,
PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Vierundvierzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 567 | 551 |
| Baseline-Muster | 559 | 543 |

Drei Pakete: `AbstractReplacer`, `Replacement`, `AbstractServiceMailable`.
Echte Fehler: fehlende Array-Wertetypen und dynamische Audit-Properties
eines Change-Tracking-Traits, die lokal über eine Object-Shape-Schnittmenge
abgesichert wurden. Fehlende PHPDoc-Blöcke auf sieben Methoden des
`AbstractReplacer` ergänzt. 16 obsolete PHPStan-Muster und 16 veraltete
PHPDoc-Baseline-Einträge entfernt; keine neuen Ausnahmen. PHPStan,
PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Fünfundvierzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 551 | 539 |
| Baseline-Muster | 543 | 531 |

Drei Pakete: `RegulatoryReportMail`, `AccountData`, `Absence`. Echte Fehler:
falscher `User`-FQCN in einer Mailable, nicht kovariante Modell-Properties
und zwei dynamische Accessoren, die über `@method`-Tags und einen engen
`__call`-Forwarder erhalten blieben. Fehlende PHPDoc-Blöcke auf Klassen,
`boot()` und Kalender-Methoden ergänzt. 12 obsolete PHPStan-Muster und 5
veraltete PHPDoc-Baseline-Einträge entfernt; keine neuen Ausnahmen.
PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Sechsundvierzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 539 | 523 |
| Baseline-Muster | 531 | 517 |

Drei Pakete: `Poolmaster`, `Song`, `City`. Echte Fehler: unsicheres
`new static()`, nicht beschreibbare Relation-Properties (jetzt
`setRelation()`), nicht kovariante `$fillable`-Typen und ein redundantes
Null-Coalescing. Fehlende PHPDoc-Blöcke auf `Poolmaster`-Methoden ergänzt.
14 obsolete PHPStan-Muster und 5 veraltete PHPDoc-Baseline-Einträge
entfernt; keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige
PHP-Tests grün.

## Siebenundvierzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 523 | 511 |
| Baseline-Muster | 517 | 505 |

Drei Pakete: `EventServiceProvider`, `AbstractReport`,
`LeaveRequestFormReport`. Echte Fehler: nicht kovariante Provider-
Properties, ein ungültiger `@var`-Tag und eine kaputte PDF-Erzeugung
(`renderPDF()` liefert HTML, nicht Mpdf). Die PDF-Erzeugung nutzt nun
`PDF::fromView()` wie `sendToFile()`. Fehlende PHPDoc-Blöcke ergänzt.
12 obsolete PHPStan-Muster und 5 veraltete PHPDoc-Baseline-Einträge
entfernt; keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige
PHP-Tests grün.

## Achtundvierzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 511 | 499 |
| Baseline-Muster | 505 | 493 |

Drei Pakete: `TravelRequestFormReport`, `SeatingModels`, `ImageService`.
Echte Fehler: dieselbe kaputte PDF-Erzeugung wie im Schwester-Report und
eine nicht existierende `App\Services\RuntimeException` (jetzt globale
`\RuntimeException`). Fehlende Rückgabe- und Array-Typen ergänzt.
12 obsolete PHPStan-Muster und 7 veraltete PHPDoc-Baseline-Einträge
entfernt; keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige
PHP-Tests grün.

## Neunundvierzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 499 | 485 |
| Baseline-Muster | 493 | 480 |

Drei Pakete: `DasKirchenjahrStudyHelper`, `CityFactory`,
`2025_12_30_092052_migrate_int_ids`. Echte Fehler: ungültiger
`Model`-FQCN im Factory-Generic (jetzt `Factory<City>`) und fehlende
Typen in der Migrationslogik. Fehlende PHPDoc-Blöcke auf Helper- und
Migrationsmethoden ergänzt. 13 obsolete PHPStan-Muster und 8 veraltete
PHPDoc-Baseline-Einträge entfernt; keine neuen Ausnahmen. PHPStan,
PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Fünfzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 485 | 474 |
| Baseline-Muster | 480 | 469 |

Drei Pakete: `CreateBooking`, `migrate_homescreentabs_setting`,
`convert_liturgic_to_freetext`. Echte Fehler: ein falsch benannter
`@var`-Variable und eine ungenutzte private Methode in der Migration, die
entfernt wurde. Fehlende Array-Wertetypen und Rückgabetypen ergänzt.
11 obsolete PHPStan-Muster und 6 veraltete PHPDoc-Baseline-Einträge
entfernt; keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige
PHP-Tests grün.

## Einundfünfzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 474 | 465 |
| Baseline-Muster | 469 | 460 |

Drei Pakete: `UpdateBooking`, `LocalEventCalendarFactory`,
`GenerateDatabaseKey`. Echte Fehler: `return $this->line()` bei einer
void-Methode und ein unvollständiger Rückgabetyp von `preg_match`.
Fehlende Array-Wertetypen und Docblocks ergänzt. 9 obsolete PHPStan-
Muster und 13 veraltete PHPDoc-Baseline-Einträge entfernt; keine neuen
Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Zweiundfünfzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 465 | 456 |
| Baseline-Muster | 460 | 451 |

Drei Pakete: `ServiceBeforeUpdate`, `AbstractZIPBasedFileFormat`,
`YoutubeHelper`. Echte Fehler: ungültiger `@param`-Tag ohne `$` und
unsicheres `new static()` — letzteres mit `@phpstan-consistent-constructor`
abgesichert, damit das Late Static Binding der Subklassen erhalten bleibt.
Fehlende PHPDoc-Blöcke ergänzt. 9 obsolete PHPStan-Muster und 15 veraltete
PHPDoc-Baseline-Einträge entfernt; keine neuen Ausnahmen. PHPStan,
PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Dreiundfünfzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 456 | 447 |
| Baseline-Muster | 451 | 442 |

Drei Pakete: `AbsenceRequestsHomeScreenTab`, `NextOfferingsHomeScreenTab`,
`StreamingHomeScreenTab`. Echte Fehler: fehlende Query-Typen und
ungültige Carbon-Aufrufe. Bei `NextOfferingsHomeScreenTab` wurde
`addMonth(2)` zu `addMonths(2)` korrigiert, damit das Zwei-Monats-Fenster
erhalten bleibt. Fehlende PHPDoc-Blöcke auf Klassen und Methoden ergänzt.
9 obsolete PHPStan-Muster und 17 veraltete PHPDoc-Baseline-Einträge
entfernt; keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige
PHP-Tests grün.

## Vierundfünfzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 447 | 435 |
| Baseline-Muster | 442 | 430 |

Drei Pakete: `WeddingsHomeScreenTab`, `AbstractCRUDController`,
`Api/ReportsController`. Echte Fehler: fehlende Query-/Route-Parameter-
typen, ein ungültiger Carbon-Aufruf (`addMonths(2)` erhält das
Zeitfenster) und großgeschriebene Sortierrichtung. Fehlende PHPDoc-Blöcke
ergänzt. 12 obsolete PHPStan-Muster und 8 veraltete PHPDoc-Baseline-
Einträge entfernt; keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und
vollständige PHP-Tests grün.

## Fünfundfünfzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 435 | 426 |
| Baseline-Muster | 430 | 422 |

Drei Pakete: `Api/ServiceController`, `BaptismController`,
`CommentController`. Echte Fehler: `get()->pluck()` statt Query-`pluck()`,
ungültige Carbon-Aufrufe, `View` statt gerendertem String in
`str_replace()` und zwei falsche Rückgabetypen. 8 obsolete PHPStan-Muster
entfernt; keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige
PHP-Tests grün.

## Sechsundfünfzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 426 | 417 |
| Baseline-Muster | 422 | 413 |

Drei Pakete: `Extranet/SermonController`, `Extranet/ServiceController`,
`TokenController`. Echte Fehler: dynamische `Sermon`-Properties, die über
`setAttribute()` statt direkter Zuweisung gesetzt werden, sowie fehlende
Rückgabetypen. Fehlende PHPDoc-Blöcke auf Klasse und Konstruktor ergänzt.
9 obsolete PHPStan-Muster und 9 veraltete PHPDoc-Baseline-Einträge
entfernt; keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige
PHP-Tests grün.

## Siebenundfünfzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 417 | 408 |
| Baseline-Muster | 413 | 404 |

Drei Pakete: `TrustProxies`, `ServiceResource`, `Inputs`. Echte Fehler:
nicht kovarianter Property-Typ, doppelter Array-Key `location`, falscher
`@var`-Typ und ein `array`/`Collection`-Widerspruch — die Collection wurde
bewusst beibehalten, weil eine leere Collection im Blade-`@if` wahr ist.
9 obsolete PHPStan-Muster entfernt; keine neuen Ausnahmen. PHPStan,
PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Achtundfünfzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 408 | 397 |
| Baseline-Muster | 404 | 393 |

Drei Pakete: `FreetextItemHelper`, `SongBeamer/Song`, `AdChannel`. Echte
Fehler: falscher Factory-FQCN, ein vom tatsächlichen Helper abweichender
Property-Typ und ein wirkungsloser Null-Coalescing-Fall. Fehlende PHPDoc-
Blöcke auf Klasse und Helper-Methoden ergänzt. 11 obsolete PHPStan-Muster
und 5 veraltete PHPDoc-Baseline-Einträge entfernt; keine neuen Ausnahmen.
PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Neunundfünfzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 397 | 387 |
| Baseline-Muster | 393 | 383 |

Drei Pakete: `Occurence`, `Comment`, `Parish`. Echte Fehler: nicht
kovariante Modell-Properties, ein undefinierter `$user` in einer
`array_walk()`-Closure und fehlende Relational-/Rückgabetypen. Fehlender
PHPDoc-Block auf `getLabelAttribute()` ergänzt. 10 obsolete PHPStan-Muster
und 3 veraltete PHPDoc-Baseline-Einträge entfernt; keine neuen Ausnahmen.
PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Sechzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 387 | 378 |
| Baseline-Muster | 383 | 374 |

Drei Pakete: `Funeral`, `ServiceGroup`, `AuthServiceProvider`. Echte
Fehler: nicht kovariante Properties, ein fehlender `Parish`-Import und
die öffentliche `age()`-Methode, die den gleichnamigen Accessor verdeckte.
Aufrufer in `KasualienReplacer` nutzen jetzt die Accessor-Property `$age`.
9 obsolete PHPStan-Muster entfernt; keine neuen Ausnahmen. PHPStan,
PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Einundsechzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 378 | 369 |
| Baseline-Muster | 374 | 365 |

Drei Pakete: `EmbedRegistrationReport`, `EmbedStreamingReport`,
`MinistryRequestReport`. Echte Fehler: nicht existierender Typ
`Inertia\Render`, zu enge `@return`-Tags, Float an `setTime()` und
`get()->pluck()` statt Query-`pluck()`. Fehlende PHPDoc-Blöcke auf Klasse
und `render()` ergänzt. 9 obsolete PHPStan-Muster und 3 veraltete
PHPDoc-Baseline-Einträge entfernt; keine neuen Ausnahmen. PHPStan,
PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Zweiundsechzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 369 | 358 |
| Baseline-Muster | 365 | 354 |

Drei Pakete: `AbstractSeatFinder`, `FirstRunInstallerService`,
`GPStudyHelper`. Reine Typangaben: Array-Wertetypen, Parameter- und
Rückgabetypen. Fehlende PHPDoc-Blöcke auf Installer- und StudyHelper-
Methoden ergänzt. 11 obsolete PHPStan-Muster und 10 veraltete
PHPDoc-Baseline-Einträge entfernt; keine neuen Ausnahmen. PHPStan,
PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Dreiundsechzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 358 | 349 |
| Baseline-Muster | 354 | 345 |

Drei Pakete: `LiturgischerWegweiserStudyHelper`,
`IncludesPermissionAttributes`, `TracksChangesTrait`. Reine Typangaben:
Array-Wertetypen, bool-Rückgaben der Berechtigungs-Accessoren und ein
Diff-Shape. Fehlende PHPDoc-Blöcke auf StudyHelper-Methoden ergänzt.
9 obsolete PHPStan-Muster und 8 veraltete PHPDoc-Baseline-Einträge
entfernt; keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige
PHP-Tests grün.

## Vierundsechzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 349 | 342 |
| Baseline-Muster | 345 | 338 |

Drei Pakete: `decrypt_old_schema`-Migration,
`NormalizesSeatingRowData`, `NormalizesSeatingSectionData`. Reine
Typangaben: `array<string, mixed>` bei den Normalisierungs-Traits und
Parameter-/Rückgabetypen in der Migration. 7 obsolete PHPStan-Muster und
4 veraltete PHPDoc-Baseline-Einträge entfernt; keine neuen Ausnahmen.
PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Fünfundsechzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 342 | 334 |
| Baseline-Muster | 338 | 330 |

Drei Pakete: `NormalizesTeamData`, `AbsencesCalendarLink`, `CalendarLinks`.
Echte Fehler: `array`/`Collection`-Widerspruch bei `getRenderData()` und
eine fehlende `return null;`-Anweisung in `findKey()`. Fehlende PHPDoc-
Blöcke ergänzt. 8 obsolete PHPStan-Muster und 6 veraltete PHPDoc-
Baseline-Einträge entfernt; keine neuen Ausnahmen. PHPStan, PHPDoc-
Prüfung und vollständige PHP-Tests grün.

## Sechsundsechzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 334 | 328 |
| Baseline-Muster | 330 | 324 |

Drei Pakete: `PersonalServicesCalendarLink`, `DefaultComposer`,
`DeletesPoolmasters`. Echte Fehler: `array`/`Collection`-Widerspruch und
ein fehlender `Poolmaster`-Import im `@method`-Tag. Fehlende PHPDoc-Blöcke
auf Klasse und `setDataFromRequest()` ergänzt. 6 obsolete PHPStan-Muster
und 3 veraltete PHPDoc-Baseline-Einträge entfernt; keine neuen Ausnahmen.
PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Siebenundsechzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 328 | 322 |
| Baseline-Muster | 324 | 318 |

Drei Pakete: `FoldedFlyerWordDocument`, `ServiceBeforeStore`, `FileHelper`.
Reine Typangaben: Style-Rückgabe mit explizitem `return null;`, Event-
`$data` und Helper-Parameter. 6 obsolete PHPStan-Muster und 1 veralteter
PHPDoc-Baseline-Eintrag entfernt; keine neuen Ausnahmen. PHPStan,
PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Achtundsechzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 322 | 316 |
| Baseline-Muster | 318 | 312 |

Drei Pakete: `NextServicesHomeScreenTab`,
`PredicantServicesHomeScreenTab`, `RegistrationsHomeScreenTab`. Query-
Typen ergänzt, ungültige Carbon-Aufrufe zu `addMonths(2)` korrigiert
(Zeitfenster unverändert). Fehlende PHPDoc-Blöcke auf Klassen und
Methoden nachgeholt. 6 obsolete PHPStan-Muster und 19 veraltete
PHPDoc-Baseline-Einträge entfernt; keine neuen Ausnahmen. PHPStan,
PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Neunundsechzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 316 | 310 |
| Baseline-Muster | 312 | 306 |

Drei Pakete: `AbsenceController`, `AdCentralController`,
`Api/TabController`. Echte Fehler: zu enge Rückgabetypen, ein dynamisches
`adStart`-Attribut (über `setAttribute()` gesetzt) und fehlende
`$tab`-Parameter. Fehlender PHPDoc-Block auf dem Konstruktor ergänzt.
6 obsolete PHPStan-Muster und 2 veraltete PHPDoc-Baseline-Einträge
entfernt; keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige
PHP-Tests grün.

## Siebzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 310 | 304 |
| Baseline-Muster | 306 | 300 |

Drei Pakete: `Api/UserController`, `AttachmentController`,
`Auth/EmailLoginLinkController`. Echte Fehler: `void`-Deklaration mit
JsonResponse-Return, dynamische `attachments`-Relation (lokal über eine
Object-Shape-Schnittmenge abgesichert) und fehlende Rückgabetypen.
PHPDoc-Block auf dem Konstruktor ergänzt. 6 obsolete PHPStan-Muster und
2 veraltete PHPDoc-Baseline-Einträge entfernt; keine neuen Ausnahmen.
PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Einund siebzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 304 | 298 |
| Baseline-Muster | 300 | 294 |

Drei Pakete: `CityController`, `Controller`, `GoogleApiController`.
Echte Fehler: `abort_unless()` mit Objekt statt bool, zu enge
Rückgabetypen und ein implizites `null`. Die durch `class-string<City>`
sichtbar gewordenen `getImageField()`-Aufrufe im
`HandlesAttachedImageTrait` wurden mit `method_exists()` abgesichert —
City liefert das Feld stets über die Route. 6 obsolete PHPStan-Muster
und 1 veralteter PHPDoc-Baseline-Eintrag entfernt; keine neuen
Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Zweiundsiebzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 298 | 292 |
| Baseline-Muster | 294 | 288 |

Drei Pakete: `PatchController`, `SettingsController`,
`CheckForMaintenanceMode`. Echte Fehler: nicht kovarianter `$except`-Typ
und fehlende Parameter-/Rückgabetypen bei Patch und Settings. Fehlende
PHPDoc-Blöcke auf Klasse und Konstruktor ergänzt. 6 obsolete PHPStan-
Muster und 5 veraltete PHPDoc-Baseline-Einträge entfernt; keine neuen
Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Dreiundsiebzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 292 | 286 |
| Baseline-Muster | 288 | 282 |

Drei Pakete: `EncryptCookies`, `TrimStrings`, `VerifyCsrfToken`. Reine
Typangaben: `$except` in allen drei Middlewares auf `array<int, string>`
kovariant zur Elternklasse gesetzt. 6 obsolete PHPStan-Muster entfernt;
keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests
grün.

## Vierundsiebzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 286 | 280 |
| Baseline-Muster | 282 | 276 |

Drei Pakete: `AbsenceRequest`, `FuneralStoreRequest`,
`CalendarServiceIdResource`. Echte Fehler: falscher `@var $this`-Typ und
fehlende Array-Wertetypen in `rules()`/`validated()`. PHPDoc-Blöcke auf
`getAbsence()`/`setAbsence()` ergänzt. 6 obsolete PHPStan-Muster und 2
veraltete PHPDoc-Baseline-Einträge entfernt; keine neuen Ausnahmen.
PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Fünfundsiebigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 280 | 274 |
| Baseline-Muster | 276 | 270 |

Drei Pakete: `CalendarServiceResource`, `EventResource`,
`KonfiAppIntegration`. Echte Fehler: widersprüchliche `@var $this`-
Annotationen in zwei Resources, ein ungültiger Carbon-Aufruf und ein
`@return mixed`-Widerspruch. 6 obsolete PHPStan-Muster entfernt; keine
neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Sechsundsiebigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 274 | 268 |
| Baseline-Muster | 270 | 265 |

Drei Pakete: `UnpublishYoutubeBroadcasts`, `PsalmLiturgySheet`,
`SongPPTLiturgySheet`. Fehlende Property-/Parametertypen ergänzt sowie
die `emuToPixels()`-Argumente ganzzahlig gecastet. PHPDoc-Block auf
`render()` nachgeholt. 5 obsolete PHPStan-Muster und 2 veraltete
PHPDoc-Baseline-Einträge entfernt; keine neuen Ausnahmen. PHPStan,
PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Siebenundsiebigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 268 | 262 |
| Baseline-Muster | 265 | 259 |

Drei Pakete: `ABCMusic`, `SongBeamer/Psalm`, `ContactFormMessage`.
Echte Fehler: zwei möglicherweise undefinierte Variablen und ein zu enger Helper-
Typ im Psalm-Objekt (direkte Instantiierung wie bei `Song`). Fehlende
Array-Typen im Mailable ergänzt. 6 obsolete PHPStan-Muster entfernt;
keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige
PHP-Tests grün.

## Achtundsiebigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 262 | 256 |
| Baseline-Muster | 259 | 254 |

Drei Pakete: `ServiceUpdated`, `AbstractModel`, `Announcements`.
Echte Fehler: `print_r(..., 1)` statt `true`, unsicheres `new static()`
(abgesichert mit `@phpstan-consistent-constructor`) und fehlende
Parametertypen. PHPDoc-Blöcke auf sieben `AbstractModel`-Methoden
ergänzt. 5 obsolete PHPStan-Muster und 8 veraltete PHPDoc-Baseline-
Einträge entfernt; keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und
vollständige PHP-Tests grün.

## Neunundsiebigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 256 | 250 |
| Baseline-Muster | 254 | 248 |

Drei Pakete: `Booking`, `SeatingSection`, `UserSetting`. Echte Fehler:
falscher `@return BelongsTo` an einem String-Accessor, `str_pad()` mit
int-Padding und nicht kovariante Modell-Properties. Der
`SeatingModel`-Mutator wurde mit `object|string` typisiert. 6 obsolete
PHPStan-Muster und 1 veralteter PHPDoc-Baseline-Eintrag entfernt; keine
neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Achtzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 250 | 244 |
| Baseline-Muster | 248 | 242 |

Drei Pakete: `ServicePolicy`, `AbstractExcelDocumentReport`,
`ChildrensChurchReport`. Echte Fehler: falscher `mixed`-Rückgabetyp in
der Policy, ungültiger Carbon-Aufruf und großgeschriebene Sortierrichtung.
PHPDoc-Block auf dem Konstruktor des Excel-Reports ergänzt. 6 obsolete
PHPStan-Muster und 2 veraltete PHPDoc-Baseline-Einträge entfernt; keine
neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Einundachtzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 244 | 238 |
| Baseline-Muster | 242 | 237 |

Drei Pakete: `GiroCodeQRReport`, `KonfiAppQRReport`,
`ServicesByWTCCategoryReport`. Echte Fehler: zu enge `setup()`-Rückgaben,
zwei möglicherweise undefinierte `$service`-Variablen und zwei
`get()->pluck()`-Aufrufe. 5 obsolete PHPStan-Muster entfernt; keine
neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Zweiundachtzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 238 | 232 |
| Baseline-Muster | 237 | 231 |

Drei Pakete: `WeeklyVerseReport`, `Seatable`, `SeatableFixed`. Echte
Fehler: `addDay(1)`, ein überzähliges Argument an `beautify()` und
fehlende Array-Wertetypen in den beiden Rules. PHPDoc-Blöcke auf
`validate()` ergänzt. 6 obsolete PHPStan-Muster und 4 veraltete
PHPDoc-Baseline-Einträge entfernt; keine neuen Ausnahmen. PHPStan,
PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Dreiundachtzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 232 | 225 |
| Baseline-Muster | 231 | 225 |

Drei Pakete: `AbstractSeatingModel`, `RowBasedSeatFinder`,
`RowBasedSeatingModel`. PHPStan ignoriert `@property`-Tags auf einfachen
Klassen — `$title` wurde deshalb direkt in der Basisklasse deklariert.
Fehlende PHPDoc-Blöcke auf vier Sitzplatz-Methoden ergänzt. 6 obsolete
PHPStan-Muster und 5 veraltete PHPDoc-Baseline-Einträge entfernt; keine
neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Vierundachtzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 225 | 219 |
| Baseline-Muster | 225 | 219 |

Drei Pakete: `AmazonService`, `QRService`, `ResourcePolicyService`.
Echte Fehler: unbekannte `App\Services\RuntimeException` (jetzt globale
`\RuntimeException`) und fehlende Typen bei ISBN-/Collection-Parametern.
PHPDoc-Block auf der AmazonService-Klasse ergänzt. 6 obsolete PHPStan-
Muster und 4 veraltete PHPDoc-Baseline-Einträge entfernt; keine neuen
Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Fünfundachtzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 219 | 212 |
| Baseline-Muster | 219 | 212 |

Drei Pakete: `EFPStudyHelper`, `HasLiturgicalInfo`, `MenuBuilder`.
Echte Fehler: undefinierte `$doc`-Variable, eine redundante Array-Prüfung
und fehlende Array-Wertetypen im Menü-Builder. PHPDoc-Blöcke auf zwei
StudyHelper-Methoden ergänzt. 7 obsolete PHPStan-Muster und 2 veraltete
PHPDoc-Baseline-Einträge entfernt; keine neuen Ausnahmen. PHPStan,
PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Sechsundachtzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 212 | 206 |
| Baseline-Muster | 212 | 206 |

Drei Pakete: `Modules`, `2019_08_06_132505_extend_city_user_pivot`,
`2022_04_08_112800_add_songbooks_table`. Echte Fehler: falscher
`Doctrine\DBAL\Schema\Table`-FQCN (jetzt `Blueprint`) und fehlende
Rückgabetypen. 6 obsolete PHPStan-Muster und 1 veralteter PHPDoc-
Baseline-Eintrag entfernt; keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung
und vollständige PHP-Tests grün.

## Siebenundachtzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 206 | 201 |
| Baseline-Muster | 206 | 201 |

Drei Pakete: `Alt-Proprium`-Migration, `routes/web.php`,
`CreateAdChannel`. Echte Fehler: `get()->pluck()` in der Migration und
fehlende Typen bei `routeNames()` sowie im Action-Input. 5 obsolete
PHPStan-Muster und 3 veraltete PHPDoc-Baseline-Einträge entfernt; keine
neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Achtundachtzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 201 | 198 |
| Baseline-Muster | 201 | 198 |

Drei Pakete: `UpdateAdChannel`, `CreateAdConfig`, `UpdateAdConfig`.
Reine PHPDoc-Ergänzungen (`@param array<string, mixed> $input`) in den
seriellen Actions. 3 obsolete PHPStan-Muster und 5 veraltete PHPDoc-
Baseline-Einträge entfernt; keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung
und vollständige PHP-Tests grün.

## Neunundachtzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 198 | 195 |
| Baseline-Muster | 198 | 195 |

Drei Pakete: `UpdateAttachment`, `CreateBaptism`, `UpdateBaptism`.
Reine PHPDoc-Ergänzungen (`@param array<string, mixed> $input`),
zusätzlich ein Block auf `getServiceSlug()`. 3 obsolete PHPStan-Muster
und 9 veraltete PHPDoc-Baseline-Einträge entfernt; keine neuen Ausnahmen.
PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Neunzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 195 | 192 |
| Baseline-Muster | 195 | 192 |

Drei Pakete: `CreateCity`, `UpdateCity`, `CreateComment`. Reine PHPDoc-
Ergänzungen (`@param array<string, mixed> $input`). 3 obsolete PHPStan-
Muster und 1 veralteter PHPDoc-Baseline-Eintrag entfernt; keine neuen
Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Einundneunzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 192 | 189 |
| Baseline-Muster | 192 | 189 |

Drei Pakete: `CreateFuneral`, `UpdateFuneral`, `CreateLocation`.
Reine PHPDoc-Ergänzungen; zusätzlich ein Block auf `getServiceSlug()`.
3 obsolete PHPStan-Muster und 4 veraltete PHPDoc-Baseline-Einträge
entfernt; keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige
PHP-Tests grün.

## Zweiundneunzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 189 | 186 |
| Baseline-Muster | 189 | 186 |

Drei Pakete: `UpdateLocation`, `CreateParish`, `UpdateParish`. Reine
PHPDoc-Ergänzungen (`@param array<string, mixed> $input`). 3 obsolete
PHPStan-Muster entfernt; keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung
und vollständige PHP-Tests grün.

## Dreiundneunzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 186 | 183 |
| Baseline-Muster | 186 | 183 |

Drei Pakete: `CreatePool`, `UpdatePool`, `CreatePoolmaster`. Reine
PHPDoc-Ergänzungen (`@param array<string, mixed> $input`). 3 obsolete
PHPStan-Muster und 1 veralteter PHPDoc-Baseline-Eintrag entfernt; keine
neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Vierundneunzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 183 | 180 |
| Baseline-Muster | 183 | 180 |

Drei Pakete: `DeletePoolmaster`, `UpdatePoolmaster`, `CreatePsalm`.
`$forward`-Property mit Wertetyp versehen und Input-Parameter in den
Actions dokumentiert. 3 obsolete PHPStan-Muster und 3 veraltete
PHPDoc-Baseline-Einträge entfernt; keine neuen Ausnahmen. PHPStan,
PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Fünfundneunzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 180 | 177 |
| Baseline-Muster | 180 | 177 |

Drei Pakete: `UpdatePsalm`, `CreateReplacement`, `UpdateReplacement`.
Reine PHPDoc-Ergänzungen (`@param array<string, mixed> $input`). 3 obsolete
PHPStan-Muster und 6 veraltete PHPDoc-Baseline-Einträge entfernt; keine
neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Sechsundneunzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 177 | 174 |
| Baseline-Muster | 177 | 174 |

Drei Pakete: `CreateSeatingRow`, `UpdateSeatingRow`,
`CreateSeatingSection`. Reine PHPDoc-Ergänzungen (`@param array<string,
mixed> $input`). 3 obsolete PHPStan-Muster und 9 veraltete PHPDoc-
Baseline-Einträge entfernt; keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung
und vollständige PHP-Tests grün.

## Siebenundneunzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 174 | 171 |
| Baseline-Muster | 174 | 171 |

Drei Pakete: `UpdateSeatingSection`, `CreateServiceGroup`,
`UpdateServiceGroup`. Reine PHPDoc-Ergänzungen (`@param array<string,
mixed> $input`). 3 obsolete PHPStan-Muster und 12 veraltete PHPDoc-
Baseline-Einträge entfernt; keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung
und vollständige PHP-Tests grün.

## Achtundneunzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 171 | 168 |
| Baseline-Muster | 171 | 168 |

Drei Pakete: `CreateSong`, `UpdateSong`, `CreateSongbook`. Reine
PHPDoc-Ergänzungen (`@param array<string, mixed> $input`). 3 obsolete
PHPStan-Muster und 15 veraltete PHPDoc-Baseline-Einträge entfernt; keine
neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Neunundneunzigste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 168 | 165 |
| Baseline-Muster | 168 | 165 |

Drei Pakete: `UpdateSongbook`, `CreateStreetRange`,
`UpdateStreetRange`. Reine PHPDoc-Ergänzungen (`@param array<string,
mixed> $input`). 3 obsolete PHPStan-Muster und 18 veraltete PHPDoc-
Baseline-Einträge entfernt; keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung
und vollständige PHP-Tests grün.

## Hundertste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 165 | 162 |
| Baseline-Muster | 165 | 162 |

Drei Pakete: `CreateTag`, `UpdateTag`, `CreateTeam`. Reine PHPDoc-
Ergänzungen (`@param array<string, mixed> $input`). 3 obsolete PHPStan-
Muster und 19 veraltete PHPDoc-Baseline-Einträge entfernt; keine neuen
Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Einhunderterste Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 162 | 159 |
| Baseline-Muster | 162 | 159 |

Drei Pakete: `UpdateTeam`, `CreateWedding`, `UpdateWedding`. Reine
PHPDoc-Ergänzungen; zusätzlich ein Block auf `getServiceSlug()`. 3 obsolete
PHPStan-Muster und 23 veraltete PHPDoc-Baseline-Einträge entfernt; keine
neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Einhundertzweite Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 159 | 115 |
| Baseline-Muster | 159 | 115 |

Drei Sammelpakete für alle 44 `Contracts`-Interfaces: `$input` in
`@method`-Tags bzw. nativen Signaturen mit `array<string, mixed>` typisiert.
44 obsolete PHPStan-Muster und 8 veraltete PHPDoc-Baseline-Einträge
entfernt; keine neuen Ausnahmen. PHPStan, PHPDoc-Prüfung und vollständige
PHP-Tests grün.

## Einhundertdritte Subagentenwelle

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 115 | 47 |
| Baseline-Muster | 115 | 47 |

Drei Sammelpakete über 69 Dateien: fehlende Array-/Property-/Rückgabe-
typen, dynamische Properties (über `setAttribute()`), ungültige
Carbon-/Collection-Aufrufe und fehlende Returns. PHPDoc-Blöcke auf 26
Deklarationen zentral ergänzt. 68 obsolete PHPStan-Muster und 41 veraltete
PHPDoc-Baseline-Einträge entfernt; keine neuen Ausnahmen. PHPStan,
PHPDoc-Prüfung und vollständige PHP-Tests grün.

## Einhundertvierte Subagentenwelle — Ziel erreicht

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 47 | 0 |
| Baseline-Muster | 47 | 0 |

Drei Pakete: `Service`-Modell (13), Batch B (16) und Batch C (18 Dateien).
Die 12 `rules.modelAppends`-Funde im `Service`-Modell wurden durch
Umbenennung kollidierender Hilfsmethoden und Entfernung redundanter
`$appends`-Einträge gelöst. Das ungenutzte `TestWithCredentialsTrait`
wurde entfernt. Ein veralteter Baseline-Pfad musste manuell entfernt
werden, damit PHPStan wieder starten konnte. PHPDoc-Blöcke auf 13
Deklarationen zentral ergänzt. 46 obsolete PHPStan-Muster und 22
veraltete PHPDoc-Baseline-Einträge entfernt.

**Damit ist die Level-6-Baseline vollständig abgebaut:** PHPStan Level 6
ohne Baseline meldet 0 Befunde; `analyse`, `docs:check` und die
vollständige PHP-Suite (1205 Tests, 4656 Assertions) sind grün.

## Umstieg auf PHPStan Level 8

Nachdem die Level-6-Baseline vollständig abgebaut war, wurde der
verbindliche Prüfmaßstab in `src/phpstan.neon` auf **Level 8** angehoben.
Aus einer leeren Baseline wurde eine neue Level-8-Baseline erzeugt; sie
umfasst **1785 Befunde in 1235 Mustern** (Rohmessung ohne Baseline: 1785).

Der Gate ist damit weiterhin grün: PHPStan Level 8, PHPDoc-Prüfung und die
vollständige PHP-Suite (1207 Tests, 4668 Assertions) laufen ohne Fehler.
Das technische Handbuch (`codequalitaet-und-workflow.md`) ist auf Level 8
nachgezogen. Die Level-8-Altlasten sind nun die nächste Ratchet-Stufe auf
dem Weg zu Level 10.

## Level-8-Welle 1

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1785 | 1610 |
| Baseline-Muster | 1235 | 1146 |

Drei Pakete: `SongPPTLiturgySheet` (68), `HandlesAttachmentsTrait` (54),
`UserController` (53). Null-Absicherungen mit `?->`/Guards, präzise
Collection-Typen, `abort_unless`-Absicherung von `Auth::user()` und
explizite Casts für `Storage::put()`. Im Trait wurde ein zunächst
unauflösbarer `@var`-Typ durch eine `method_exists()`-Prüfung ersetzt.
92 obsolete PHPStan-Muster entfernt; keine neuen Ausnahmen. PHPStan Level 8,
PHPDoc-Prüfung und vollständige PHP-Tests (1207) grün.

## Level-8-Welle 2

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1610 | 1486 |
| Baseline-Muster | 1146 | 1065 |

Drei Pakete: `AnnouncementsReport`, `Service`, `Announcements`. Nullbare
Relationen und Datumswerte über Null-Guards, `?->` und lokale `Carbon`-/
`City`-Variablen abgesichert; `findOrFail()`-IDs ganzzahlig gecastet.
Ungültige `(string)`-Casts auf `list<string>|string` durch lokale `@var
string` ersetzt; PHPDoc-Blöcke auf acht Announcements-Deklarationen
ergänzt. 81 obsolete PHPStan-Muster und 8 veraltete PHPDoc-Baseline-
Einträge entfernt; keine neuen Ausnahmen. PHPStan Level 8, PHPDoc-Prüfung
und vollständige PHP-Tests (1207) grün.

## Level-8-Welle 3

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1486 | 1396 |
| Baseline-Muster | 1065 | 1018 |

Drei Pakete: `RowBasedSeatFinder`, `AbsenceController`,
`FuneralSermonTextLiturgySheet`. Null-Guards und `?->` für模型-/Service-
Properties, `abort_unless`-Absicherung von `Auth::user()`, lokale
`@var`-Typangaben. Zwei neu aufgetauchte Befunde im Sheet (Array-Union an
`addText()`, überflüssiges Nullsafe) zentral korrigiert. 47 obsolete
PHPStan-Muster entfernt; keine neuen Ausnahmen. PHPStan Level 8,
PHPDoc-Prüfung und vollständige PHP-Tests (1207) grün.

## Level-8-Welle 4

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1396 | 1319 |
| Baseline-Muster | 1018 | 988 |

Drei Pakete: `ServiceController`, `CalendarController`, `Funeral`-Modell.
`Auth::user()` und Relationen über `abort_unless`/`instanceof` abgesichert,
Carbon-Mutation über `copy()` verhindert, nullbare Datumswerte mit `?->`
behandelt. Zwei überflüssige Nullsafe-Aufrufe im Funeral-Modell und vier
fehlende PHPDoc-Blöcke zentral korrigiert. 30 obsolete PHPStan-Muster und
4 veraltete PHPDoc-Baseline-Einträge entfernt; keine neuen Ausnahmen.
PHPStan Level 8, PHPDoc-Prüfung und vollständige PHP-Tests (1207) grün.

## Level-8-Welle 5

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1319 | 1245 |
| Baseline-Muster | 988 | 950 |

Drei Pakete: `SermonHandoutLiturgySheet`, `KasualienReplacer`,
`convert_liturgic_to_freetext`-Migration. Null-Guards und `?->` für
Services, Riten, Datumswerte und PhpWord-Objekte; lokale `@var`-Typen
für Arrays und Pronoun-Sets. 38 obsolete PHPStan-Muster entfernt; keine
neuen Ausnahmen. PHPStan Level 8, PHPDoc-Prüfung und vollständige
PHP-Tests (1207) grün.

## Level-8-Welle 6

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1245 | 1177 |
| Baseline-Muster | 950 | 917 |

Drei Pakete: `PowerPoint`, `PlanningInput`, `ODP`. DOM-Knoten und
`DOMXPath`-Ergebnisse über `instanceof`/Guards abgesichert, `Auth::user()`
und `Carbon`-Werte mit `abort_unless` eingegrenzt. Eine überflüssige
`=== null`-Prüfung und zwei fehlende PHPDoc-Blöcke zentral korrigiert.
33 obsolete PHPStan-Muster und 2 veraltete PHPDoc-Baseline-Einträge
entfernt; keine neuen Ausnahmen. PHPStan Level 8, PHPDoc-Prüfung und
vollständige PHP-Tests (1207) grün.
## Level-8-Welle 8

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1177 | 103 |
| Baseline-Muster | 917 | 917 |

Drei Pakete: `BulletinBLReport`, `FullTextLiturgySheet`, `Streaming/Broadcast`.
Auth::user() null-Guards, `?->` zu `->` Umwandlung mit null-sicheren Mustern,
Service-Stadt-Zugriffe konsequent vergeben. 3 `ignore.count`-Einträge in der Baseline
angepasst; keine neuen Ausnahmen. PHPStan Level 8, PHPDoc-Prüfung und
volle PHP-Tests (1207) grün.

## Level-8-Welle 10 — Baseline auf 0

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 214 | 0 |
| Baseline-Muster | 917 | 0 |

Drei Pakete: A (Mail/Listener/Policies/SyncEngine/PPTUnitsHelper, 41), B
(Commands/Policies/IDML/HomeScreens/Jobs, 37), C (Traits/Migrationen/Helpers/
Seating/StringTool, 44). Zusätzlich 72 Dateien zentral korrigiert:
Null-Guards für `Auth::user()`, `City`/`Service`-Relationen und `Carbon`-Werte,
`glob()`-Fallbacks, `file_get_contents()`-Absicherungen, `findOrFail()`-Casts,
`view-string`-Annotationen und korrekte generische Factory-Callbacks.

`setPrivacyStatus`, `whereRaw`/`orderByRaw` und `DB::raw` wurden typsicher
umgesetzt. 176 obsolete PHPStan-Muster entfernt, Baseline ist jetzt leer
(`ignoreErrors: []`). PHPDoc-Baseline auf 1007 Einträge aktualisiert.
Pint, PHPStan Level 8, PHPDoc-Prüfung und vollständige PHP-Tests (1207) grün.

## Umstieg auf PHPStan Level 10

Der verbindliche Prüfmaßstab in `src/phpstan.neon` wurde auf **Level 10**
angehoben. Aus der leeren Level-8-Baseline wurde eine neue Level-10-Baseline
erzeugt: **3700 Rohbefunde / 2418 Muster**. Der Gate bleibt grün: PHPStan
Level 10, PHPDoc-Prüfung und vollständige PHP-Tests (1207) laufen ohne Fehler.
Das technische Handbuch (`codequalitaet-und-workflow.md`) ist auf Level 10
nachgezogen. Die Level-10-Altlasten sind die nächste Ratchet-Stufe.

## Level-10-Welle 1

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 3700 | 2969 |
| Baseline-Muster | 2418 | 2039 |

Drei Pakete: `BillBoardReport`, `HandlesAttachmentsTrait`, `SongPPTLiturgySheet`.
Zusätzlich zentral: Permission-Migration, `TracksDeletedByTrait`,
`DefaultWordDocument`. 400 obsolete PHPStan-Muster entfernt; 13 Restbefunde
im `BillBoardReport` (Casts, Nullsafe, Magic-Attribute) zentral korrigiert.
PHPDoc-Baseline auf 1007 Einträge aktualisiert. PHPStan Level 10,
PHPDoc-Prüfung und vollständige PHP-Tests (1207) grün.

## Level-10-Welle 2

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 2969 | 2681 |
| Baseline-Muster | 2039 | 1897 |

Drei Pakete: `Announcements` (133), `BulletinReport` (78), `Broadcast` (64).
Generics und Shape-Typen für Collections und Request-Daten, Google-API-
Ressourcen über getippte Helfer, Null-Guards und `stringify()`-Helper.
142 obsolete PHPStan-Muster entfernt; PHPDoc-Baseline auf 1011 Einträge
aktualisiert. PHPStan Level 10, PHPDoc-Prüfung und PHP-Tests (1207) grün.

## Level-10-Welle 3

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 2681 | 2483 |
| Baseline-Muster | 1897 | 1765 |

Drei Pakete: `BulletinBLReport` (59), `Service`-Modell (55), `PapierkorbController`
und `UserController` (86). Type-Narrowing für Request-/Setting-Werte,
Shape-Typen für Collections, `stringify()`-Helper im Service-Modell.
132 obsolete PHPStan-Muster entfernt; 17 Restbefunde zentral korrigiert.
PHPDoc-Baseline auf 1011 Einträge aktualisiert. PHPStan Level 10,
PHPDoc-Prüfung und PHP-Tests (1207) grün.

## Level-10-Welle 4

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 2483 | 2232 |
| Baseline-Muster | 1765 | 1621 |

Drei Pakete: Berichte A (`EKDStatsReport`, `AnnouncementsReport`), Liturgie
(`SongItemHelper`, `BekanntgabenReplacer`), Berichte C (`MinistryRequestReport`,
`SingleMinistryReport`, `EventListReport`). Shape-Typen für Request-/Event-Daten,
`is_string`/`is_array`-Guards, `int`-Casts in Versbereichen. 144 obsolete
PHPStan-Muster entfernt; 2 Restbefunde im `SongItemHelper` zentral korrigiert.
PHPStan Level 10, PHPDoc-Prüfung und PHP-Tests (1207) grün.

## Level-10-Welle 5

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 2232 | 2028 |
| Baseline-Muster | 1621 | 1498 |

Drei Pakete: Paket A (`TermineReplacer`, `ServiceTableReport`), Paket B
(`User`-Modell, `CalendarService`), Liturgie-Sheets (`FullText`,
`A4WordSpecific`, `AIPrompt`). Shape-Typen für Event-/Request-Daten und
Day-Maps, `instanceof`-Guards, `DateTimeInterface`-Annotationen. 123 obsolete
PHPStan-Muster entfernt; 6 Restbefunde zentral korrigiert. PHPDoc-Baseline
auf 1011 Einträge aktualisiert. PHPStan Level 10, PHPDoc-Prüfung und
PHP-Tests (1207) grün.

## Level-10-Welle 6

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 2028 | 1803 |
| Baseline-Muster | 1498 | 1365 |

Drei Pakete: Berichte (`ServicesByWTCCategoryReport`,
`ReplaceableServicesReport`, `ServiceExcelTableReport`), Attachments/Sheets
(`HandlesAttachedImageTrait`, `SermonHandoutLiturgySheet`,
`SongMailLiturgySheet`), Seating/API/Import (`RowBasedSeatFinder`,
`Api/ServiceController`, `OPEventsImport`). Shape-Typen für Request-/Item-Daten,
`is_string`/`is_array`-Guards, `class-string<Model>`-Annotationen. 133 obsolete
PHPStan-Muster entfernt. PHPDoc-Baseline auf 1011 Einträge aktualisiert.
PHPStan Level 10, PHPDoc-Prüfung und PHP-Tests (1207) grün.

## Level-10-Welle 7

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1803 | 1607 |
| Baseline-Muster | 1365 | 1235 |

Drei Pakete: A (`ServiceThemesReport`, `InterstitialService`, `Wedding`),
B (`EmbedWebBuilderReport`, `MinistrySignupSheetReport`, `PlanningInput`),
C (`SongSheetLiturgySheet`, `ReadingItemHelper`, `BaptismController`).
Shape-Typen für Request-/Config-Daten, Scalar-Narrowing für Item-/Versdaten,
User-Guards in Controllern. 130 obsolete PHPStan-Muster entfernt.
PHPDoc-Baseline auf 1011 Einträge aktualisiert. PHPStan Level 10,
PHPDoc-Prüfung und PHP-Tests (1207) grün.

## Level-10-Welle 8

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1607 | 1434 |
| Baseline-Muster | 1235 | 1141 |

Drei Pakete: Migrationen (`migrate_int_ids`, `add_cc_fields`,
`add_service_fields2`), Berichte (`OfferingPlanReport`, `QuarterlyEventsReport`,
`RitesReport`), Controller (`FuneralController`, `PublicController`,
`WeddingController`). Blueprint-/JoinClause-Typen in Migrations-Closures,
Shape-Typen für Request-Daten, `getAttribute()` für dynamische Rite-Attribute,
`instanceof`-Guards. 94 obsolete PHPStan-Muster entfernt; 5 Restbefunde
zentral korrigiert. PHPDoc-Baseline auf 1011 Einträge aktualisiert.
PHPStan Level 10, PHPDoc-Prüfung und PHP-Tests (1207) grün.

## Level-10-Welle 9

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1434 | 1275 |
| Baseline-Muster | 1141 | 1015 |

Drei Pakete: A (`ChildrensChurchInput`, `Item`, `NormalizesBaptismData`),
B (`EventCalendarImport`, `SongBeamer/Song`, `ServicesByLocationCSVReport`),
C (`WeeklyVerseReport`, `ServiceController`, `CalendarLiturgicalInfoResource`,
`HasCityScopes`). Shape-Typen für validierte Daten und Liturgie-Einträge,
`is_scalar`/`is_string`-Guards, präzise `@return`-Typen. 126 obsolete
PHPStan-Muster entfernt. PHPDoc-Baseline auf 1008 Einträge aktualisiert.
PHPStan Level 10, PHPDoc-Prüfung und PHP-Tests (1207) grün.

## Level-10-Welle 10

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 1275 | 904 |
| Baseline-Muster | 1015 | 761 |

Drei Pakete mit je 10 Dateien (Normalizer/DAV/HomeController/SongBeamer,
Helpers/Modelle/Controller, Reports/Migrationen/StudyHelper). Erstmals größere
Batches, da sich die restlichen Befunde auf über 300 Dateien mit je ~10–14
Findings verteilen. Shape-Typen, `is_scalar`/`is_string`-Guards, `VEvent`-Typ,
`getAttribute()` statt dynamischer Properties. 254 obsolete PHPStan-Muster
entfernt; 4 Restbefunde zentral korrigiert. PHPDoc-Baseline auf 1024 Einträge
aktualisiert. PHPStan Level 10, PHPDoc-Prüfung und PHP-Tests (1207) grün.

## Level-10-Welle 11

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 904 | 600 |
| Baseline-Muster | 761 | 533 |

Drei Pakete mit je 12 Dateien (Inputs/Helpers/Reports/Middleware, Controller/
Resources/Modelle/Migrationen, Actions/Services/Migrationen). Guards für
Request-/JSON-/API-Werte, getippte Closure-Parameter, `self::`-Konstanten,
`setAttribute()` statt Magic-Property. 228 obsolete PHPStan-Muster entfernt;
4 Restbefunde (CreateComment, Migration) zentral korrigiert.
PHPDoc-Baseline auf 1024 Einträge aktualisiert. PHPStan Level 10,
PHPDoc-Prüfung und PHP-Tests (1207) grün.

## Level-10-Welle 12

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 600 | 361 |
| Baseline-Muster | 533 | 343 |

Drei Pakete mit je 15 Dateien. Guards für `mixed` aus Request/`config()`/
`json_decode()`, `@var`-Shapes für validierte Daten und DB-Zeilen,
`self::`-Konstanten, `method_exists`-Guards für dynamische Actions,
`Blueprint`-Hinweise in Migrationen. 190 obsolete PHPStan-Muster entfernt.
PHPDoc-Baseline auf 1024 Einträge aktualisiert. PHPStan Level 10,
PHPDoc-Prüfung und PHP-Tests (1207) grün.

## Level-10-Welle 13

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 361 | 186 |
| Baseline-Muster | 343 | 178 |

Drei Pakete mit je 20 Dateien (Actions/Controller/Rules/Seating/Migrationen,
Controller/Requests/Resources/Parser/Migrationen, Actions/Modelle/Migrationen).
Shape-Typen für validierte Daten, `Blueprint`-Closure-Parameter,
`is_string`/`is_array`-Guards, `toArray()` → `all()`. 165 obsolete
PHPStan-Muster entfernt. PHPDoc-Baseline auf 1024 Einträge aktualisiert.
PHPStan Level 10, PHPDoc-Prüfung und PHP-Tests (1207) grün.

## Level-10-Welle 14

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 186 | 45 |
| Baseline-Muster | 178 | 45 |

Drei Pakete mit je 30 Dateien. Shape-/`@var`-Typen für validierte Daten und
Konfigurationswerte, Typverengung mit `is_array`/`is_string`/`is_scalar`/
`instanceof`, `self::`-Konstanten, `Blueprint`-Parameter. 133 obsolete
PHPStan-Muster entfernt. PHPDoc-Baseline auf 1024 Einträge aktualisiert.
PHPStan Level 10, PHPDoc-Prüfung und PHP-Tests (1207) grün.

## Level-10-Welle 15 — Baseline auf 0

| Messwert | Vorher | Nachher |
|---|---:|---:|
| Rohbefunde ohne Baseline | 45 | 0 |
| Baseline-Muster | 45 | 0 |

Drei Pakete mit je 15 Einzelbefunden in 15 Dateien. `is_string`/`is_scalar`/
`is_array`-Guards, `instanceof`-Prüfungen, `@var`-Typen und `self::`-Konstanten.
Ein Restbefund in `AdminModule::modules()` (Template-Type von `collect()`)
zentral korrigiert. Baseline ist jetzt leer (`ignoreErrors: []`).
PHPDoc-Baseline auf 1024 Einträge aktualisiert. PHPStan Level 10,
PHPDoc-Prüfung und vollständige PHP-Tests (1207) grün.

Damit ist das PHPStan-Ratchet vollständig: Level 6, Level 8 und Level 10
jeweils von einer frischen Baseline auf 0 Rohbefunde abgebaut.
