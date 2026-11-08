# Gemeinsamer Anhang-Uploader – Implementierungsplan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Einen gemeinsamen Desktop-Anhang-Uploader mit gut bedienbarer mobiler Darstellung, lokaler Warteschlange, Bildvarianten, Ziel-Presets, einmaligen Uploads und resumierbaren Uploads für große Dateien bauen.

**Architecture:** `FormFileUploader` wird zum gemeinsamen Orchestrator für Quellen, lokale Warteschlange und Uploadstatus. Ein separater Bildeditor erzeugt aus jedem Variantenstatus ein finales lokales `File`; direkte Uploads und Chunk-Uploads verwenden danach dieselbe Ziel- und Titelstruktur. Das Backend erhält einen generischen, autorisierten Upload-Session-Service für polymorphe Anhänge sowie einen kleinen Inbox-Download-Endpunkt.

**Tech Stack:** Vue 3 Options API, Bootstrap/MDI, `vue-advanced-cropper`, Axios, Laravel, PHPUnit, Vitest, konfigurierbarer Laravel-Storage/MinIO.

**Spec:** `docs/superpowers/specs/2026-11-08-gemeinsamer-anhang-uploader-design.md`

## Global Constraints

- Bildbearbeitung und Variantenbildung erfolgen vor jedem Server-Upload lokal.
- Die gesamte sichtbare Uploader-Fläche akzeptiert Desktop-Drag-and-drop; mobile Geräte erhalten große Touch-Aktionen sowie native Datei-/Kameraauswahl, ohne den Desktop-Workflow zu verdichten.
- Eine Quelle kann mehrere unabhängige Varianten mit eigenem Titel, Preset, `cut` und Transformationszustand erzeugen.
- Der Werbung-Tab verwendet eine gemeinsame Quellen-/Warteschlangen-Zone und Zielkarten für alle `config.images.cuts`-Presets.
- Jeder Eintrag zeigt Prozent-/Statusfortschritt; die Warteschlange zeigt zusätzlich den Gesamtfortschritt.
- Kleine Dateien nutzen Multipart; ab 10 MiB entscheidet eine zentrale Konfiguration für den Chunk-Upload.
- Chunks werden im konfigurierten Objektspeicher gehalten und nach Abschluss atomar als Attachment übernommen.
- Tests verwenden eine explizit isolierte Testdatenbank; keine Standard-Dev- oder Produktionsdatenbank.
- Neue/geänderte Vue-/JavaScript-Deklarationen erhalten JSDoc; neue/geänderte PHP-Deklarationen erhalten PHPDoc.

## Review Focus

- Ein Browser-`change`-Event mit mehreren Dateien erzeugt genau einen Warteschlangeneintrag je Datei und keine Doppeluploads.
- Dieselbe lokale Quelle kann mit zwei verschiedenen Ziel-Presets und Zuschnitten als zwei Attachments gespeichert werden.
- Ein Upload-Fehler lässt nur die betroffene Variante wiederholen und dupliziert keine erfolgreich gespeicherten Attachments.
- Ein Netzabbruch in der Mitte eines Chunk-Uploads setzt beim fehlenden Chunk fort und akzeptiert keine fremde oder doppelte Session.
- Direkte und Chunk-Uploads melden Fortschritt korrekt pro Variante und aggregiert, ohne nach einem Fehler auf null zu springen.
- Mobile Ansichten behalten Titel, Vorschau, Bearbeiten, Entfernen, Wiederholen und „Alle hochladen“ ohne horizontales Scrollen bedienbar.

### Task 1: Gemeinsame Upload-Modelle und lokale Quellen

**Files:**
- Create: `src/resources/js/components/Ui/forms/attachmentUploadQueue.js`
- Modify: `src/resources/js/components/Ui/forms/FormFileUpload.vue`
- Modify: `src/resources/js/components/Ui/forms/FormFileUploader.vue`
- Test: `src/tests/js/components/Ui/forms/attachmentUploadQueue.test.js`
- Test: `src/tests/js/components/Ui/forms/FormFileUpload.test.js`

**Interfaces:**
- Produces `createUploadItem(file, metadata)`, `duplicateUploadItem(item)` and
  `appendFiles(queue, files)` as pure, testable queue operations.
- `FormFileUpload` emits all selected/dropped local files once, resets its
  input, and keeps source events for URL, Pixabay and Inbox.
- `FormFileUploader` owns queue entries with `id`, `sourceId`, `file`, `title`,
  `previewUrl`, `status`, `error`, `cut` and optional `preset`.

- [ ] **Step 1: Write failing tests** for multiple files, source/variant IDs,
  duplicate variants, input reset and full-surface drop handling.
- [ ] **Step 2: Run the focused Vitest file** and verify the missing queue
  behavior fails for the intended reason.
- [ ] **Step 3: Implement the pure queue helpers and source-picker event
  contract** without performing an upload.
- [ ] **Step 4: Run the focused tests** and verify they pass.
- [ ] **Step 5: Add URL/Pixabay conversion to local `File` objects** with
  visible per-item errors; add Inbox download integration in Task 3.
- [ ] **Step 6: Run the source-picker tests** and the existing affected JS
  tests.

### Task 2: Local image editor and target variants

**Files:**
- Create: `src/resources/js/components/Ui/forms/AttachmentImageEditor.vue`
- Modify: `src/resources/js/components/Ui/forms/FormFileUploader.vue`
- Modify: `src/resources/js/components/ServiceEditor/tabs/AdsTab.vue`
- Test: `src/tests/js/components/Ui/forms/AttachmentImageEditor.test.js`
- Test: `src/tests/js/components/Ui/forms/FormFileUploader.test.js`

**Interfaces:**
- `AttachmentImageEditor` accepts `file`, `width`, `height`, `preset` and
  `initialTransform`; emits `apply(File, transform)` and `cancel`.
- Editor actions expose rotate left/right, mirror horizontal/vertical, reset,
  zoom and crop; output is a local blob/file and never an HTTP request.
- `FormFileUploader` accepts optional `presets`/`cut` target definitions and
  can duplicate a queued source into a new independently editable variant.

- [ ] **Step 1: Write failing editor tests** for rotation, both mirror
  directions, reset, preset aspect ratio and local `File` output.
- [ ] **Step 2: Run the focused editor tests** and verify the transform API is
  absent or fails.
- [ ] **Step 3: Implement the Influence-inspired responsive editor** around
  `vue-advanced-cropper`, with large touch controls and a wrapping toolbar.
- [ ] **Step 4: Run editor tests** and verify all transformations and output
  metadata pass.
- [ ] **Step 5: Add queue actions for editing and duplicating a source into a
  second target variant; display title, target preset, status and preview.
- [ ] **Step 6: Wire `config.images.cuts` in `AdsTab` as named presets with
  dimensions, aspect ratio and slug/cut; redesign the tab around one shared
  source/queue area and responsive target cards with add/edit/replace/duplicate
  and delete actions.
- [ ] **Step 7: Run all affected JS tests and verify the desktop layout plus
  the mobile fallback with a narrow viewport test.

### Task 3: Local Inbox acquisition and direct upload orchestration

**Files:**
- Modify: `src/app/Http/Controllers/Api/InboxController.php`
- Modify: `src/routes/api/Inbox.php`
- Modify: `src/resources/js/components/Ui/forms/FormFileUploader.vue`
- Modify: `src/app/Traits/HandlesAttachmentsTrait.php`
- Test: `src/tests/Feature/InboxFeatureTest.php`
- Test: `src/tests/Feature/AttachmentFeatureTest.php`

**Interfaces:**
- Add authenticated `GET /api/inbox/{filename}` with path validation and a
  streamed/download response for allowed Inbox files.
- `FormFileUploader` converts Inbox responses to local `File` objects before
  inserting them into the queue.
- Direct upload sends one `attachments[0]`, matching `attachment_text[0]`
  and optional `cut` per queue variant, then merges the returned attachment
  collection once.

- [ ] **Step 1: Write failing feature tests** for allowed Inbox download,
  traversal rejection and direct multi-attachment title/cut mapping.
- [ ] **Step 2: Run the focused PHP tests** with isolated SQLite and verify
  the new endpoint/contract fails.
- [ ] **Step 3: Implement secure Inbox download and document its PHPDoc.
- [ ] **Step 4: Fix `HandlesAttachmentsTrait` to tolerate missing title keys,
  preserve title-to-file mapping, and avoid duplicate processing.
- [ ] **Step 5: Implement sequential direct upload with one request per queue
  variant, Axios `onUploadProgress`, explicit status/percent transitions,
  retry, and emitted collection merge.
- [ ] **Step 6: Run focused PHP and JS tests and inspect diff for accidental
  server-side original uploads.

### Task 4: Resumable upload sessions for large files

**Files:**
- Create: `src/database/migrations/2026_11_08_000000_create_attachment_upload_sessions_table.php`
- Create: `src/app/Models/AttachmentUploadSession.php`
- Create: `src/app/Services/AttachmentUploadService.php`
- Create: `src/app/Http/Controllers/Api/AttachmentUploadController.php`
- Create: `src/routes/api/AttachmentUploads.php`
- Modify: `src/routes/api.php`
- Modify: `src/config/filesystems.php` or the project’s upload config location
- Test: `src/tests/Unit/AttachmentUploadServiceTest.php`
- Test: `src/tests/Feature/AttachmentUploadFeatureTest.php`

**Interfaces:**
- `POST /api/attachment-uploads` starts an authorized session for a concrete
  attachable target and returns session ID, chunk size and already received
  chunk indexes.
- `PUT /api/attachment-uploads/{session}/chunks/{index}` accepts one chunk,
  verifies size/checksum, is idempotent for the same bytes, and rejects
  mismatches.
- `POST /api/attachment-uploads/{session}/complete` atomically assembles the
  object and creates exactly one Attachment with title and optional `cut`.
- `DELETE /api/attachment-uploads/{session}` aborts and deletes temporary
  chunks; scheduled cleanup removes expired sessions.

- [ ] **Step 1: Write failing unit/feature tests** for authorization,
  threshold metadata, idempotent chunks, missing chunk rejection, complete,
  abort and cleanup.
- [ ] **Step 2: Run focused PHP tests** against isolated SQLite and fake
  storage; verify the session service is missing/failing.
- [ ] **Step 3: Add the session schema/model and service with central
  threshold/chunk-size configuration, checksum validation and expiry.
- [ ] **Step 4: Add controller routes, authorization against the target
  attachable, atomic finalization and safe cleanup.
- [ ] **Step 5: Run the focused feature/unit tests** and verify exactly one
  Attachment is created for a completed session.
- [ ] **Step 6: Add the Vue chunk client: threshold selection, sequential
  chunks, retry, resume from returned indexes, abort, per-item progress and
  aggregate progress for the active browser session. Browser reload
  persistence is deferred because transformed local `File` objects need a
  separate IndexedDB lifecycle.
- [ ] **Step 7: Run JS and PHP focused tests, including a simulated network
  failure between chunks.

### Task 5: Integrated attachment collection UI

**Files:**
- Modify: `src/resources/js/components/Ui/elements/AttachmentList.vue`
- Modify: `src/resources/js/components/Ui/elements/Attachment.vue`
- Create: `src/resources/js/components/Ui/elements/AttachmentLightbox.vue`
- Modify: all existing `FormFileUploader` consumers only where target props or
  event compatibility require it, including `AttachmentsTab.vue` and the rite/
  absence editors.
- Test: `src/tests/js/components/Ui/elements/AttachmentList.test.js`
- Test: `src/tests/js/components/Ui/elements/AttachmentLightbox.test.js`
- Test: `src/tests/js/components/Ui/elements/Attachment.test.js`

**Interfaces:**
- `AttachmentList` watches `modelValue`, uses `attachment.id` as Vue key,
  updates local state after deletion and preserves existing `v-model` output.
- `AttachmentList` optionally accepts the shared uploader props and renders
  the uploader, queue and current attachments inside one full-area dropzone.
- `AttachmentLightbox` accepts image attachments and emits close/previous/
  next/download actions without owning upload state.
- `FormFileUploader` remains drop-in compatible with existing `v-model`
  consumers while exposing optional target presets.

- [ ] **Step 1: Write failing integrated UI tests** for prop synchronization,
  stable IDs, immediate deletion, full-area drop handling, ignored drops on
  controls, image/file/error presentation and mobile actions.
- [ ] **Step 2: Run focused list tests** and verify the current cached/index
  behavior fails.
- [ ] **Step 3: Implement the reactive list and accessible responsive cards,
  embedding `FormFileUploader` when upload props are present.
- [ ] **Step 4: Implement `AttachmentLightbox` with responsive preview,
  Escape/touch closing, download/open actions and image-only navigation.
- [ ] **Step 5: Run existing AttachmentList/Attachment/Lightbox tests and all
  affected uploader tests.
- [ ] **Step 6: Verify AdsTab can create two variants from one source, each
  with its preset and `cut`, and that existing attachments remain filtered and
  deletable; verify the redesigned target cards keep each preset’s dimensions,
  preview and actions.

### Task 6: Documentation, quality checks and handoff

**Files:**
- Modify: `docs/superpowers/specs/2026-11-08-gemeinsamer-anhang-uploader-design.md`
- Modify: `docs/admin/installation-und-migration.md` if upload-session storage
  or cleanup configuration changes operator behavior
- Modify: `src/CHANGELOG.md` if the project release process requires it

- [ ] **Step 1: Document configuration, object-storage requirements, expiry,
  chunk threshold and mobile/browser behavior.
- [ ] **Step 2: Run `git diff --check`.
- [ ] **Step 3: Run focused Vitest tests and the complete JS test suite through
  Docker.
- [ ] **Step 4: Run focused PHP tests with explicit isolated DB variables,
  then the relevant Docker quality/build checks.
- [ ] **Step 5: Perform a real browser check of desktop drag/drop, camera/file
  selection, two Ads variants, local crop/rotation/mirroring, direct upload,
  error retry and narrow mobile layout.
- [ ] **Step 6: Review the diff for secrets, generated files, unrelated edits,
  and correct German Conventional Commit scope before requesting commit.
