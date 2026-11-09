# FTP-Container für die Ablage – Implementierungsplan

> **Für agentische Implementierer:** REQUIRED SUB-SKILL: Verwende `superpowers:subagent-driven-development` (empfohlen) oder `superpowers:executing-plans`, um diesen Plan Aufgabe für Aufgabe umzusetzen. Die Schritte verwenden Checkboxen zur Nachverfolgung.

**Ziel:** Einen optional konfigurierbaren FTP-Dienst als persistente Upload-Inbox für Scanner und andere Ablage-Quellen in die Entwicklungs- und Produktions-Compose-Umgebung integrieren.

**Architektur:** Ein dedizierter FTP-Container schreibt in ein ausschließlich für die Ablage bestimmtes Docker-Volume. Dieses Volume wird read/write in den App-Containern unter `storage/inbox` eingebunden, sodass die vorhandene Inbox-Konfiguration und API unverändert weiterarbeiten. FTP-Benutzer, Passwort, Host-Port und passiver Portbereich werden über `.env` konfiguriert; der Container wird nicht mit dem MinIO-Bucket vermischt.

**Technologie:** Docker Compose, ein schlankes FTP-Server-Image, Docker-Volumes, Laravel-Inbox-API, Bash-Strategietests und Markdown-Handbücher.

**Spezifikation:** GitHub-Issue [#41 „Füge einen FTP-Container für die Ablage hinzu“](https://github.com/pfarr-tools/pfarrplaner/issues/41).

## Globale Vorgaben

- MinIO bleibt der verbindliche Objektspeicher für dauerhafte Anwendungsdateien; die FTP-Inbox ist ausschließlich ein Eingang für noch zu verarbeitende Ablage-Dateien.
- Zugangsdaten und Portwerte kommen aus `.env` beziehungsweise `.env.example`; keine geheimen Werte, festen lokalen Ports oder produktiven Zugangsdaten committen.
- Entwicklungs- und Produktions-Compose müssen dieselbe funktionale Schnittstelle anbieten, aber getrennte Compose-Projekte, Volumes und konfigurierbare Host-Ports unterstützen.
- Die App darf nur den gemeinsamen Inbox-Pfad lesen; der FTP-Container erhält keinen Zugriff auf Quellcode, MariaDB, Redis oder MinIO.
- Zulässige Dateitypen und der Schutz gegen Pfad-Traversal bleiben die Verantwortung der bestehenden Inbox-API; der FTP-Dienst darf diese Sicherheitsgrenze nicht umgehen.
- Alle Betriebs-, Installations- und Storage-Schnittstellen werden im Administratoren- und Technischen Handbuch dokumentiert.

## Prüfungsfokus

- Falsch oder unvollständig gesetzte FTP-/Passivport-Werte dürfen keinen unbrauchbaren Compose-Stack erzeugen; der Konfigurations- und Strategietest prüft die env-gesteuerte Schnittstelle.
- Die App und der FTP-Dienst müssen exakt dasselbe Inbox-Volume sehen; der Compose-Test prüft identische Mounts und den Pfad `storage/inbox`.
- FTP-Dateien müssen Container-Neustarts überstehen; der Compose-Test prüft das benannte persistente Volume.
- Der Dienst darf nicht versehentlich MinIO- oder App-Daten exponieren; der Compose-Test prüft Volume-, Netzwerk- und Servicegrenzen.
- Die vorhandene API darf weiterhin nicht konfigurierte Inboxes, nicht erlaubte Endungen und Traversal-Anfragen akzeptieren; die fokussierten Inbox-Tests bleiben Teil der Verifikation.

---

### Aufgabe 1: Gemeinsame FTP-Inbox und Compose-Dienste definieren

**Dateien:**
- Ändern: `.env.example`
- Ändern: `compose.yaml`
- Ändern: `compose.production.yaml`
- Ändern: `scripts/tests/compose_project_name_test.sh` oder neuer fokussierter Test unter `scripts/tests/`

**Schnittstellen:**
- Verbraucht: bestehende `FTP_INBOX_STORAGE_PATH`-Konfiguration und die App-Mounts unter `/var/www/html/storage`.
- Erzeugt: ein Dienst mit stabilem Namen `ftp`, ein gemeinsames Volume `ftp_inbox`, env-gesteuerte FTP-Zugangsdaten, FTP-Port und passivem Portbereich sowie der Mount `/var/www/html/storage/inbox` für `app` und FTP.

- [ ] **Schritt 1: Bestehende Compose-Konventionen und das gewählte FTP-Image festschreiben.** Dokumentiere im Test die erwarteten env-Namen, den internen FTP-Port, den passiven Portbereich und die Mount-Ziele. Verwende ein gepflegtes, schlankes Image mit getrenntem Benutzer-/Datenverzeichnis und ohne Zugriff auf andere Pfade.
- [ ] **Schritt 2: Konfigurationsvariablen in `.env.example` ergänzen.** Füge kommentierte Werte für Aktivierung, Benutzername, Passwort, Host-FTP-Port, passiven Start-/Endport und den Containerpfad hinzu; Geheimnisse bleiben leer oder werden ausschließlich vom Installations-/Bootstrap-Weg erzeugt, sofern dieser bereits solche Werte generiert.
- [ ] **Schritt 3: Den Dienst `ftp` in `compose.yaml` ergänzen.** Konfiguriere die Zugangsdaten und den passiven Portbereich aus `.env`, binde das benannte Volume ausschließlich in das FTP-Datenverzeichnis ein, veröffentliche nur die konfigurierten FTP-/Passivports und hänge den Dienst an das bestehende Pfarrplaner-Netzwerk.
- [ ] **Schritt 4: Den Dienst `ftp` in `compose.production.yaml` ergänzen.** Übernimm dieselbe Schnittstelle mit `restart: unless-stopped`, produktionsgeeigneten Pflichtwerten ohne unsichere Geheimnisdefaults und einem passenden Netzwerk-/Portmodell für Scanner im vorgesehenen Netz.
- [ ] **Schritt 5: Das gemeinsame Volume in die App-Services einbinden.** Ergänze bei den App-Ankern beider Compose-Dateien den Mount `ftp_inbox:/var/www/html/storage/inbox` und setze `FTP_INBOX_STORAGE_PATH=inbox` als wirksamen Pfad oder dokumentiere die explizite `.env`-Pflicht dafür. Vermeide einen Mount auf `storage/app` oder den MinIO-Bereich.
- [ ] **Schritt 6: Einen strukturellen Compose-Test schreiben.** Prüfe per YAML-/Textprüfung, dass beide Dateien den Dienst, die Variablen, den identischen Inbox-Mount, das benannte Volume und keinen direkten MinIO-Mount für FTP enthalten.
- [ ] **Schritt 7: Die Compose-Dateien rendern.** Führe mit einer sicheren temporären env-Datei `docker compose -f compose.yaml config` und `docker compose -f compose.production.yaml config` aus; erwarte valide Konfiguration, keine leeren Pflichtwerte im Produktionsfall und keine Änderungen an `.env`.

### Aufgabe 2: Erstinstallation, Laufzeit und Datenlebenszyklus absichern

**Dateien:**
- Prüfen/Ändern: `scripts/bootstrap.sh` und zugehörige Bootstrap-Skripte, falls neue Pflichtwerte erzeugt oder abgefragt werden müssen
- Ändern: `scripts/tests/` fokussierter Bootstrap-/Runtime-Test
- Prüfen/Ändern: `scripts/backup.sh` oder Backup-Konfiguration, falls die Inbox bewusst gesichert oder als temporärer Eingang ausgeschlossen wird

**Schnittstellen:**
- Verbraucht: die in Aufgabe 1 definierten FTP-Variablen und das benannte `ftp_inbox`-Volume.
- Erzeugt: reproduzierbare Erstinstallation, explizite Entscheidung zur Inbox-Aufbewahrung und überprüfbare Start-/Restart-Verifikation.

- [ ] **Schritt 1: Den Bootstrap-Pfad gegen die neuen Variablen prüfen.** Stelle sicher, dass `./planer install` beziehungsweise `./planer install --dev` eine gültige Konfiguration hinterlässt, ohne bestehende `.env` ungefragt zu überschreiben oder Passwörter zu protokollieren.
- [ ] **Schritt 2: Falls erforderlich sichere Defaults oder Eingaben ergänzen.** Erzeuge ein zufälliges FTP-Passwort nur über den bestehenden Geheimnis-Mechanismus oder verlange in Produktion einen expliziten Wert; lehne leere Produktionszugangsdaten ab.
- [ ] **Schritt 3: Den Datenlebenszyklus festlegen und implementieren.** Entscheide anhand der bestehenden Ablage-API, ob die Inbox-Dateien als wiederherstellbare Eingangsdaten in Backups aufgenommen werden oder nach erfolgreicher Verarbeitung als temporär gelten; dokumentiere die Entscheidung im Backup-/Betriebscode und verhindere unbeabsichtigte MinIO-Übernahme.
- [ ] **Schritt 4: Einen Runtime-Test ergänzen.** Prüfe die Dienstnamen, Restart-Policy, persistente Volume-Zuordnung, FTP-Konfigurationsvariablen und die Existenz des Inbox-Verzeichnisses im App-Kontext. Führe keine Tests gegen die normale Dev- oder Produktionsdatenbank aus.
- [ ] **Schritt 5: Den fokussierten sicheren Testlauf ausführen.** Verwende die vorhandenen Shell-Strategietests und `./planer dev test` für betroffene Laravel-Tests; bei Docker-/Netzwerkprüfungen verwende ausschließlich temporäre Testwerte und stelle fest, dass `.env` unverändert bleibt.

### Aufgabe 3: Betreiber- und technische Dokumentation aktualisieren

**Dateien:**
- Ändern: `docs/admin/installation-und-migration.md`
- Ändern: `docs/manual/administratorhandbuch/installation-docker.md`
- Ändern: `docs/manual/administratorhandbuch/betrieb.md` und/oder `sicherheit.md`
- Ändern: `docs/manual/technikhandbuch/installation-docker.md`
- Ändern: `docs/manual/technikhandbuch/dateien-und-verzeichnisse.md`
- Ändern: passende Stichwortverzeichnisse, falls neue Begriffe aufgenommen werden

**Schnittstellen:**
- Verbraucht: die finalen Variablennamen, Portwerte, Compose-Dienstnamen, Volume-Namen und Backup-Entscheidung aus Aufgabe 1 und 2.
- Erzeugt: eine vollständige Anleitung für Scanner-/FTP-Konfiguration, Start, Diagnose, Portfreigaben, Passwortwechsel, Persistenz und sichere Abgrenzung zu MinIO.

- [ ] **Schritt 1: Installationsdokumentation ergänzen.** Beschreibe die erforderlichen `.env`-Werte, den Start mit `./planer dev up` beziehungsweise `./planer up`, den konfigurierbaren FTP- und passiven Portbereich sowie die nötige Erreichbarkeit aus dem Scanner-Netz.
- [ ] **Schritt 2: Betriebs- und Sicherheitsdokumentation ergänzen.** Erkläre, dass FTP-Zugangsdaten nicht in Git gehören, FTP möglichst nur in vertrauenswürdigen Netzen erreichbar sein soll, die Inbox keine dauerhafte Anwendungsablage ersetzt und Daten nicht mit dem MinIO-Bucket verwechselt werden dürfen.
- [ ] **Schritt 3: Technische Dateipfade und Diagnose dokumentieren.** Verknüpfe `ftp`, `ftp_inbox`, `FTP_INBOX_STORAGE_PATH` und `/var/www/html/storage/inbox`; beschreibe Prüfung mit `./planer status`, Logs und der bestehenden `/api/inbox`-Funktion.
- [ ] **Schritt 4: Dokumentationsprüfungen ausführen.** Führe `./planer dev docs:check` und die relevanten Manual-Build-/Linkprüfungen aus; erwarte korrekte deutsche Umlaute und keine geheimen Beispielwerte.

### Aufgabe 4: Gesamtverifikation und Issue-Abschlussvorbereitung

**Dateien:**
- Prüfen: alle geänderten Dateien aus den Aufgaben 1–3

- [ ] **Schritt 1: Compose-Struktur und Sicherheitsgrenzen prüfen.** Führe die fokussierten Shell-Tests, `docker compose ... config` für Entwicklung und Produktion sowie `git diff --check` aus.
- [ ] **Schritt 2: Bestehende Inbox-Funktion verifizieren.** Führe die isolierten Tests für `InboxFeatureTest` und `InboxApiFeatureTest` über `./planer dev test` aus; erwarte weiterhin erfolgreiche Downloads erlaubter Dateien, Ablehnung nicht erlaubter Endungen/Traversal und Authentifizierung der API.
- [ ] **Schritt 3: Relevante Qualitätsprüfungen ausführen.** Führe mindestens `./planer dev format:check`, `./planer dev docs:check` und die betroffenen Runtime-/Compose-Tests aus; erweitere auf `./planer dev quality`, wenn der finale Diff die vollständige Qualitätsprüfung verlangt.
- [ ] **Schritt 4: Änderungsumfang und Betriebsdokumentation gegen Issue #41 prüfen.** Stelle sicher, dass nur FTP-Inbox, Compose, Konfiguration, Tests und erforderliche Dokumentation enthalten sind; MinIO, bestehende Upload-API und unabhängige Backup-Ziele bleiben unverändert.
- [ ] **Schritt 5: Nach erfolgreicher Implementierung den Issue-Plan und die Verifikation kommentieren.** Der spätere Commit verwendet eine deutsche Conventional-Commit-Nachricht und verweist gemäß Repository-Regel auf Issue #41; ein Abschluss erfolgt erst nach realer Dev-/Produktionsprüfung der FTP-Erreichbarkeit.
