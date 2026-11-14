# Öffentliche Opfererfassung – Designspezifikation

## Ziel

Issue #79 erhält je Kirchengemeinde einen signierten, loginfreien Link. Über
diesen Link werden die Gottesdienste des aktuellen Tages angezeigt und die
Opfersumme sowie optional zwei Opferzähler:innen erfasst. Die Eingabe wird in
den bestehenden Gottesdienstfeldern gespeichert. Zusätzlich wird eine
Opferurkunde als PDF-Report angeboten.

## Umfang

- Öffentliche, mobile Inertia-Seite ohne Admin-Layout.
- Signierter Gemeinde-Link aus Administration → Gemeinde → Opfer.
- Tagesgottesdienste mit Ort, Zeit, Opferzweck und bestehendem Betrag.
- Wenn der Tag leer ist: Gottesdienste derselben Gemeinde im nahen zeitlichen
  Umfeld (zunächst die nächsten beziehungsweise vorherigen sieben Tage).
- Betrag, optional eingeblendete Felder für Opferzähler:in 1 und 2, sowie
  Speichern über eine signierte POST-Route.
- Eine zunächst eingeklappte Einzelaufstellung mit 13 Stückzahlfeldern für
  Euro-Scheine und -Münzen. Beim Ausklappen wird die Summe automatisch
  berechnet; die Stückzahlen werden als JSON am Service gespeichert.
- Serverseitige Validierung und Schutz gegen fremde Gottesdienste.
- Neuer authentifizierter PDF-Report „Opferurkunde“ für erfasste Gottesdienste.
  Die Urkunde orientiert sich an der bereitgestellten A4-Vorlage und druckt
  die Einzelaufstellung nach Schein- oder Münzsorten nur dann mit, wenn
  sie bei der Erfassung verwendet wurde.
- Im Gemeinde-Reiter „Opfer“ kann eine E-Mail-Adresse für den automatischen
  Versand hinterlegt werden. Nach erfolgreicher öffentlicher Erfassung wird
  die Urkunde an diese Adresse gemailt; ohne Adresse erfolgt kein Versand.

Nicht Bestandteil ist eine eigene Opferhistorie: Die vorhandenen Service-Felder
bleiben die fachliche Quelle; nur die optionalen Denominationsstückzahlen
werden als JSON am Service ergänzt. Eine geographische Entfernungssuche ist
ebenfalls nicht Bestandteil; „näheres Umfeld“ wird mangels Ortskoordinaten
zeitlich innerhalb derselben Gemeinde bestimmt.

## Ansätze

1. Eine neue Datenstruktur für Opfer mit eigener Historie würde spätere
   Auswertungen erleichtern, ist aber für die bestehende Anwendung unnötig
   invasiv und würde die vorhandenen Reports entkoppeln.
2. Die bestehende `/qr/{city}`-Seite könnte erweitert werden, ist jedoch nicht
   signiert und fachlich auf KonfiApp-QR-Codes zugeschnitten.
3. Empfohlen und gewählt ist eine eigene signierte öffentliche Route mit
   Wiederverwendung der Service-Felder sowie ein eigenständiger Report. Damit
   bleiben Berechtigungsgrenze, mobile Oberfläche und bestehende Auswertungen
   klar getrennt.

## Technische Leitplanken

- Route und Controller prüfen die Laravel-Signatur; Login ist nicht
  erforderlich.
- Nur Services der aufgerufenen Gemeinde dürfen gelesen und geschrieben
  werden; zusätzlich wird der Service am aktuellen beziehungsweise ausgewählten
  Datum validiert.
- Geldbeträge werden als bestehendes Textfeld mit deutscher Dezimalnotation
  akzeptiert und normalisiert gespeichert.
- Wenn Stückzahlen übermittelt werden, berechnet der Server die Summe aus den
  festen Cent-Werten und ignoriert einen abweichenden Client-Gesamtbetrag.
- Die Opferurkunde übernimmt die gespeicherten Stückzahlen in ihre
  Einzelaufstellung; ohne Stückzahlen bleibt dieser Bereich in der Urkunde
  weg.
- Der Admin-Link wird nicht als dauerhaftes Klartext-Geheimnis gespeichert,
  sondern bei Anzeige aus der Laravel-URL-Signatur erzeugt.
- Der automatische Versand läuft als Mail-Job und erzeugt die PDF aus denselben
  Reportdaten wie der manuelle Abruf.
- Neue öffentliche Vue- und PHP-Deklarationen erhalten die im Repository
  vorgeschriebenen JSDoc-/PHPDoc-Blöcke; Tests verwenden die isolierte
  SQLite-Testdatenbank.

## Erfolgskriterien

- Gäste ohne Login können nur mit gültiger Signatur die Gemeinde-Seite öffnen
  und Beträge speichern.
- Ungültige Signaturen, fremde Services und nicht erlaubte Datumswerte werden
  abgewiesen.
- Die öffentliche Seite ist auf kleinen Bildschirmen bedienbar.
- Der neue Report ist im authentifizierten Reportbereich sichtbar und erzeugt
  für einen erfassten Gottesdienst eine lesbare einseitige PDF-Opferurkunde
  mit optionaler Einzelaufstellung, sofern Stückzahlen erfasst wurden.
