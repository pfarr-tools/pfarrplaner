[//]: # (TOC: 16. Versionsangaben)

# Versionsangaben

| Angabe | Wert |
|---|---|
| Handbuch | Pfarrplaner Benutzerhandbuch |
| Programmversion | 2026.10.2 |
| Umgebung | local |
| Build-Datum der Anwendung | Freitag, 08. Mai 2026 19:26 |
| Handbuch erstellt am | 08.05.2026 19:27 |
| Git-Branch | vue3-migration |
| Git-Stand | ab867d6e |
| Lizenz | GNU General Public License, Version 3.0 oder später |
| Projekt | Pfarrplaner |
| Autor und Copyright | Christoph Fischer, https://christoph-fischer.org |

## Was ist neu?

Die wichtigsten Änderungen der letzten Versionen stehen im Änderungsprotokoll. Für die tägliche Arbeit sind vor allem neue oder geänderte Schaltflächen, neue Berichte, neue Eingabefelder und geänderte Abläufe wichtig.

## [2026.11.0](https://codeberg.org/pfarr.tools/pfarrplaner/compare/v2026.10.2...v2026.11.0) (2026-04-25)


### Features

* Duplikate-Assistent überarbeitet und Benutzerverwaltung verbessert ([d5c099b](https://codeberg.org/pfarr.tools/pfarrplaner/commit/d5c099b9f55369ce8259d88078781587c70f1f72))


### Bug Fixes

* Fehler beim Übernehmen einer Person aus anderer Kirchengemeinde im PeopleSelect behoben ([1ba2eee](https://codeberg.org/pfarr.tools/pfarrplaner/commit/1ba2eee45bb360d7b52e8b712d7270d1cf80605f))
* Fehlerhafte mergeInto()-Methode beim Zusammenführen von Personen korrigiert ([22fb4ab](https://codeberg.org/pfarr.tools/pfarrplaner/commit/22fb4abb2f8a73cd22a27cad89439d1a76875f0b))
* Fehlgeschlagene Unit-Tests für AbstractModel-Subklassen korrigiert. ([b0e1887](https://codeberg.org/pfarr.tools/pfarrplaner/commit/b0e188759723654c41c9e764b7300c705bbc0af8))
* PHPUnit-Deprecation für [@test](https://codeberg.org/test)-Annotationen in Docblocks behoben. ([4f6dbd2](https://codeberg.org/pfarr.tools/pfarrplaner/commit/4f6dbd202b792af4fb3f83910a6bfc13f9091b65))

### [2026.10.2](https://codeberg.org/pfarr.tools/pfarrplaner/compare/v2026.10.1...v2026.10.2) (2026-04-19)


### Bug Fixes

* Falsche Einrückungen beim Zitaten in Word-Dokumenten. ([d74c18e](https://codeberg.org/pfarr.tools/pfarrplaner/commit/d74c18e7e70e8a616f48ad5a88a91ef3f5e35087))
* Tippfehler ([319fe56](https://codeberg.org/pfarr.tools/pfarrplaner/commit/319fe56fa9148498c29405c6b791a86da595c9d4))

### [2026.10.1](https://codeberg.org/pfarr.tools/pfarrplaner/compare/v2026.10.0...v2026.10.1) (2026-04-18)


### Bug Fixes

* Fehlende Dateien ([29e4e99](https://codeberg.org/pfarr.tools/pfarrplaner/commit/29e4e99cca5bbc2494d3c0fe0286e7f2493a461d))

## [2026.10.0](https://codeberg.org/pfarr.tools/pfarrplaner/compare/v2026.9.2...v2026.10.0) (2026-04-18)


### Features

* Werbeschleifen funktionieren in Powerpoint und LibreOffice ([1b514d6](https://codeberg.org/pfarr.tools/pfarrplaner/commit/1b514d6a605df369a25788f07e7eee525be8a281))


### Bug Fixes

* Zeitspalte zu schmal auf Folien mit Veranstaltungslisten ([dffaa78](https://codeberg.org/pfarr.tools/pfarrplaner/commit/dffaa7845a0d6629ece8441ee6aa4ec82a335f3e))

### [2026.9.2](https://codeberg.org/pfarr.tools/pfarrplaner/compare/v2026.9.1...v2026.9.2) (2026-02-05)


### Bug Fixes

* Fehlendes Vorschaubild für Format "Gäufelden" ([1ff1cfa](https://codeberg.org/pfarr.tools/pfarrplaner/commit/1ff1cfa2edeb6d35b8702fd0ba92369fb1a80b77))
* Gemeindebrief enthält nicht-gottesdienstliche Veranstaltungen ([426f75f](https://codeberg.org/pfarr.tools/pfarrplaner/commit/426f75f84f67dac6511cc02544b4625386cdb8c5))

### [2026.9.1](https://codeberg.org/pfarr.tools/pfarrplaner/compare/v2026.9.0...v2026.9.1) (2026-02-05)


### Bug Fixes

* Inertia sendet falsches Token bei Form POSTs. ([cf6238c](https://codeberg.org/pfarr.tools/pfarrplaner/commit/cf6238cf572bc4c876a6e913bafbcc0ba0b67a83))

## [2026.9.0](https://codeberg.org/pfarr.tools/pfarrplaner/compare/v2026.8.2...v2026.9.0) (2026-02-05)


### Features

* Bericht mit Zahlen zur EKD-Statistik ([8382049](https://codeberg.org/pfarr.tools/pfarrplaner/commit/8382049b91d0a2599d13fee24429e988aa04bc6b))


### Bug Fixes

* Einige Formulare führen zu Fehler 419 ([85c52a7](https://codeberg.org/pfarr.tools/pfarrplaner/commit/85c52a7750489425e905544277673bebc15c24e8))
* Fehlerhafte Kalenderanzeige, wenn kein Bestattungstyp ausgewählt ist. ([695c7ac](https://codeberg.org/pfarr.tools/pfarrplaner/commit/695c7ac60e1cb1a68ff0922c8d5b223f562e6ea9))
* ServiceTableReport enthält nicht-gottesdienstliche Veranstaltungen ([5f34917](https://codeberg.org/pfarr.tools/pfarrplaner/commit/5f34917b06a44f86455983f5ef13d96c5596fc68))
* Versteckte Beerdigungen sind im Kalender unlesbar. ([decb48d](https://codeberg.org/pfarr.tools/pfarrplaner/commit/decb48da3b1d3b7e4e090852acab3cdbf890909c))
* Weitere Fehler 419 ([e616f30](https://codeberg.org/pfarr.tools/pfarrplaner/commit/e616f30d97f6e76653e21276c7c8b1270f83aab2))

### [2026.8.2](https://codeberg.org/pfarr.tools/pfarrplaner/compare/v2026.8.1...v2026.8.2) (2026-01-31)


### Bug Fixes

* Häufiger Fehler 419. ([8989cfd](https://codeberg.org/pfarr.tools/pfarrplaner/commit/8989cfdc46b556e2880cd372a7e28cbb4d5619d7))
* Kalenderansicht scheitert, wenn Urlaube mit Vertretungen angezeigt werden sollen. ([74422c6](https://codeberg.org/pfarr.tools/pfarrplaner/commit/74422c6c6c13c6d6b2c81a3143b1bb5aee676b27))
* Materialsammlung ist nicht mehr verfügbar ([498fe83](https://codeberg.org/pfarr.tools/pfarrplaner/commit/498fe838a8df2afaef611836bd2da4ac0db7a456))

### [2026.8.1](https://codeberg.org/pfarr.tools/pfarrplaner/compare/v2026.8.0...v2026.8.1) (2026-01-28)


### Bug Fixes

* Fehler beim Erstellen von PPT ([084f846](https://codeberg.org/pfarr.tools/pfarrplaner/commit/084f846540093f9e4a7b786b3e857a9df66c9536))
* Fehler beim Erstellen von PPT ([8e027ae](https://codeberg.org/pfarr.tools/pfarrplaner/commit/8e027aef180b0a3f64b9f942a769dde231228ee9))
* Poolmaster können nicht angelegt werden ([277c807](https://codeberg.org/pfarr.tools/pfarrplaner/commit/277c80736fe29ac44c09d30801771e33d0d8d4a1))
* SingleMinistryReport sendet Exceldatei direkt binär in den Browser ([dae87a4](https://codeberg.org/pfarr.tools/pfarrplaner/commit/dae87a43085a7ba50b920b29da64297c57fcc5cf))

Pfarrplaner ist freie Software. Sie dürfen das Programm unter den Bedingungen der GNU General Public License Version 3 oder später weitergeben und verändern.

Dieses Handbuch beschreibt die Bedienung für Benutzerinnen und Benutzer. Installation, Betrieb und technische Wartung sind nicht Teil dieses Handbuchs.
