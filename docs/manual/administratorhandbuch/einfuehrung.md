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

## Der vorgesehene Betriebsweg

Dieses Repository ist die Betriebs- und Migrationsschicht von Pfarrplaner. Die
Laravel-Anwendung liegt darunter in `src/`; der zentrale Einstieg für
Entwicklung, Backups, Migrationen und Produktionsupdates ist `./planer`.

Der vorgesehene und künftig ausschließlich unterstützte Produktionsweg ist
Docker Compose. Die Betriebsdateien liegen im Root-Repository; die Laravel-
Anwendung wird über die zentralen `./planer`-Befehle betrieben.

Für eine neue Instanz beginnen Sie mit:

```sh
./planer bootstrap
./planer up -d --build
./planer status
```

Der konkrete Ablauf steht in [Installation mit Docker Compose](installation-docker.md).

## Was dieses Handbuch abdeckt

- Voraussetzungen für Server, Laufzeit und Zusatzprogramme
- Einrichtungsablauf mit Docker Compose
- Updatepfad für Docker Compose
- Betrieb, Wartung und Sicherheitsaufgaben
- typische Fehlerbilder

Details zur internen Architektur, zu Quellcode-Strukturen und zur API stehen im [Technischen Handbuch](https://handbuch.pfarrplaner.de/technikhandbuch/).
