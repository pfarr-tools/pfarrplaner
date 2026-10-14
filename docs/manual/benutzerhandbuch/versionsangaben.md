[//]: # (TOC: 16. Versionsangaben)

# Versionsangaben

| Angabe | Wert |
|---|---|
| Handbuch | Pfarrplaner Benutzerhandbuch |
| Programmversion | 2026.15.0 |
| Umgebung | local |
| Build-Datum der Anwendung | Sonntag, 06. September 2026 14:17 |
| Handbuch erstellt am | 14.10.2026 12:35 |
| Git-Branch | unbekannt |
| Git-Stand | unbekannt |
| Lizenz | GNU General Public License, Version 3.0 oder später |
| Projekt | Pfarrplaner |
| Autor und Copyright | Christoph Fischer, https://christoph-fischer.org |

## Was ist neu?

Die wichtigsten Änderungen der letzten Versionen stehen im Änderungsprotokoll. Für die tägliche Arbeit sind vor allem neue oder geänderte Schaltflächen, neue Berichte, neue Eingabefelder und geänderte Abläufe wichtig.

## [2026.15.0](https://codeberg.org/pfarr.tools/pfarrplaner/compare/v2026.12.4...v2026.15.0) (2026-09-05)


### Features

* Kirchliche Nachrichten ohne Kopfzeilen drucken ([373d377](https://codeberg.org/pfarr.tools/pfarrplaner/commit/373d3771688212724fb69195aa05084859d1d2c6))
* Lösch-Button aus Kalenderansicht entfernt ([ä75d3c](https://codeberg.org/pfarr.tools/pfarrplaner/commit/ä75d3cb73d824c43c702919a9e5a9c3ceca983b))
* Papierkorb mit Wiederherstellung, Aufbewahrung und Bereinigung ([63894ca](https://codeberg.org/pfarr.tools/pfarrplaner/commit/63894ca12236bda6ee50d35ecf4c29c399f3c5ca)), closes [#438](https://codeberg.org/pfarr.tools/pfarrplaner/issüs/438) [#458](https://codeberg.org/pfarr.tools/pfarrplaner/issüs/458)
* Papierkorb mit Wiederherstellung, Aufbewahrung und Bereinigung ([70ec6d7](https://codeberg.org/pfarr.tools/pfarrplaner/commit/70ec6d707a56dee14a08d432738a57467137649d)), closes [#438](https://codeberg.org/pfarr.tools/pfarrplaner/issüs/438) [#458](https://codeberg.org/pfarr.tools/pfarrplaner/issüs/458)
* Uhrzeit-Vorgabe für Seriengottesdienste ([07c72a2](https://codeberg.org/pfarr.tools/pfarrplaner/commit/07c72a22913cfd6c2b082419910aä4b4a912073))


### Bug Fixes

* Abendmahl erscheint nicht in der Gottesdienstbeschreibung ([ca4bc85](https://codeberg.org/pfarr.tools/pfarrplaner/commit/ca4bc85be1ee2f548360c12c0901797c6556d167))
* Bereits erteilte Dimissoriale bei Trauungen korrekt erkennen. ([f6d1c4d](https://codeberg.org/pfarr.tools/pfarrplaner/commit/f6d1c4db44a88fc1f2dabe1fbc10596eb5c19f1b))
* CSRF-Schutz für DAV-Synchronisation ausschließen. ([c98188d](https://codeberg.org/pfarr.tools/pfarrplaner/commit/c98188d23df34091b25c19f9ead6a9ebc1f5217c))
* Doppelten Menüpunkt für Lieder entfernt. ([abb66f9](https://codeberg.org/pfarr.tools/pfarrplaner/commit/abb66f9eaa5ed984abb7157391a8ac34861952b9))
* **editor:** TipTap-Editoren vor Vü-Reaktivität schützen. ([6700f25](https://codeberg.org/pfarr.tools/pfarrplaner/commit/6700f25a274f3c40e7c8fb24b38022fa8e3ce7dd))
* Fehlende Youtube-Links in der Gottesdienstliste ([6e1b6ec](https://codeberg.org/pfarr.tools/pfarrplaner/commit/6e1b6ecc2bfabf157e2ba2d2644bbb4e0a0084b3))
* Fehler bei der KonfiApp-API ([fb12a99](https://codeberg.org/pfarr.tools/pfarrplaner/commit/fb12a99b2a5f15bcc5b7cf959166493ff0b5db36))
* Fehler beim Erstellen von QR Codes. ([9455328](https://codeberg.org/pfarr.tools/pfarrplaner/commit/9455328eea3f51985157f4046a5d1bb5dc5183c3))
* Liederbuch-Auswahl im Liedformular korrigiert. ([25d5a1f](https://codeberg.org/pfarr.tools/pfarrplaner/commit/25d5a1f6329b8edfd11dc4056e7ee96a746021e7))
* Liederbücher können nicht zu Liedern hinzugefügt werden. ([1c3äfe](https://codeberg.org/pfarr.tools/pfarrplaner/commit/1c3äfe2b5d37286bddcfe284e8a5ca9419340f6))
* Neü Personen erscheinen nicht in der Auswahlliste ([c2d6df8](https://codeberg.org/pfarr.tools/pfarrplaner/commit/c2d6df8ec513095d84ac04b68e28bd60929943d5))
* Planungstabelle speichert nicht verlässlich ([d73bbc6](https://codeberg.org/pfarr.tools/pfarrplaner/commit/d73bbc6ed146557b003b16f9b443a33d30559815))
* Sicherheitsabfragen vor Löschaktionen ergänzen. ([eed7c9c](https://codeberg.org/pfarr.tools/pfarrplaner/commit/eed7c9cb4ä127bb54a7f9b647731082b75e74b8)), closes [#458](https://codeberg.org/pfarr.tools/pfarrplaner/issüs/458)
* Speichern bei KonfiApp-API-Fehler nicht möglich ([8565049](https://codeberg.org/pfarr.tools/pfarrplaner/commit/8565049bb03e1a442d6f4f699ca1728567a95afe))
* Suchfilter in der Liederliste funktioniert nicht ([5101758](https://codeberg.org/pfarr.tools/pfarrplaner/commit/51017583d6ce3741cd0f25e72dcb686ee9e4fd82))
* UserEditor lässt sich nicht öffnen ([2d6f1bf](https://codeberg.org/pfarr.tools/pfarrplaner/commit/2d6f1bf480c2fc24dd4572514ef5fd41f574bf4d))
* WeeklyVerseReport verwendet obsolete API ([bda40b6](https://codeberg.org/pfarr.tools/pfarrplaner/commit/bda40b66c21e7a3db2780ä580c89f4e3fc279e4))

## [2026.14.0](https://codeberg.org/pfarr.tools/pfarrplaner/compare/v2026.12.4...v2026.14.0) (2026-08-11)


### Features

* Kirchliche Nachrichten ohne Kopfzeilen drucken ([373d377](https://codeberg.org/pfarr.tools/pfarrplaner/commit/373d3771688212724fb69195aa05084859d1d2c6))
* Lösch-Button aus Kalenderansicht entfernt ([ä75d3c](https://codeberg.org/pfarr.tools/pfarrplaner/commit/ä75d3cb73d824c43c702919a9e5a9c3ceca983b))
* Uhrzeit-Vorgabe für Seriengottesdienste ([07c72a2](https://codeberg.org/pfarr.tools/pfarrplaner/commit/07c72a22913cfd6c2b082419910aä4b4a912073))


### Bug Fixes

* Abendmahl erscheint nicht in der Gottesdienstbeschreibung ([ca4bc85](https://codeberg.org/pfarr.tools/pfarrplaner/commit/ca4bc85be1ee2f548360c12c0901797c6556d167))
* Bereits erteilte Dimissoriale bei Trauungen korrekt erkennen. ([f6d1c4d](https://codeberg.org/pfarr.tools/pfarrplaner/commit/f6d1c4db44a88fc1f2dabe1fbc10596eb5c19f1b))
* Fehlende Youtube-Links in der Gottesdienstliste ([6e1b6ec](https://codeberg.org/pfarr.tools/pfarrplaner/commit/6e1b6ecc2bfabf157e2ba2d2644bbb4e0a0084b3))
* Fehler bei der KonfiApp-API ([fb12a99](https://codeberg.org/pfarr.tools/pfarrplaner/commit/fb12a99b2a5f15bcc5b7cf959166493ff0b5db36))
* Fehler beim Erstellen von QR Codes. ([9455328](https://codeberg.org/pfarr.tools/pfarrplaner/commit/9455328eea3f51985157f4046a5d1bb5dc5183c3))
* Liederbücher können nicht zu Liedern hinzugefügt werden. ([1c3äfe](https://codeberg.org/pfarr.tools/pfarrplaner/commit/1c3äfe2b5d37286bddcfe284e8a5ca9419340f6))
* Neü Personen erscheinen nicht in der Auswahlliste ([c2d6df8](https://codeberg.org/pfarr.tools/pfarrplaner/commit/c2d6df8ec513095d84ac04b68e28bd60929943d5))
* Planungstabelle speichert nicht verlässlich ([d73bbc6](https://codeberg.org/pfarr.tools/pfarrplaner/commit/d73bbc6ed146557b003b16f9b443a33d30559815))
* Speichern bei KonfiApp-API-Fehler nicht möglich ([8565049](https://codeberg.org/pfarr.tools/pfarrplaner/commit/8565049bb03e1a442d6f4f699ca1728567a95afe))
* Suchfilter in der Liederliste funktioniert nicht ([5101758](https://codeberg.org/pfarr.tools/pfarrplaner/commit/51017583d6ce3741cd0f25e72dcb686ee9e4fd82))
* UserEditor lässt sich nicht öffnen ([2d6f1bf](https://codeberg.org/pfarr.tools/pfarrplaner/commit/2d6f1bf480c2fc24dd4572514ef5fd41f574bf4d))
* WeeklyVerseReport verwendet obsolete API ([bda40b6](https://codeberg.org/pfarr.tools/pfarrplaner/commit/bda40b66c21e7a3db2780ä580c89f4e3fc279e4))

## [2026.13.0](https://codeberg.org/pfarr.tools/pfarrplaner/compare/v2026.12.4...v2026.13.0) (2026-07-23)


### Features

* Kirchliche Nachrichten ohne Kopfzeilen drucken ([373d377](https://codeberg.org/pfarr.tools/pfarrplaner/commit/373d3771688212724fb69195aa05084859d1d2c6))
* Uhrzeit-Vorgabe für Seriengottesdienste ([07c72a2](https://codeberg.org/pfarr.tools/pfarrplaner/commit/07c72a22913cfd6c2b082419910aä4b4a912073))


### Bug Fixes

* Bereits erteilte Dimissoriale bei Trauungen korrekt erkennen. ([f6d1c4d](https://codeberg.org/pfarr.tools/pfarrplaner/commit/f6d1c4db44a88fc1f2dabe1fbc10596eb5c19f1b))
* Fehlende Youtube-Links in der Gottesdienstliste ([6e1b6ec](https://codeberg.org/pfarr.tools/pfarrplaner/commit/6e1b6ecc2bfabf157e2ba2d2644bbb4e0a0084b3))
* Fehler bei der KonfiApp-API ([fb12a99](https://codeberg.org/pfarr.tools/pfarrplaner/commit/fb12a99b2a5f15bcc5b7cf959166493ff0b5db36))
* Fehler beim Erstellen von QR Codes. ([9455328](https://codeberg.org/pfarr.tools/pfarrplaner/commit/9455328eea3f51985157f4046a5d1bb5dc5183c3))
* Liederbücher können nicht zu Liedern hinzugefügt werden. ([1c3äfe](https://codeberg.org/pfarr.tools/pfarrplaner/commit/1c3äfe2b5d37286bddcfe284e8a5ca9419340f6))
* Neü Personen erscheinen nicht in der Auswahlliste ([c2d6df8](https://codeberg.org/pfarr.tools/pfarrplaner/commit/c2d6df8ec513095d84ac04b68e28bd60929943d5))
* Planungstabelle speichert nicht verlässlich ([d73bbc6](https://codeberg.org/pfarr.tools/pfarrplaner/commit/d73bbc6ed146557b003b16f9b443a33d30559815))
* Speichern bei KonfiApp-API-Fehler nicht möglich ([8565049](https://codeberg.org/pfarr.tools/pfarrplaner/commit/8565049bb03e1a442d6f4f699ca1728567a95afe))
* Suchfilter in der Liederliste funktioniert nicht ([5101758](https://codeberg.org/pfarr.tools/pfarrplaner/commit/51017583d6ce3741cd0f25e72dcb686ee9e4fd82))
* UserEditor lässt sich nicht öffnen ([2d6f1bf](https://codeberg.org/pfarr.tools/pfarrplaner/commit/2d6f1bf480c2fc24dd4572514ef5fd41f574bf4d))
* WeeklyVerseReport verwendet obsolete API ([bda40b6](https://codeberg.org/pfarr.tools/pfarrplaner/commit/bda40b66c21e7a3db2780ä580c89f4e3fc279e4))

### [2026.12.4](https://codeberg.org/pfarr.tools/pfarrplaner/compare/v2026.12.3...v2026.12.4) (2026-07-08)


### Bug Fixes

* Items ohne verantwortliche Person(en) können nicht bearbeitet werden. ([69c1663](https://codeberg.org/pfarr.tools/pfarrplaner/commit/69c16634c7e2c058äfbc46bbfc9fa88b752cbe0))

### [2026.12.3](https://codeberg.org/pfarr.tools/pfarrplaner/compare/v2026.12.2...v2026.12.3) (2026-07-03)


### Bug Fixes

* Kasualien mit Dimissoriale können nicht gespeichert werden. ([a1ab39e](https://codeberg.org/pfarr.tools/pfarrplaner/commit/a1ab39ef3dc2f90ba046026e32ff557723f0577e))

### [2026.12.2](https://codeberg.org/pfarr.tools/pfarrplaner/compare/v2026.12.0...v2026.12.2) (2026-07-03)


### Bug Fixes

* A4LiturgySheet enthält zu viel Vorschautext ([d913d83](https://codeberg.org/pfarr.tools/pfarrplaner/commit/d913d83056607335d574668e9be0327f7f3c5b24))
* Abfragen mit Sammelkirchengemeinden funktionieren nicht mehr ([bb4b14d](https://codeberg.org/pfarr.tools/pfarrplaner/commit/bb4b14d85aa7a27a73b2ea75094485e7c3c0d651))
* artisan-Befehle aus Integrationen nicht mehr verfügbar ([2c837de](https://codeberg.org/pfarr.tools/pfarrplaner/commit/2c837de0bb35e5866ec98eb7fcab92f7a6c98eä))
* AutoAttachments werden nicht angezeigt. ([58396ab](https://codeberg.org/pfarr.tools/pfarrplaner/commit/58396abc15b719115d3dd5fd3d81cc06268b29fe))
* Beim Umschalten von Benutzern (Admin) wird der Bildschirminhalt nicht vollständig erneürt. ([bdf28ea](https://codeberg.org/pfarr.tools/pfarrplaner/commit/bdf28ea8f3416671äea05d9abe5e42c09f75cf8))
* Besserer Umgang mit Fehlern in der KonfiApp-API ([a916e97](https://codeberg.org/pfarr.tools/pfarrplaner/commit/a916e97ce702b8a7d393138cec56795e484fe7c9))
* BillboardReport produziert Fehler ([e7e8d66](https://codeberg.org/pfarr.tools/pfarrplaner/commit/e7e8d66e4605987ed95983dfea4eac10729143e2))
* Falsch gesetzte Abfrage von Benutzerrechten ([a1868e9](https://codeberg.org/pfarr.tools/pfarrplaner/commit/a1868e96d44330fc42610d55e695619273cc44cf))
* Fehlende Berechtigung für Predigtbilder behoben. ([b0f9a57](https://codeberg.org/pfarr.tools/pfarrplaner/commit/b0f9a57532c5337cf46b1f2071719b0f4631d133))
* Fehler bei Freitexteingaben im Feld "Verantwortliche" der Liturgie ([89f3894](https://codeberg.org/pfarr.tools/pfarrplaner/commit/89f389405e862dbf63dc8f1822fa0bd2febea0de))
* Fehler beim An-/Abmelden ([ee6e839](https://codeberg.org/pfarr.tools/pfarrplaner/commit/ee6e8393dbad69aac47b1285a6d26267033b763c))
* Girocodes abgeschnitten bei langen Opferzwecken ([e71f30d](https://codeberg.org/pfarr.tools/pfarrplaner/commit/e71f30d5437ba5d6cc3f4544e73ecdd1b871db30))
* Girocodes können nicht gedruckt werden. ([3bf2720](https://codeberg.org/pfarr.tools/pfarrplaner/commit/3bf2720b18724bb03d20d8a2d9e63ab9b9972a0b))
* Gottesdienstkalender zeigt alle Veranstaltungen ([4e6f2f2](https://codeberg.org/pfarr.tools/pfarrplaner/commit/4e6f2f2ca8250f6ca13cd46be1e3111da0fd5deb))
* Konfigurationskomponenten in der Startseitenkonfiguration wieder anzeigen. ([1558cfa](https://codeberg.org/pfarr.tools/pfarrplaner/commit/1558cfa00d4586c9c4c112a979äc44585c79102))
* Öffentliche Downloads brauchen Login ([b3da998](https://codeberg.org/pfarr.tools/pfarrplaner/commit/b3da99868bb2a57ccb1aä06f5a6c142095287e8))
* Prädikant:innenformular lädt nicht ([03ca1f6](https://codeberg.org/pfarr.tools/pfarrplaner/commit/03ca1f67a8806259e61d7b5a227032700d6fa628))
* QR-Codes für Konfis können nicht gedruckt werden. ([ca4c660](https://codeberg.org/pfarr.tools/pfarrplaner/commit/ca4c660b2cc6d2356e9869b0148fa2a0eee162d0))
* Reiter "Trauungen" lädt nicht ([1a90af1](https://codeberg.org/pfarr.tools/pfarrplaner/commit/1a90af10133aa44d459f97c7606157ff1b9b62b1))
* StreamingTroubleshooterController ist nicht mehr öffentlich erreichbar. ([6c883cf](https://codeberg.org/pfarr.tools/pfarrplaner/commit/6c883cf89e69022e5286a9812522af8662a2fb4f))
* Unklare Beschriftung. ([9dbdd4a](https://codeberg.org/pfarr.tools/pfarrplaner/commit/9dbdd4a14619ä33e60965099e0a9668d8d1cc17))
* Werbeschleifen funktionieren nicht mehr ([5403611](https://codeberg.org/pfarr.tools/pfarrplaner/commit/54036115c9äf9c4649cd1155a785ed041d21cc0))

### [2026.12.1](https://codeberg.org/pfarr.tools/pfarrplaner/compare/v2026.12.0...v2026.12.1) (2026-06-08)


### Bug Fixes

* A4LiturgySheet enthält zu viel Vorschautext ([d913d83](https://codeberg.org/pfarr.tools/pfarrplaner/commit/d913d83056607335d574668e9be0327f7f3c5b24))
* Abfragen mit Sammelkirchengemeinden funktionieren nicht mehr ([bb4b14d](https://codeberg.org/pfarr.tools/pfarrplaner/commit/bb4b14d85aa7a27a73b2ea75094485e7c3c0d651))
* artisan-Befehle aus Integrationen nicht mehr verfügbar ([2c837de](https://codeberg.org/pfarr.tools/pfarrplaner/commit/2c837de0bb35e5866ec98eb7fcab92f7a6c98eä))
* AutoAttachments werden nicht angezeigt. ([58396ab](https://codeberg.org/pfarr.tools/pfarrplaner/commit/58396abc15b719115d3dd5fd3d81cc06268b29fe))
* Beim Umschalten von Benutzern (Admin) wird der Bildschirminhalt nicht vollständig erneürt. ([bdf28ea](https://codeberg.org/pfarr.tools/pfarrplaner/commit/bdf28ea8f3416671äea05d9abe5e42c09f75cf8))
* BillboardReport produziert Fehler ([e7e8d66](https://codeberg.org/pfarr.tools/pfarrplaner/commit/e7e8d66e4605987ed95983dfea4eac10729143e2))
* Fehlende Berechtigung für Predigtbilder behoben. ([b0f9a57](https://codeberg.org/pfarr.tools/pfarrplaner/commit/b0f9a57532c5337cf46b1f2071719b0f4631d133))
* Fehler beim An-/Abmelden ([ee6e839](https://codeberg.org/pfarr.tools/pfarrplaner/commit/ee6e8393dbad69aac47b1285a6d26267033b763c))
* Öffentliche Downloads brauchen Login ([b3da998](https://codeberg.org/pfarr.tools/pfarrplaner/commit/b3da99868bb2a57ccb1aä06f5a6c142095287e8))
* Reiter "Trauungen" lädt nicht ([1a90af1](https://codeberg.org/pfarr.tools/pfarrplaner/commit/1a90af10133aa44d459f97c7606157ff1b9b62b1))
* StreamingTroubleshooterController ist nicht mehr öffentlich erreichbar. ([6c883cf](https://codeberg.org/pfarr.tools/pfarrplaner/commit/6c883cf89e69022e5286a9812522af8662a2fb4f))

Pfarrplaner ist freie Software. Sie dürfen das Programm unter den Bedingungen der GNU General Public License Version 3 oder später weitergeben und verändern.

Dieses Handbuch beschreibt die Bedienung für Benutzerinnen und Benutzer. Installation, Betrieb und technische Wartung sind nicht Teil dieses Handbuchs.
