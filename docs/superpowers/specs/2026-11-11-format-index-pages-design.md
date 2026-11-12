# Design: Moderne Indexseiten für Ein- und Ausgabeformate

## Intent

Issue #40 soll die Auswahl von Assistenten und Ausgabeformaten in eine
einheitliche, moderne und schnell erfassbare Oberfläche überführen. Beide
Seiten sollen sich wie Teile derselben Anwendung anfühlen und weiterhin ohne
Änderung der fachlichen Auswahl- und Routinglogik funktionieren.

## Ziele

- Kompakte, gruppierte Werkzeuglisten für Ein- und Ausgabeformate.
- Einheitliche Kopfzeile, Suche, Gruppierung und Interaktion.
- Icons und einzeilige Beschreibungen als schnelle Orientierung.
- Tastatur- und Screenreader-freundliche Links bzw. Buttons.
- Bestehende Inertia-/Vue-Datenstrukturen und Routen weiterverwenden.
- Aussagekräftige Leerzustände für fehlende Berechtigungen bzw. Suchtreffer.

## Nichtziele

- Keine Änderungen an den Eingabe- oder Ausgabeformaten selbst.
- Keine neuen Backend-Endpunkte oder Datenbankänderungen.
- Keine dauerhafte Umschaltung zwischen Karten- und Tabellenansicht.
- Keine Änderung der Berechtigungen oder Navigation.

## Ansätze

1. Kompakte gemeinsame Werkzeugliste: empfohlen, weil sie viele Optionen
   scanbar darstellt, wenig vertikalen Raum benötigt und beide Seiten ohne
   zusätzliche Zustandslogik vereinheitlicht.
2. Gemeinsames responsives Kartenraster: visuell auffällig, aber für diese
   Auswahlseiten zu groß und zu wenig informationsdicht.
3. Karten-/Tabellenumschalter: flexibel, aber für Issue #40 unnötig komplex
   und mit zusätzlicher Zustands- und Testfläche.

## Umsetzung

Die Ausgabeformatseite bleibt bei der vorhandenen Inertia-Datenquelle, wird
aber als Vue-Seite mit wiederverwendbarer Index-Komponente dargestellt. Die
Assistentenseite erhält denselben visuellen Aufbau. Die Optionen erscheinen
als vollständig klickbare, kompakte Links in zwei Spalten auf großen
Bildschirmen und einer Spalte auf kleinen Bildschirmen. Beide Seiten haben
einen sichtbaren Fokuszustand und getrennte Leerzustände für fehlende Einträge
und erfolglose Suchen. Die Gruppierung der Ausgabeformate bleibt erhalten; die
Suche berücksichtigt Titel und Beschreibung.

## Qualität

Die relevanten Vue-Tests werden um die gemeinsame Indexdarstellung ergänzt
bzw. angepasst. Anschließend werden Formatprüfung, JSDoc-Prüfung,
JavaScript-Linting und der relevante Testlauf ausgeführt. Die Änderungen
bleiben uncommitted zur visuellen Prüfung durch den Auftraggeber.
