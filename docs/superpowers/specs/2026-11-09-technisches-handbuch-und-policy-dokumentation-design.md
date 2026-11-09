# Technisches Handbuch und Policy-Dokumentation

## Zweck

Das Technische Handbuch soll den Docker-Stack von Pfarrplaner als zusammenhängenden Betriebs- und Entwicklungsrahmen erklären. Die Installation wird ausführlich und reproduzierbar beschrieben. Das Administratorhandbuch erhält zusätzlich eine vollständige Übersicht der registrierten Policies und ihrer Verben, damit Administratorinnen und Administratoren die Wirkung von Rollen und Berechtigungen nachvollziehen können.

## Ziele

- Vollständige Installationsdokumentation vom Host bis zum ersten Health-Check.
- Verständliche Beschreibung der Entwicklungs- und Produktions-Compose-Dienste, Volumes, Netze und Betriebsprozesse.
- Dokumentation der zentralen `./planer`-Befehle mit sicherem Kontext (Entwicklung oder Produktion).
- Policy-Referenz je Modell bzw. Berechtigungsbereich mit Verben und verständlicher Berechtigungsbeschreibung.
- API-, Architektur- und Qualitätskapitel als technische Referenz erhalten und auf den Docker-Betrieb einordnen.

## Nichtziele

- Keine Dokumentation interner Container-Requirements, die Administratorinnen und Administratoren nicht konfigurieren müssen.
- Keine Änderung an Anwendungsberechtigungen, Rollen, Compose-Dateien oder Betriebsabläufen.
- Keine neue Generator- oder Laufzeitlogik für die Policy-Tabelle.

## Ansätze

1. **Gezielte Neustrukturierung (gewählt):** Das Technikhandbuch wird auf den Docker-Stack ausgerichtet; API und fachliche Architektur bleiben als kompakte technische Referenzen erhalten. Die Policy-Referenz wird statisch dokumentiert und gegen den aktuellen AuthServiceProvider und die Policy-Dateien geprüft.
2. **Vollständiger Ersatz:** Die bisherigen technischen Kapitel würden zugunsten einer reinen Stack-Dokumentation entfallen. Das würde die Entwickler- und API-Orientierung unnötig verschlechtern.
3. **Automatische Policy-Generierung:** Eine neue Build- oder Testlogik würde die Policy-Tabelle aus PHP ableiten. Das wäre für diese rein dokumentarische Aufgabe unverhältnismäßig und würde die Dokumentationspipeline erweitern.

## Umsetzung

- `docs/manual/technikhandbuch/index.md` und die vorhandenen technischen Kapitel werden auf einen klaren Docker-Stack-Einstieg, eine ausführliche Installation, Laufzeit, Befehle, Deployment und Fehlerbehebung ausgerichtet.
- Die vorhandenen API-, Sicherheits- und Qualitätskapitel bleiben erhalten; Querverweise und Inhaltsverzeichnis werden aktualisiert.
- Das Administratorhandbuch erhält ein eigenes Kapitel `berechtigungen.md` mit allen registrierten Policies und Sonderfähigkeiten. Vererbte oder interne Hilfsmethoden werden nicht als Benutzerberechtigungen aufgeführt.
- Die Stichwortverzeichnisse erhalten Einträge für Installation, Docker-Stack, `./planer` und Policies/Berechtigungen.
- Verifikation erfolgt mit `git diff --check`, den vorhandenen Manual-CLI-Tests sowie `./planer dev format:check` und `./planer dev docs:check` soweit die lokale Docker-Umgebung verfügbar ist.
