[//]: # (TOC: 8. Sicherheit, Backup und Datenschutz)

# Sicherheit, Backup und Datenschutz

Pfarrplaner verarbeitet personenbezogene Daten. Darum gehören Sicherheit und Wiederherstellbarkeit nicht an den Rand, sondern in den normalen Betriebsalltag.

## Zugangsschutz

- Nur HTTPS nach außen
- starke Passwörter
- möglichst wenige Super-Admin-Konten
- getrennte Konten für normale Nutzung und Administration, wenn sinnvoll
- keine gemeinsamen Dauerpasswörter im Team

## Serverhärtung

- nur nötige Ports öffnen
- SSH absichern
- Sicherheitsupdates des Betriebssystems zeitnah einspielen
- keine unnötigen Werkzeuge auf dem Produktionsserver belassen

## Pfarrplaner-spezifische Härtung

Für Pfarrplaner selbst sollten Sie besonders darauf achten:

- `APP_DEBUG` in Produktion auf `false`
- Entwicklerhilfen wie Boost, Telescope, Ignition-Runnable-Solutions oder Dusk nur gezielt und nie dauerhaft in Produktion aktivieren
- öffentliche Datei- und Bildlinks aus `attachments/` nur über signierte URLs weitergeben
- keine alten Direktlinks zu Logout-, Token-, Patch- oder Benutzerwechsel-Routen weiterverwenden, wenn Integrationen oder Eigenanpassungen existieren
- öffentliche Einbettungen und Feeds regelmäßig darauf prüfen, ob sie wirklich nur die beabsichtigten Daten preisgeben

Ein technischer Überblick über den Sicherheitsdurchgang und die abgesicherten Routen liegt im [Technikhandbuch](../technikhandbuch/sicherheitsaudit.md).

## Backup

Ein brauchbares Backup umfasst mindestens:

- Datenbank
- `storage/`
- falls nötig `.env` oder andere sicher verwahrte Konfigurationsdaten

Wichtig ist nicht nur das Erstellen, sondern auch das Wiederherstellen. Testen Sie regelmäßig, ob sich aus den Sicherungen wirklich eine lauffähige Instanz herstellen lässt.

## Datenschutz

Prüfen Sie besonders:

- Wer darf welche personenbezogenen Daten sehen?
- Welche öffentlichen Ausgaben enthalten personenbezogene Daten?
- Welche Demo-, Test- oder Schulungsinstanzen enthalten Produktivdaten?
- Wie lange bleiben Protokolle, Uploads und Exporte gespeichert?

## Mail und externe Integrationen

Externe Integrationen sollten sparsam und nachvollziehbar konfiguriert sein. Zugangsdaten gehören nur in geschützte Konfigurationswege, nicht in Tickets, Chats oder unverschlüsselte Notizen.

## Vor größeren Änderungen

Vor Updates, Serverumzügen oder Integrationsarbeiten:

1. Backup prüfen.
2. Wartungsfenster festlegen.
3. Rückfallplan festhalten.
4. Verantwortliche informieren.
