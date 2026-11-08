# Werbung-Handbuch und AdEditor-Dokumentation

## Goal

Das Benutzerhandbuch erhält ein neues Kapitel 9 „Werbung“ direkt nach dem
Kapitel „Liturgie-Editor“. Die Dokumentation beschreibt den eigenständigen
AdEditor, seine beiden Tabs, Bildquellen, lokale Bearbeitung, Zuschnitte,
Wiederverwendung und Veröffentlichungen. Ein reproduzierbarer Dusk-Test erzeugt
isolierte Beispieldaten und aktualisiert je einen Screenshot für beide Tabs.

## Architecture

- Das neue Kapitel liegt unter `docs/manual/benutzerhandbuch/werbung.md`.
- Querverweise in Einführung, Veranstaltungen, Administration und Stichwortverzeichnis
  verweisen auf das neue Kapitel; die veraltete Beschreibung des eingebetteten
  Werbung-Tabs wird auf den eigenständigen Bereich umgestellt.
- `AdvertisingScreenshotTest` verwendet die vorhandene
  `ManualScreenshotTestCase`-Infrastruktur und legt Service, Werbekonfiguration
  und SVG-Bildanhänge ausschließlich in der isolierten Dusk-Datenbank und im
  isolierten Storage an.
- Die Screenshots werden durch den bestehenden Manual-Dusk-Befehl erzeugt und
  in `docs/manual/benutzerhandbuch/media/images/` abgelegt.

## Tech Stack

- Laravel, Inertia, Vue 3 und Bootstrap-Layout im AdEditor.
- Laravel Dusk mit dem vorhandenen `ManualScreenshotTestCase`.
- Markdown-Handbuch mit den bestehenden PNG-Screenshot-Konventionen.

## Specification

Die aktuelle fachliche Gestaltung ist in
`docs/superpowers/specs/2026-11-08-werbung-asset-panel-design.md` festgehalten.
Die Umsetzung dokumentiert den tatsächlich sichtbaren Stand der beiden AdEditor-
Tabs und behauptet keine Funktionen, die im UI nicht vorhanden sind.

## Global Constraints

- Keine Produktions- oder Dev-Datenbank für Tests verwenden.
- Keine `.env`, Laufzeitdaten, Vendor-Dateien oder Node-Module committen.
- Bestehende Screenshots und unbeteiligte lokale Änderungen erhalten.
- Neue Dusk-Daten müssen deterministisch und verständlich beschriftet sein.
- Vor Abschluss `git diff --check`, fokussierten Dusk-Test bzw. den vorgesehenen
  Manual-Dusk-Befehl und den relevanten Build prüfen.

## Review Focus

- Kapitelnummer und Reihenfolge im Benutzerhandbuch.
- Verständliche Erklärung von „Werbebilder“ und „Veröffentlichungen“.
- Screenshots zeigen tatsächlich Beispielbilder, Zuschnitte und Beispieldaten.
- Test läuft mit der bestehenden isolierten Dusk-Datenbank.
- Keine Rückkehr zur alten eingebetteten Werbung-Dokumentation ohne Hinweis auf
  den eigenständigen AdEditor.

## Tasks

1. Bestehende Manual-Dokumentation und AdEditor-Screenshot-Konventionen prüfen.
2. Dusk-Test für beide AdEditor-Tabs mit deterministischen Bildanhängen und
   Veröffentlichungsdaten hinzufügen.
3. Kapitel 9 sowie die betroffenen Querverweise und Altbeschreibungen anpassen.
4. Manual-Dusk-Test, Build und Diff-Prüfungen ausführen; erzeugte Screenshots
   und Dokumentationsänderungen prüfen.
