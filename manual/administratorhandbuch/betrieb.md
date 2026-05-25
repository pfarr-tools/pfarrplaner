[//]: # (TOC: 6. Betrieb und Wartung)

# Betrieb und Wartung

Nach der Installation beginnt die eigentliche Arbeit. Eine stabile Pfarrplaner-Instanz lebt davon, dass wiederkehrende Betriebsaufgaben zuverlässig erledigt werden.

## Regelmäßige Aufgaben

- Logs prüfen
- Backups kontrollieren
- freien Speicherplatz überwachen
- Queue-Worker und Scheduler prüfen
- Mailzustellung beobachten
- Sicherheitsupdates des Betriebssystems einspielen

## Wichtige Anwendungsbereiche im Betrieb

### Konfiguration

Die zentrale Laufzeitkonfiguration liegt in der `.env`. Änderungen dort sollten bewusst dokumentiert werden. Nach relevanten Änderungen sind meist Cache- und Konfigurationsbefehle nötig, zum Beispiel `php artisan optimize` oder gezielte Cache-Clears.

### Caches

Laravel und Vite arbeiten mit verschiedenen Caches. Nach Änderungen an Konfiguration, Views oder Assets ist es sinnvoll, den Cachezustand bewusst zu prüfen statt blind viele Befehle nacheinander auszuführen.

### Queue

Sobald Pfarrplaner Hintergrundaufgaben nutzt, brauchen Sie eine überwachte Worker-Instanz. Ein abgestürzter Queue-Worker fällt im Alltag oft erst spät auf, wenn E-Mails, Exporte oder Folgeaktionen nicht mehr ankommen.

### Scheduler

Der Scheduler sollte einmal pro Minute laufen. Fehlt dieser Cronjob oder ist er defekt, bleiben geplante Aufgaben liegen.

### Dateispeicher

Anhänge, Bilder und andere Dateien dürfen nicht nur in Backups berücksichtigt werden. Sie brauchen auch ausreichend Speicherplatz und funktionierende Schreibrechte.

## Prüfliste nach Server-Arbeiten

Wenn Sie am Server, an PHP oder an Docker-Grundlagen gearbeitet haben, prüfen Sie danach mindestens:

- Login
- Datei-Uploads
- Berichte und PDF-Ausgaben
- Mails
- Kalender- und Editoransichten

## Rechte und Rollen in der Anwendung

Zur technischen Administration gehört auch ein klares Verständnis der administrativen Oberfläche:

- Benutzer
- Rollen
- Gemeinden
- Orte
- Vertretungs-Pools
- Lieder und liturgische Texte

Diese Inhalte werden funktional im [Benutzerhandbuch](https://handbuch.pfarrplaner.de/benutzerhandbuch/administration/) beschrieben. Dieses Administratorhandbuch ergänzt dazu die Betriebs- und Sicherheitsaspekte.
