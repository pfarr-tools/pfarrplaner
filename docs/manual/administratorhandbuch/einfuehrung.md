[//]: # (TOC: 1. Einführung)

# Einführung

Pfarrplaner braucht im laufenden Betrieb mehr als nur funktionierenden Programmcode. Eine produktive Instanz braucht eine passende Serverumgebung, regelmäßige Updates, sichere Zugangsdaten, laufende Sicherungen und einen klaren Blick auf Protokolle, Hintergrundaufgaben und Speicher.

Dieses Handbuch ist deshalb kein Entwicklerhandbuch. Es beschreibt die praktische Verantwortung einer Person oder eines Teams, das Pfarrplaner für eine Gemeinde, einen Kirchenbezirk oder eine größere Trägerstruktur bereitstellt.

## Typische Aufgaben der Administration

- Server und Laufzeitumgebung bereitstellen.
- Die Anwendung mit Datenbank, Mail und Dateispeicher verbinden.
- Benutzerkonten mit den richtigen Rollen anlegen.
- Updates geordnet und mit Rückfallmöglichkeit einspielen.
- Backups, Wiederherstellung und Protokolle im Blick behalten.
- Externe Integrationen, Dateiausgabe und Browser-Automatisierung funktionsfähig halten.

## Wichtige Grundentscheidung

Vor der ersten Installation sollten Sie festlegen, welchen Betriebsweg Sie dauerhaft nutzen möchten:

1. **Klassische Installation**: Quellcode mit `git clone`, Abhängigkeiten mit Composer und npm, Einrichtung mit dem neuen CLI-Installer `php artisan pfarrplaner:install`.
2. **Docker-Compose-Installation**: Container für App, Datenbank und ergänzende Dienste, danach ebenfalls der CLI-Installer innerhalb der Containerumgebung.

Beide Wege sind möglich. Wichtig ist vor allem, dass Sie einen Weg sauber dokumentieren und danach bei Updates dabei bleiben.

## Was dieses Handbuch abdeckt

- Voraussetzungen für Server, Laufzeit und Zusatzprogramme
- Einrichtungsabläufe für beide Installationswege
- Updatepfade für beide Installationswege
- Betrieb, Wartung und Sicherheitsaufgaben
- typische Fehlerbilder

Details zur internen Architektur, zu Quellcode-Strukturen und zur API stehen im [Technischen Handbuch](https://handbuch.pfarrplaner.de/technikhandbuch/).
