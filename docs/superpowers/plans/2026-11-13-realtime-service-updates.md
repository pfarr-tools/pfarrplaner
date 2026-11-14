# Echtzeitaktualisierung von Gottesdiensten – Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Gottesdienständerungen werden in geöffneten eingeloggten Ansichten einzeln und zeitnah als Anlegen, Ändern oder Löschen sichtbar.

**Architecture:** Laravel sendet ein schlankes, nach Gemeinde autorisiertes Ereignis über Soketi. Der Browser lädt bei Anlegen/Ändern die einzelne Service-Repräsentation über die bestehende API mit der eigenen Berechtigung und verteilt sie über einen zentralen Frontend-Dispatcher an registrierte Service-Sammlungen; Löschen wird anhand der ID verarbeitet. Nicht registrierte servicehaltige Seiten nutzen einen gezielten Inertia-Reload als Fallback.

**Tech Stack:** Laravel 11, Laravel Broadcasting/Pusher, Soketi, `laravel-echo`, `pusher-js`, Inertia.js, Vue 3, Vitest, PHPUnit 11.

**Spec:** `docs/superpowers/specs/2026-11-13-realtime-service-updates-design.md`

## Global Constraints

- Broadcasts enthalten keine vollständige benutzerabhängige Service-Repräsentation und keine Teilnehmerdaten.
- Channel-Zugriff wird serverseitig gegen die sichtbaren Gemeinden des angemeldeten Benutzers geprüft.
- Ereignisse werden erst nach erfolgreichem Datenbank-Commit gesendet.
- Tests verwenden ausschließlich die konfigurierte Testdatenbankisolation und niemals Dev- oder Produktionsdaten.
- Neue oder geänderte PHP-Deklarationen erhalten PHPDoc; neue oder geänderte JavaScript-/Vue-Deklarationen erhalten JSDoc.
- Relevante Konfigurations- und Betriebsänderungen werden im Administratoren- bzw. Technischen Handbuch dokumentiert.

## Review Focus

- Ein Gottesdienst wechselt Datum oder Gemeinde: Der alte Eintrag verschwindet und der neue erscheint nur dort, wo der Benutzer ihn sehen darf.
- Ein Gottesdienst wird gelöscht: Bereits geladene Listen entfernen ihn ohne anschließenden Zugriff auf nicht mehr vorhandene Daten.
- Ein Benutzer darf die Gemeinde nicht sehen: Das Ereignis wird nicht zugestellt bzw. die Einzelabfrage liefert keinen unautorisierten Datensatz.
- Eine Seite lädt mehrere Ereignisse schnell nacheinander: Abfragen werden entprellt oder veraltet verworfen, ohne den letzten Stand zu überschreiben.
- Soketi/Echo ist nicht verfügbar: Die Seite bleibt benutzbar und der bestehende Ladeweg/Fallback greift ohne Fehler im Vue-Lebenszyklus.

---

### Task 1: Schreibpfade und gemeinsame Ereignisverträge erfassen

**Files:**
- Modify: `src/app/Http/Controllers/ServiceController.php`
- Modify: `src/app/Http/Controllers/Api/ServiceController.php`
- Modify: `src/app/Inputs/PlanningInput.php`
- Modify: `src/app/Actions/Baptism/UpdateBaptism.php`
- Modify: `src/app/Actions/Funeral/UpdateFuneral.php`
- Modify: `src/app/Actions/Wedding/UpdateWedding.php`
- Modify: `src/app/Inputs/MultipleServicesInput.php`
- Test: `src/tests/Feature/ServiceFeatureTest.php`, `src/tests/Feature/ServiceApiFeatureTest.php`

**Interfaces:**
- Produces: Vollständige Liste der create/update/delete- und relationsbasierten Schreibpfade sowie ein einheitliches Ereignismodell für `created`, `updated` und `deleted`.

- [ ] **Step 1: Schreibe fokussierte Tests**, die für Editor-, API-, Planungs- und Kasualienänderungen genau ein Realtime-Ereignis erwarten und für Löschen die alte Gemeinde-/Datumszuordnung festhalten.
- [ ] **Step 2: Führe die fokussierten Tests isoliert aus** und dokumentiere die erwarteten Fehlschläge, bevor Implementierungscode ergänzt wird.
- [ ] **Step 3: Vereinheitliche die Auslösung**, ohne bestehende Mail-/Integrationsereignisse zu ersetzen; relation-only Änderungen wie Teilnehmerzuweisungen müssen ebenfalls den Realtime-Vertrag auslösen.
- [ ] **Step 4: Führe die fokussierten PHP-Tests erneut aus** und verifiziere create/update/delete einschließlich Datums- und Gemeindewechsel.
- [ ] **Step 5: Prüfe `git diff --check`**.

### Task 2: Broadcast-Ereignis und Channel-Autorisierung

**Files:**
- Create: `src/app/Events/ServiceRealtimeChanged.php`
- Modify: `src/routes/channels.php`
- Modify: `src/app/Http/Resources/Calendar/CalendarServiceResource.php`
- Test: `src/tests/Unit/Events/ServiceRealtimeChangedUnitTest.php`
- Test: `src/tests/Feature/ServiceRealtimeBroadcastFeatureTest.php`

**Interfaces:**
- Produces: `ServiceRealtimeChanged::created(Service $service)`, `updated(Service $service, ?int $oldCityId, ?string $oldDate)`, `deleted(int $serviceId, int $cityId, string $date)` sowie einen privaten Kanal pro Gemeinde.
- Broadcast payload: `type`, `serviceId`, `cityId`, `date`, `oldCityId`, `oldDate`; keine Service- oder Teilnehmerdaten.

- [ ] **Step 1: Schreibe Unit-Tests** für Payload, Broadcast-Namen, Commit-Verhalten und alte/neue Zuordnung.
- [ ] **Step 2: Schreibe Feature-Tests** für sichtbare und nicht sichtbare Benutzer auf dem Gemeinde-Kanal.
- [ ] **Step 3: Implementiere das Ereignis** mit `ShouldBroadcast`/`ShouldDispatchAfterCommit`, privaten Gemeinde-Kanälen und einer expliziten, dokumentierten Payload.
- [ ] **Step 4: Ergänze die Channel-Policy** in `routes/channels.php`, sodass nur Benutzer mit Sichtbarkeit für die Gemeinde abonnieren können.
- [ ] **Step 5: Führe die Ereignis- und Feature-Tests isoliert aus**.

### Task 3: Soketi-Konfiguration und echter WebSocket-Roundtrip

**Files:**
- Modify: `compose.yaml`
- Modify: `.env.example`
- Modify: `src/.env.example`
- Modify: `src/config/broadcasting.php`
- Create: `scripts/tests/soketi_broadcast_test.sh`
- Modify: `scripts/tests/planer_routing_test.sh`
- Test: `src/tests/Feature/BroadcastingConfigurationFeatureTest.php`

**Interfaces:**
- Produces: reproduzierbare Dev-Prüfung der effektiven Compose-Variablen, des Soketi-Containers, des Laravel-Pusher-Publishings und eines echten WebSocket-Empfangs.

- [ ] **Step 1: Schreibe Konfigurationstests**, die für temporäre Testwerte prüfen: `BROADCAST_DRIVER=pusher`, identische App-ID/Keys/Secret in Laravel und Soketi, interner Host `soketi`, Port `6001`, konfigurierbares Host-Port-Mapping und korrekte Vite-Frontend-Werte.
- [ ] **Step 2: Schreibe den Shell-Runtime-Test** mit einer temporären Env-Konfiguration, ohne `.env`, Produktionsdaten oder Secrets auszugeben; prüfe `docker compose config`, Containerstatus, Health/Logs, DNS-Auflösung von `soketi:6001` aus dem App-/Vite-Netz und den erreichbaren Soketi-HTTP-Publish-Endpunkt.
- [ ] **Step 3: Ergänze einen echten WebSocket-Client-Test** im laufenden Dev-Stack, der sich mit dem konfigurierten App-Key verbindet, einen privaten Testkanal autorisiert, ein Laravel-Testevent publiziert und den empfangenen Eventnamen/Payload bestätigt. Der Test muss bei falschem Key, falschem Secret, falschem Host und geschlossenem Soketi nachvollziehbar fehlschlagen.
- [ ] **Step 4: Prüfe die Browser-nahe Echo-Konfiguration** mit einem Node-Testclient beziehungsweise dem Vite-Build: WebSocket-Host und öffentlicher Port müssen vom Browser aus erreichbar sein, während Laravel intern den Compose-Dienstnamen nutzt.
- [ ] **Step 5: Führe den Test mehrfach aus**: frischer Stack, bestehender Stack nach Restart, und mit absichtlich fehlender/inkonsistenter Konfiguration. Erwartet werden klare Fehlerdiagnosen, keine stillen `null`-Broadcasts und keine Secrets in Logs/Testausgaben.
- [ ] **Step 6: Dokumentiere die exakten Prüfkommandos und Sollwerte** im Administratoren- und Technischen Handbuch und binde den Test in die relevante Docker-/CI-Prüfung ein.

### Task 4: Einzelne Service-Abfrage und Frontend-Broadcasting

**Files:**
- Modify: `src/routes/api/Calendar.php`
- Modify: `src/app/Http/Controllers/Api/CalendarController.php`
- Modify: `src/resources/js/bootstrap.js`
- Modify: `src/resources/js/inertia-app.js`
- Modify: `src/package.json`
- Modify: `src/package-lock.json`
- Create: `src/resources/js/events/ServiceRealtimeChangedEvent.js`
- Create: `src/resources/js/services/ServiceRealtime.js`
- Test: `src/tests/js/services/ServiceRealtime.test.js`

**Interfaces:**
- Produces: `ServiceRealtime.subscribe(cityIds)`, `ServiceRealtime.onChanged(handler)`, `ServiceRealtime.fetch(serviceId)`, `ServiceRealtime.unsubscribe()`.
- EventBus event: `ServiceRealtimeChangedEvent` mit `type`, `serviceId`, `service` (bei create/update), `oldCityId`, `oldDate`.

- [ ] **Step 1: Schreibe Vitest-Tests** für Echo-Verbindungsaufbau, Kanalabonnement, Einzelabfrage, Löschung, veraltete Antworten und graceful degradation ohne Echo.
- [ ] **Step 2: Installiere nur die benötigten Frontend-Abhängigkeiten** und konfiguriere Pusher/Soketi über Vite-kompatible `VITE_`-Variablen aus der bestehenden Umgebung.
- [ ] **Step 3: Implementiere `ServiceRealtime`** als zentralen Adapter mit Entprellung bzw. Request-Revision, damit schnelle Änderungen nicht von alten Antworten überschrieben werden.
- [ ] **Step 4: Initialisiere den Adapter** nach dem Laden der geteilten Benutzer-/Gemeinde-Props und räume Echo-Abonnements beim App-Lebensende auf.
- [ ] **Step 5: Führe den fokussierten JS-Test aus und prüfe Build, Lint sowie JSDoc**.

### Task 5: Registrierte Service-Ansichten aktualisieren

**Files:**
- Modify: `src/resources/js/Pages/Calendar/Calendar.vue`
- Modify: `src/resources/js/components/Calendar/Pane/Vertical.vue`
- Modify: `src/resources/js/components/HomeScreen/NextServicesTab.vue`
- Modify: `src/resources/js/components/HomeScreen/PredicantServicesTab.vue`
- Modify: `src/resources/js/Pages/Inputs/Planning/PlanningInputForm.vue`
- Modify: `src/resources/js/Pages/Inputs/Offerings/Form.vue`
- Modify: `src/resources/js/Pages/Inputs/ChildrensChurch/Form.vue`
- Create or modify: `src/resources/js/mixins/ServiceRealtime.js`
- Test: `src/tests/js/components/Calendar/RealtimeServiceUpdates.test.js`
- Test: fokussierte Tests der Startseite und Inputs

**Interfaces:**
- Produces: Adapter-Hooks `replaceService(service)`, `insertService(service)`, `removeService(serviceId)` und `matchesService(service)` für jede lokale Sammlung.

- [ ] **Step 1: Schreibe UI-Tests** für Ersetzen, Einfügen, Entfernen sowie einen Datums-/Gemeindewechsel.
- [ ] **Step 2: Implementiere den gemeinsamen Adapter** mit stabiler ID-Suche und sortierter Einfügung gemäß der jeweiligen bestehenden Ansicht.
- [ ] **Step 3: Binde Kalender, Startseiten-Tabs und die servicehaltigen Inputs an**; ein Ereignis darf nur die betroffene lokale Sammlung ändern.
- [ ] **Step 4: Ergänze für nicht kompatible Inertia-Props den Fallback**, der die aktuelle Seite gezielt neu lädt und lokale Editor-Eingaben nicht während eines ungespeicherten Formularzustands überschreibt.
- [ ] **Step 5: Führe alle betroffenen Vitest-Tests und den Frontend-Build aus**.

### Task 6: Restliche servicehaltige Ansichten, Dokumentation und Integrationstest

**Files:**
- Modify: alle im Audit aus Task 1 gefundenen weiteren servicehaltigen Vue-Seiten
- Modify: `docs/manual/administratorhandbuch/betrieb.md`
- Modify: `docs/manual/technikhandbuch/deployment.md`
- Modify: `docs/manual/technikhandbuch/architektur.md`
- Test: `src/tests/Browser/Stage2/CalendarPageLoadTest.php` oder ein fokussierter Browser-Test

- [ ] **Step 1: Ergänze die Adapter** für weitere authenticated Reports/Inputs oder dokumentiere begründet, warum eine Ansicht statisch bleibt.
- [ ] **Step 2: Dokumentiere Soketi, Frontend-Schlüssel, private Channels, Fallback-Verhalten und die Prüfung im Dev-Stack**.
- [ ] **Step 3: Führe den Browser-Smoke-Test** mit zwei Sitzungen aus: Sitzung A ändert/erstellt/löscht, Sitzung B beobachtet die betroffene Ansicht.
- [ ] **Step 4: Führe `./planer dev format:check`, `./planer dev lint`, `./planer dev docs:check`, relevante PHP-/JS-Tests, Build und `git diff --check` aus.**
- [ ] **Step 5: Kommentiere Issue #42 mit Umsetzung, Branch, Tests und verbleibenden Betriebsannahmen; erst nach erfolgreicher Verifikation darf ein späterer Commit das Issue schließen.**
