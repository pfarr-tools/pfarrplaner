# Werbung-Asset-Panel Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Den Werbung-Tab als einheitliches Asset-Panel mit Zielkarten,
wiederverwendbaren Quellen, funktionierendem Löschen und konsistentem
Upload-/Crop-Dialog umsetzen.

**Architecture:** `AdsTab` wird zur Zielkarten- und Veröffentlichungsansicht;
`FormFileUploader` bleibt der gemeinsame Quellen-/Dialog-Orchestrator. Die
Lightbox und der lokale Cropper sind eigenständige UI-Komponenten mit klaren
Events. Uploads werden nach erfolgreichem Abschluss aus der lokalen Queue
entfernt.

**Tech Stack:** Vue 3 Options API, Bootstrap/MDI, Canvas, Axios, Laravel,
Vitest und PHPUnit.

**Spec:** `docs/superpowers/specs/2026-11-08-werbung-asset-panel-design.md`

## Global Constraints

- Desktop ist der Normalfall; mobile Bedienbarkeit bleibt vollständig erhalten.
- Jede Uploadquelle öffnet denselben Dialog; der Upload startet erst nach
  Bestätigung.
- Bestehende Ziel-Cuts können bearbeitet, ersetzt, gelöscht und als Quelle
  für neue Cuts verwendet werden.
- Tests laufen über Docker und nie gegen eine Standard-Dev-/Produktionsdatenbank.
- Vor Abschluss: `git diff --check`, vollständige JS-Suite und Produktionsbuild.

## Review Focus

- Ziel bereits belegt: Ersetzen fragt explizit nach und erzeugt keinen zweiten
  Attachment-Datensatz mit demselben Cut.
- Bestehender Cut als Quelle: neuer Cut erhält unabhängigen lokalen Zuschnitt.
- Upload erfolgreich: Queue wird entfernt und nicht erneut gesendet.
- Cropper: Zoom unter/über 1, transparente Quelle, kombinierte Rotation und
  Spiegelung erzeugen ein sichtbares Ergebnis.
- Lightbox: sichtbare Chevrons, alle Bilder navigierbar, Ctrl+Wheel zoomt nur
  bei Maus über dem Bild.

### Task 1: Upload-Dialog und Queue-Lifecycle

**Files:**
- Modify: `src/resources/js/components/Ui/forms/FormFileUploader.vue`
- Modify: `src/resources/js/components/Ui/forms/FormFileUpload.vue`
- Modify: `src/resources/js/components/Ui/forms/attachmentUploadQueue.js`
- Test: `src/tests/js/components/Ui/forms/FormFileUploader.test.js`
- Test: `src/tests/js/components/Ui/forms/FormFileUpload.test.js`

- [ ] Failing tests für `editing → ready → uploading → removed`, einheitliche
  Titelbearbeitung, alle Quellen und keine Eingabefelder in der Queue schreiben.
- [ ] Tests rot ausführen.
- [ ] Dialog für Bild- und Nichtbilddateien vereinheitlichen; Queue nach Erfolg
  entfernen und Bearbeiten erneut ermöglichen.
- [ ] Tests grün ausführen.

### Task 2: Cropper und Vorschauen

**Files:**
- Modify: `src/resources/js/components/Ui/forms/AttachmentImageEditor.vue`
- Modify: `src/resources/js/components/Ui/elements/AttachmentLightbox.vue`
- Modify: `src/resources/js/components/Ui/forms/FormFileUploader.vue`
- Test: `src/tests/js/components/Ui/forms/AttachmentImageEditor.test.js`
- Test: `src/tests/js/components/Ui/elements/AttachmentLightbox.test.js`

- [ ] Failing Tests für Zoom `<1`, große Vorschau, transparente Ausgabe,
  Ctrl+Wheel und sichtbare Navigation ergänzen.
- [ ] Tests rot ausführen.
- [ ] Stage/Canvas skalieren, Checkboard-Hintergründe verwenden, Zoom und
  Lightbox-Wheelzoom implementieren; Modal-Footer auf eine einzige Buttonzeile
  reduzieren.
- [ ] Tests grün ausführen.

### Task 3: Service-Cut-Zuordnung und Löschen

**Files:**
- Modify: `src/resources/js/components/ServiceEditor/tabs/AdsTab.vue`
- Modify: `src/resources/js/components/Ui/elements/AttachmentList.vue`
- Modify: `src/resources/js/components/Ui/elements/Attachment.vue`
- Test: `src/tests/js/components/ServiceEditor/AdsTab.test.js`
- Test: `src/tests/js/components/Ui/elements/AttachmentList.test.js`

- [ ] Failing Tests für Löschen, vorhandene Cut-Ersetzung und unabhängiges
  Wiederverwenden einer Quelle schreiben.
- [ ] Tests rot ausführen.
- [ ] Autorisierte Service-Delete-Route mit reaktivem `v-model` verbinden und
  eine eindeutige Cut-Aktion mit Bestätigung implementieren.
- [ ] Tests grün ausführen.

### Task 4: Werbung-Asset-Panel

**Files:**
- Modify: `src/resources/js/components/ServiceEditor/tabs/AdsTab.vue`
- Modify: `src/resources/js/components/ServiceEditor/adsImagePresets.js`
- Test: `src/tests/js/components/ServiceEditor/adsImagePresets.test.js`
- Test: `src/tests/js/components/ServiceEditor/AdsTab.test.js`

- [ ] Failing Tests für Zielkarten, Aktivierung, Preview, Cut-Info und
  „Für neuen Cut verwenden“ schreiben.
- [ ] Tests rot ausführen.
- [ ] „Verfügbare Bilder“ entfernen; gemeinsame Upload-Zone und Zielkarten mit
  Live-Preview, Enable/Disable, Edit, Replace, Reuse und Delete umsetzen.
- [ ] Alle Uploadquellen in diesem Panel aktivieren, sofern der Kontext sie
  zulässt.
- [ ] Tests grün ausführen.

### Task 5: Gesamtprüfung

- [ ] `git diff --check` ausführen.
- [ ] vollständige JS-Suite über `./planer dev npm run test:js:run` ausführen.
- [ ] Produktionsbuild über `./planer dev npm run build` ausführen.
- [ ] relevante PHP-Tests isoliert ausführen.
- [ ] Browserprüfung für Desktop, mobile Breite, bestehende Cuts, Reuse,
  Löschen, Lightbox und alle Uploadquellen durchführen.
