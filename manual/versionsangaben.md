[//]: # (TOC: 16. Versionsangaben)

# Versionsangaben

| Angabe | Wert |
|---|---|
| Handbuch | Pfarrplaner Benutzerhandbuch |
| Programmversion | 2026.11.0 |
| Umgebung | local |
| Build-Datum der Anwendung | Montag, 18. Mai 2026 13:35 |
| Handbuch erstellt am | 18.05.2026 13:39 |
| Git-Branch | main |
| Git-Stand | 04fa8f96 |
| Lizenz | GNU General Public License, Version 3.0 oder später |
| Projekt | Pfarrplaner |
| Autor und Copyright | Christoph Fischer, https://christoph-fischer.org |

## Was ist neu?

Die wichtigsten Änderungen der letzten Versionen stehen im Änderungsprotokoll. Für die tägliche Arbeit sind vor allem neue oder geänderte Schaltflächen, neue Berichte, neue Eingabefelder und geänderte Abläufe wichtig.

## [2026.11.0](https://codeberg.org/pfarr.tools/pfarrplaner/compare/v2026.10.2...v2026.11.0) (2026-05-18)


### Features

* Beschreibung und Quelle für Vorlagen im Import-Dialog anzeigen ([0b5beb3](https://codeberg.org/pfarr.tools/pfarrplaner/commit/0b5beb391b028fcbe2ecefecfa87e2606c9d575e))
* Duplikate-Assistent überarbeitet und Benutzerverwaltung verbessert ([d5c099b](https://codeberg.org/pfarr.tools/pfarrplaner/commit/d5c099b9f55369ce8259d88078781587c70f1f72))
* **ui:** Formularstil und Kalenderoberflaechen ueberarbeitet ([5e902bd](https://codeberg.org/pfarr.tools/pfarrplaner/commit/5e902bdb39fe83831c2734d74165938286facee7))
* Vue3-Migration abgeschlossen ([ee24242](https://codeberg.org/pfarr.tools/pfarrplaner/commit/ee2424281f39be7e18d8bd42ef255049a147c1a4))


### Bug Fixes

* AdminHomeScreenTab lädt nicht ([f572acb](https://codeberg.org/pfarr.tools/pfarrplaner/commit/f572acbfcc795058ad80de31cdf0a796874be48e))
* Benutzer werden als "undefined" angezeigt. ([68780d4](https://codeberg.org/pfarr.tools/pfarrplaner/commit/68780d4ccf045b22db61e65cf4d563c5be1dfbef))
* Dienste mit Doppelpunkt in Liturgieausgaben berücksichtigen. ([408427e](https://codeberg.org/pfarr.tools/pfarrplaner/commit/408427e731368dcff145042de4e0650cb1207300)), closes [#442](https://codeberg.org/pfarr.tools/pfarrplaner/issues/442)
* Editor hat kein Styling für Zitate. ([9de2d20](https://codeberg.org/pfarr.tools/pfarrplaner/commit/9de2d2093edb20b71f8bbd5514917402fa43db44))
* Ereignislisten im Präsentationsexport auf mehrere Folien verteilt. ([9cbab2a](https://codeberg.org/pfarr.tools/pfarrplaner/commit/9cbab2a386ac193efca0303dcedaac9443ec71d6))
* Fehler beim Speichern von Personeneinträgen ([70f1315](https://codeberg.org/pfarr.tools/pfarrplaner/commit/70f131545bc389af7273a54112ded2d3de7f519e))
* Fehler beim Übernehmen einer Person aus anderer Kirchengemeinde im PeopleSelect behoben ([1ba2eee](https://codeberg.org/pfarr.tools/pfarrplaner/commit/1ba2eee45bb360d7b52e8b712d7270d1cf80605f))
* Fehler im PeopleSelect ([e170ee1](https://codeberg.org/pfarr.tools/pfarrplaner/commit/e170ee15797fe2d8a2a391d3a313a394426c7c53))
* Fehlerhafte mergeInto()-Methode beim Zusammenführen von Personen korrigiert ([22fb4ab](https://codeberg.org/pfarr.tools/pfarrplaner/commit/22fb4abb2f8a73cd22a27cad89439d1a76875f0b))
* Fehlgeschlagene Unit-Tests für AbstractModel-Subklassen korrigiert. ([b0e1887](https://codeberg.org/pfarr.tools/pfarrplaner/commit/b0e188759723654c41c9e764b7300c705bbc0af8))
* Opferplan enthält falsche Gottesdienste ([fb57a4d](https://codeberg.org/pfarr.tools/pfarrplaner/commit/fb57a4d92cb7e9ed03723e6ff58a6ea0f2b0a98a))
* PHPUnit-Deprecation für [@test](https://codeberg.org/test)-Annotationen in Docblocks behoben. ([4f6dbd2](https://codeberg.org/pfarr.tools/pfarrplaner/commit/4f6dbd202b792af4fb3f83910a6bfc13f9091b65))
* Pinne webpack v5.105.4 ([94ae847](https://codeberg.org/pfarr.tools/pfarrplaner/commit/94ae847ba00264782d08eeddbe5b71d3d59203e9))
* Profil kann nicht gespeichert werden. ([5943aaa](https://codeberg.org/pfarr.tools/pfarrplaner/commit/5943aaab533ea86286078ca105e258e53d2b5ceb))
* RFC-konformes Leerzeichen in ICAL-Fortsetzungszeilen erhalten. ([f6464d3](https://codeberg.org/pfarr.tools/pfarrplaner/commit/f6464d3223a08d793445eba3b99793fd91229885)), closes [#427](https://codeberg.org/pfarr.tools/pfarrplaner/issues/427)
* ServiceEditor lässt sich nicht öffnen ([3726cd1](https://codeberg.org/pfarr.tools/pfarrplaner/commit/3726cd1ea9c71aa9baf8a3bfcca41ec5aecb9580))
* Sonderzeichen im Präsentationsexport korrigiert. ([c881164](https://codeberg.org/pfarr.tools/pfarrplaner/commit/c8811648ac0dd4f797a88188c291206e9acfa418))
* Sonderzeichen in Word-Exporten erhalten. ([b782fc9](https://codeberg.org/pfarr.tools/pfarrplaner/commit/b782fc9340750807da3b00f0607d6eb3772ac638))

### [2026.11.1](https://codeberg.org/pfarr.tools/pfarrplaner/compare/v2026.11.0...v2026.11.1) (2026-05-04)


### Bug Fixes

* AdminHomeScreenTab lädt nicht ([f572acb](https://codeberg.org/pfarr.tools/pfarrplaner/commit/f572acbfcc795058ad80de31cdf0a796874be48e))
* Benutzer werden als "undefined" angezeigt. ([68780d4](https://codeberg.org/pfarr.tools/pfarrplaner/commit/68780d4ccf045b22db61e65cf4d563c5be1dfbef))
* Fehler beim Speichern von Personeneinträgen ([70f1315](https://codeberg.org/pfarr.tools/pfarrplaner/commit/70f131545bc389af7273a54112ded2d3de7f519e))
* Fehler im PeopleSelect ([e170ee1](https://codeberg.org/pfarr.tools/pfarrplaner/commit/e170ee15797fe2d8a2a391d3a313a394426c7c53))
* Pinne webpack v5.105.4 ([94ae847](https://codeberg.org/pfarr.tools/pfarrplaner/commit/94ae847ba00264782d08eeddbe5b71d3d59203e9))

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

Pfarrplaner ist freie Software. Sie dürfen das Programm unter den Bedingungen der GNU General Public License Version 3 oder später weitergeben und verändern.

Dieses Handbuch beschreibt die Bedienung für Benutzerinnen und Benutzer. Installation, Betrieb und technische Wartung sind nicht Teil dieses Handbuchs.
