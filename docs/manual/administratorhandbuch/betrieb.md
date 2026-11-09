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

Die zentrale Laufzeitkonfiguration liegt in der `.env`. Änderungen dort sollten bewusst dokumentiert werden. Im Docker-Betrieb werden Laravel-Befehle über den Root-Einstieg ausgeführt, zum Beispiel `./planer exec app php artisan optimize`.

### Caches

Laravel und Vite arbeiten mit verschiedenen Caches. Nach Änderungen an Konfiguration, Views oder Assets ist es sinnvoll, den Cachezustand bewusst zu prüfen statt blind viele Befehle nacheinander auszuführen.

### Queue

Sobald Pfarrplaner Hintergrundaufgaben nutzt, brauchen Sie eine überwachte Worker-Instanz. Ein abgestürzter Queue-Worker fällt im Alltag oft erst spät auf, wenn E-Mails, Exporte oder Folgeaktionen nicht mehr ankommen.

In der Produktionsumgebung ist Horizon der Queue-Worker für ausgehende Mails.
Postfix verwaltet zusätzlich seine eigene persistente Zustellwarteschlange.
Prüfen Sie beide Ebenen mit `./planer exec app php artisan queue:failed` und
den Postfix-Logs. Details stehen im [Mail-Betriebskapitel](mail.md).

### Scheduler

Der Scheduler sollte einmal pro Minute laufen. Fehlt dieser Dienst oder ist er
defekt, bleiben geplante Aufgaben liegen. Der Docker-Scheduler führt die
Anwendungsaufgaben selbstständig aus: YouTube-Streams werden alle zehn Minuten
aktualisiert und stündlich bereinigt; Query-Logs werden um 03:00 Uhr geleert,
der Liturgiekalender um 05:01 Uhr aktualisiert und abgelaufene Anmeldungen um
05:10 Uhr gelöscht.

Backups gehören nicht zu diesen Laravel-Aufgaben. `./planer backup create`
verwendet den separaten Backup-Container. Während der lokalen Erfassung sperrt
der Root-Workflow den HTTP- und API-Zugriff über den Laravel-Wartungsmodus und
hält nur die zuvor laufenden Worker von Horizon und Scheduler an. MariaDB und
MinIO bleiben verfügbar. Nach der lokalen Erfassung wird die Anwendung wieder
freigegeben; der Restic-Upload darf danach weiterlaufen. Ein bereits aktiver
Wartungsmodus sowie zuvor gestoppte Dienste werden nicht ungefragt verändert.
Backup-Erstellung, Aufbewahrung und Prüfung müssen weiterhin über den
dokumentierten Root-Workflow und einen passenden Host-Timer ausgelöst werden.

### Dateispeicher

Anhänge, Bilder und andere Dateien liegen im Produktionsbetrieb im MinIO-Bucket.
Sie müssen im Backup enthalten sein; lokale Containerdateien oder `storage/`
allein sind dafür nicht ausreichend. Prüfen Sie außerdem Speicherplatz und
Zugriff auf das MinIO-Volume.

Scanner-Dateien werden vorübergehend über den Dienst `ftp` in der Inbox
`ftp_inbox` abgelegt. Prüfen Sie nach der Einrichtung `./planer status` und
`./planer logs ftp` sowie die Erreichbarkeit des konfigurierten FTP- und
Passivportbereichs aus dem Scanner-Netz. Die Inbox ist nicht Teil des MinIO-
Backups. Verarbeiten Sie offene Dateien vor einer Volume-Löschung oder sichern
Sie sie vorher außerhalb des Pfarrplaner-Backups.

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
- Rollen und Berechtigungen über die Konfiguration und `roles:sync`
- Gemeinden
- Orte
- Vertretungs-Pools
- Lieder und liturgische Texte

Diese Inhalte werden funktional im [Benutzerhandbuch](https://handbuch.pfarrplaner.de/benutzerhandbuch/administration/) beschrieben. Dieses Administratorhandbuch ergänzt dazu die Betriebs- und Sicherheitsaspekte.

Die vollständige technische Zuordnung von Policy-Verben, Rollen und fachlichen
Berechtigungen steht im Kapitel [Berechtigungen und Policies](berechtigungen.md).

## Gelöschte Fachdaten

Für Gottesdienste, Abwesenheiten, Taufen, Bestattungen und Trauungen verwendet Pfarrplaner einen anwendungsinternen Papierkorb. Normales Löschen entfernt diese Datensätze also nicht sofort endgültig aus der Datenbank.

Für den Betrieb bedeutet das:

- versehentlich gelöschte Einträge können über **Administration → Papierkorb** wiederhergestellt werden
- der Papierkorb zeigt zusätzlich, welches Benutzerkonto die Löschung ausgelöst hat, solange die Aktion angemeldet erfolgt ist
- zugehörige Anhänge bleiben bis zur Wiederherstellung oder endgültigen Löschung mit dem Facheintrag verknüpft
- Einträge bleiben 30 Tage im Papierkorb und werden danach durch einen täglichen Bereinigungslauf endgültig gelöscht
- endgültiges Löschen geschieht bewusst und getrennt im Papierkorb
- Backups bleiben trotzdem notwendig, weil der Papierkorb keine historische Versionierung ersetzt

Wenn Benutzer melden, dass ein Eintrag "verschwunden" sei, prüfen Sie deshalb zuerst den Papierkorb, bevor Sie auf Datenbank- oder Backup-Ebene eingreifen.
