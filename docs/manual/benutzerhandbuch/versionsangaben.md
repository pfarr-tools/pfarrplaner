[//]: # (TOC: 16. Versionsangaben)

# Versionsangaben

| Angabe | Wert |
|---|---|
| Handbuch | Pfarrplaner Benutzerhandbuch |
| Programmversion | 2026.12.0 |
| Umgebung | local |
| Build-Datum der Anwendung | Mittwoch, 27. Mai 2026 09:23 |
| Handbuch erstellt am | 27.05.2026 09:27 |
| Git-Branch | main |
| Git-Stand | 635465d5 |
| Lizenz | GNU General Public License, Version 3.0 oder später |
| Projekt | Pfarrplaner |
| Autor und Copyright | Christoph Fischer, https://christoph-fischer.org |

## Was ist neu?

Die wichtigsten Änderungen der letzten Versionen stehen im Änderungsprotokoll. Für die tägliche Arbeit sind vor allem neue oder geänderte Schaltflächen, neue Berichte, neue Eingabefelder und geänderte Abläufe wichtig.

## [2026.12.0](https://codeberg.org/pfarr.tools/pfarrplaner/compare/v2026.11.6...v2026.12.0) (2026-05-27)


### Features

* Dusk-Testausgabe für lokale Läufe verbessert ([daafcc3](https://codeberg.org/pfarr.tools/pfarrplaner/commit/daafcc3b61a5342da952ca475100dd0efc5f3a30))
* Handbücher, Abwesenheiten und Sicherheitsaudit überarbeiten ([66e8984](https://codeberg.org/pfarr.tools/pfarrplaner/commit/66e89844c69787aa1cd628e674f5cfa03f47340a))
* Hinweis zur Umstellung der Opferzähler-Felder ([deafc4d](https://codeberg.org/pfarr.tools/pfarrplaner/commit/deafc4d095b7ea29ac0b63b5112b4438fc2c6f6f))
* **installer:** Erstinstallation für Pfarrplaner ([d894a1a](https://codeberg.org/pfarr.tools/pfarrplaner/commit/d894a1ab2ad4cede709028281450ef25c46ad06f))
* Interstitial-Hinweise nach der Anmeldung ([2c73a38](https://codeberg.org/pfarr.tools/pfarrplaner/commit/2c73a38acff027ace9a80a56734d871548bd3300))
* Leseansicht für Predigten verbessert ([ebc7fc6](https://codeberg.org/pfarr.tools/pfarrplaner/commit/ebc7fc69a6b1de73962748b7096b037bb832b3d3))
* **liturgie:** Liturgie-Editor einheitlich neu strukturieren ([13ad950](https://codeberg.org/pfarr.tools/pfarrplaner/commit/13ad950b6d5e53c8a28bd204a3af6966aa7296a8))
* **liturgie:** Sheet-Dialoge ohne Download-Submit unterstützen ([c2f90ff](https://codeberg.org/pfarr.tools/pfarrplaner/commit/c2f90ff26e14901057a2a6f9fe4cf8675da14ef9))
* Öffentliche Infoseite und Pflegehinweise aktualisiert ([3261687](https://codeberg.org/pfarr.tools/pfarrplaner/commit/3261687ede0a0a3900959f2fdf78e53ca7c0b6d9))
* Ortsfilter für dienstbezogene Berichte ([cdc3faf](https://codeberg.org/pfarr.tools/pfarrplaner/commit/cdc3faf94d2b91f207e811e5c97da88c093898dc))
* **reports:** Neü Excel-Tabelle für Gottesdienste und Veranstaltungen ([eca800b](https://codeberg.org/pfarr.tools/pfarrplaner/commit/eca800b78b4569641c247c21ce3d47b9ad1f351a))
* Sitzungsmodelle entfernt ([5cb003d](https://codeberg.org/pfarr.tools/pfarrplaner/commit/5cb003d7e036064cbba26dd503d9e3df72e0a0de))
* Überflüssige Listenfolien in PowerPoint-Werbung ausblenden ([1c3f8a1](https://codeberg.org/pfarr.tools/pfarrplaner/commit/1c3f8a10e0ce297326d22c62c26d5d57cb113f49))


### Bug Fixes

* **absence:** Entwurf erst beim Bearbeiten speichern. ([9b935a2](https://codeberg.org/pfarr.tools/pfarrplaner/commit/9b935a22359769f31285afee3fe0d5edb24ff17a))
* **api:** API-Endpunkte standardmäßig absichern. ([b3b08f6](https://codeberg.org/pfarr.tools/pfarrplaner/commit/b3b08f68adb5ff229fbce6bc78651f558bfac161))
* Datumslogik und Aktionen im Urlaubseditor korrigiert. ([14c27bb](https://codeberg.org/pfarr.tools/pfarrplaner/commit/14c27bb403554636056750b4334cd2c8d6470e9b))
* **dusk:** Browser-Tests stabilisiert. ([2195e04](https://codeberg.org/pfarr.tools/pfarrplaner/commit/2195e04b4a4b98a1e2732bf064fe27e1f4376bc0))
* Fehlende Navigationsleiste im Predigteditor nach dem Speichern beheben. ([e4496a9](https://codeberg.org/pfarr.tools/pfarrplaner/commit/e4496a9a2e953c9f9ä7fc0d6e1a58589b8e9537))
* Importqüllen der Liturgie-API korrekt auflösen. ([27dca58](https://codeberg.org/pfarr.tools/pfarrplaner/commit/27dca585814a92eb65883494e9e017bafä25da4))
* **report:** Opferzwecke in Ausgaben mit Standardwerten verwenden. ([c9bb3e0](https://codeberg.org/pfarr.tools/pfarrplaner/commit/c9bb3e0d87222865a4eead962bfe1f076b99e503))
* Später bei Interstitials an die Anmeldung binden. ([2d733bc](https://codeberg.org/pfarr.tools/pfarrplaner/commit/2d733bcc3bb5687a1521265e604065d64f43d2dc))
* Spendenlinks und Hinweise korrigiert. ([a341593](https://codeberg.org/pfarr.tools/pfarrplaner/commit/a34159350f7421bf26d4afa31c6a29fde75831e6))
* **tests:** Fehlgeschlagene Testläufe stabilisiert. ([3af3983](https://codeberg.org/pfarr.tools/pfarrplaner/commit/3af398369f3e5f06468deba5bf0ad602a2426858))
* Titel im Ablaufplan fehlt beim Bearbeiten von Liedelementen ([4d9eefe](https://codeberg.org/pfarr.tools/pfarrplaner/commit/4d9eefe279156f902f15d2a5a6190c78a935a716))
* Zeilen- und Absatzumbrüche in Predigtzitaten exportieren. ([05c2c6b](https://codeberg.org/pfarr.tools/pfarrplaner/commit/05c2c6bf0693bb059370be6190edde8f0d0f4e31))

### [2026.11.6](https://codeberg.org/pfarr.tools/pfarrplaner/compare/v2026.11.5...v2026.11.6) (2026-05-20)


### Bug Fixes

* Datumsformat im Beerdigungseditor strikt deutsch parsen. ([51c446f](https://codeberg.org/pfarr.tools/pfarrplaner/commit/51c446f5d4f319118d7c41a931a2d9fb8266b799))

### [2026.11.5](https://codeberg.org/pfarr.tools/pfarrplaner/compare/v2026.11.4...v2026.11.5) (2026-05-19)


### Bug Fixes

* **absences:** Horizontales Scrollen im Urlaubsplan auf Mobilgeräten. ([3f078fe](https://codeberg.org/pfarr.tools/pfarrplaner/commit/3f078fe3004b84ecf9d2bcd4e8feb8084546099f))
* Zeitzonenfehler beim Speichern von Gottesdienstzeiten beheben. ([cb5d392](https://codeberg.org/pfarr.tools/pfarrplaner/commit/cb5d392fd62d74747fbe65bc1a975ee7757c7a61))

### [2026.11.4](https://codeberg.org/pfarr.tools/pfarrplaner/compare/v2026.11.3...v2026.11.4) (2026-05-19)


### Bug Fixes

* Leeres Arbeitsverzeichnis für PDF-Downloads abgefangen. ([9cf9984](https://codeberg.org/pfarr.tools/pfarrplaner/commit/9cf9984be764b1a2b2d705ab91bb62fc26f2cb1a))
* **liturgie:** Downloads im Liturgie-Dropdown wieder öffnen. ([ec42c57](https://codeberg.org/pfarr.tools/pfarrplaner/commit/ec42c57b0eace121e10b92cab9f991815ed636a8))
* Powerpoint-Präsentation wird nicht erstellt ([8201ce7](https://codeberg.org/pfarr.tools/pfarrplaner/commit/8201ce7b8989189f7d9befc16393c177e0eb26ca))

### [2026.11.3](https://codeberg.org/pfarr.tools/pfarrplaner/compare/v2026.11.2...v2026.11.3) (2026-05-19)


### Bug Fixes

* Klonfehler in Tauf- und Traüditor behoben. ([04e7962](https://codeberg.org/pfarr.tools/pfarrplaner/commit/04e7962dd2349d26fb2b32b98cb8d336d3c5883a))
* Quickpicker-Datum auf der Startseite korrekt initialisieren. ([79921ec](https://codeberg.org/pfarr.tools/pfarrplaner/commit/79921eccfd5135ffac7d6ef160b151bb3469d2d6))

### [2026.11.1](https://codeberg.org/pfarr.tools/pfarrplaner/compare/v2026.11.0...v2026.11.1) (2026-05-18)

### [2026.11.2](https://codeberg.org/pfarr.tools/pfarrplaner/compare/v2026.11.0...v2026.11.2) (2026-05-18)


### Bug Fixes

* Backup speichert unnötige Dateien ([9294f21](https://codeberg.org/pfarr.tools/pfarrplaner/commit/9294f216c0851ca9c8174cbd18b20bab92cdbc73))
* Datumsvalidierung bei Taufen und Trauungen repariert. ([88b3984](https://codeberg.org/pfarr.tools/pfarrplaner/commit/88b3984dd0cd6f6ce0b37d50c4d199d7b631622b))
* Datumsvalidierung im Beerdigungseditor repariert. ([9edea86](https://codeberg.org/pfarr.tools/pfarrplaner/commit/9edea86dc40320a3301b2b49b0a6a2af96bdb4e5))
* Formularwerte aus Multiselect-Feldern korrekt übermitteln. ([5f04ef7](https://codeberg.org/pfarr.tools/pfarrplaner/commit/5f04ef7a9e390181d39c8a2f6a1a1dbea120a47e))

### [2026.11.1](https://codeberg.org/pfarr.tools/pfarrplaner/compare/v2026.11.0...v2026.11.1) (2026-05-18)


### Bug Fixes

* Formularwerte aus Multiselect-Feldern korrekt übermitteln. ([5f04ef7](https://codeberg.org/pfarr.tools/pfarrplaner/commit/5f04ef7a9e390181d39c8a2f6a1a1dbea120a47e))

## [2026.11.0](https://codeberg.org/pfarr.tools/pfarrplaner/compare/v2026.10.2...v2026.11.0) (2026-05-18)


### Features

* Beschreibung und Qülle für Vorlagen im Import-Dialog anzeigen ([0b5beb3](https://codeberg.org/pfarr.tools/pfarrplaner/commit/0b5beb391b028fcbe2ecefecfa87e2606c9d575e))
* Duplikate-Assistent überarbeitet und Benutzerverwaltung verbessert ([d5c099b](https://codeberg.org/pfarr.tools/pfarrplaner/commit/d5c099b9f55369ce8259d88078781587c70f1f72))
* **ui:** Formularstil und Kalenderoberflächen überarbeitet ([5e902bd](https://codeberg.org/pfarr.tools/pfarrplaner/commit/5e902bdb39fe83831c2734d74165938286facee7))
* Vü3-Migration abgeschlossen ([ee24242](https://codeberg.org/pfarr.tools/pfarrplaner/commit/ee2424281f39be7e18d8bd42ef255049a147c1a4))


### Bug Fixes

* AdminHomeScreenTab lädt nicht ([f572acb](https://codeberg.org/pfarr.tools/pfarrplaner/commit/f572acbfcc795058ad80de31cdf0a796874be48e))
* Benutzer werden als "undefined" angezeigt. ([68780d4](https://codeberg.org/pfarr.tools/pfarrplaner/commit/68780d4ccf045b22db61e65cf4d563c5be1dfbef))
* Dienste mit Doppelpunkt in Liturgieausgaben berücksichtigen. ([408427e](https://codeberg.org/pfarr.tools/pfarrplaner/commit/408427e731368dcff145042de4e0650cb1207300)), closes [#442](https://codeberg.org/pfarr.tools/pfarrplaner/issüs/442)
* Editor hat kein Styling für Zitate. ([9de2d20](https://codeberg.org/pfarr.tools/pfarrplaner/commit/9de2d2093edb20b71f8bbd5514917402fa43db44))
* Ereignislisten im Präsentationsexport auf mehrere Folien verteilt. ([9cbab2a](https://codeberg.org/pfarr.tools/pfarrplaner/commit/9cbab2a386ac193efca0303dcedaac9443ec71d6))
* Fehler beim Speichern von Personeneinträgen ([70f1315](https://codeberg.org/pfarr.tools/pfarrplaner/commit/70f131545bc389af7273a54112ded2d3de7f519e))
* Fehler beim Übernehmen einer Person aus anderer Kirchengemeinde im PeopleSelect behoben ([1ba2eee](https://codeberg.org/pfarr.tools/pfarrplaner/commit/1ba2eee45bb360d7b52e8b712d7270d1cf80605f))
* Fehler im PeopleSelect ([e170ee1](https://codeberg.org/pfarr.tools/pfarrplaner/commit/e170ee15797fe2d8a2a391d3a313a394426c7c53))
* Fehlerhafte mergeInto()-Methode beim Zusammenführen von Personen korrigiert ([22fb4ab](https://codeberg.org/pfarr.tools/pfarrplaner/commit/22fb4abb2f8a73cd22a27cad89439d1a76875f0b))
* Fehlgeschlagene Unit-Tests für AbstractModel-Subklassen korrigiert. ([b0e1887](https://codeberg.org/pfarr.tools/pfarrplaner/commit/b0e188759723654c41c9e764b7300c705bbc0af8))
* Opferplan enthält falsche Gottesdienste ([fb57a4d](https://codeberg.org/pfarr.tools/pfarrplaner/commit/fb57a4d92cb7e9ed03723e6ff58a6ea0f2b0a98a))
* PHPUnit-Deprecation für [@test](https://codeberg.org/test)-Annotationen in Docblocks behoben. ([4f6dbd2](https://codeberg.org/pfarr.tools/pfarrplaner/commit/4f6dbd202b792af4fb3f83910a6bfc13f9091b65))
* Pinne webpack v5.105.4 ([94ä847](https://codeberg.org/pfarr.tools/pfarrplaner/commit/94ä847ba00264782d08eeddbe5b71d3d59203e9))
* Profil kann nicht gespeichert werden. ([5943aaa](https://codeberg.org/pfarr.tools/pfarrplaner/commit/5943aaab533ea86286078ca105e258e53d2b5ceb))
* RFC-konformes Leerzeichen in ICAL-Fortsetzungszeilen erhalten. ([f6464d3](https://codeberg.org/pfarr.tools/pfarrplaner/commit/f6464d3223a08d793445eba3b99793fd91229885)), closes [#427](https://codeberg.org/pfarr.tools/pfarrplaner/issüs/427)
* ServiceEditor lässt sich nicht öffnen ([3726cd1](https://codeberg.org/pfarr.tools/pfarrplaner/commit/3726cd1ea9c71aa9baf8a3bfcca41ec5äcb9580))
* Sonderzeichen im Präsentationsexport korrigiert. ([c881164](https://codeberg.org/pfarr.tools/pfarrplaner/commit/c8811648ac0dd4f797a88188c291206e9acfa418))
* Sonderzeichen in Word-Exporten erhalten. ([b782fc9](https://codeberg.org/pfarr.tools/pfarrplaner/commit/b782fc9340750807da3b00f0607d6eb3772ac638))

### [2026.11.1](https://codeberg.org/pfarr.tools/pfarrplaner/compare/v2026.11.0...v2026.11.1) (2026-05-04)


### Bug Fixes

* AdminHomeScreenTab lädt nicht ([f572acb](https://codeberg.org/pfarr.tools/pfarrplaner/commit/f572acbfcc795058ad80de31cdf0a796874be48e))
* Benutzer werden als "undefined" angezeigt. ([68780d4](https://codeberg.org/pfarr.tools/pfarrplaner/commit/68780d4ccf045b22db61e65cf4d563c5be1dfbef))
* Fehler beim Speichern von Personeneinträgen ([70f1315](https://codeberg.org/pfarr.tools/pfarrplaner/commit/70f131545bc389af7273a54112ded2d3de7f519e))
* Fehler im PeopleSelect ([e170ee1](https://codeberg.org/pfarr.tools/pfarrplaner/commit/e170ee15797fe2d8a2a391d3a313a394426c7c53))
* Pinne webpack v5.105.4 ([94ä847](https://codeberg.org/pfarr.tools/pfarrplaner/commit/94ä847ba00264782d08eeddbe5b71d3d59203e9))

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

Pfarrplaner ist freie Software. Sie dürfen das Programm unter den Bedingungen der GNU General Public License Version 3 oder später weitergeben und verändern.

Dieses Handbuch beschreibt die Bedienung für Benutzerinnen und Benutzer. Installation, Betrieb und technische Wartung sind nicht Teil dieses Handbuchs.
