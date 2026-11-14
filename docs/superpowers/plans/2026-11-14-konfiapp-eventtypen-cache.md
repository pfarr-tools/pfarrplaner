# KonfiApp-Eventtypen cachen und Volltext-Downloads abfedern Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** KonfiApp-Netzwerk- und API-Fehler dürfen den Volltext-Download nicht mehr verhindern.

**Architecture:** Die zentrale KonfiApp-Integration cached Eventtypen stadtbezogen und verwendet bei temporären Fehlern den letzten Wert. Der Volltext behandelt fehlende optionale Metadaten als leere Liste; QR-Code und DOCX bleiben unabhängig davon verfügbar.

**Tech Stack:** Laravel, Laravel Cache, Guzzle, PHPUnit/Mockery.

**Spec:** GitHub Issue #82.

## Global Constraints

- Keine produktiven Daten oder `.env`-Dateien ändern.
- API-Schlüssel niemals loggen.
- Tests nur über die Docker-Testisolation ausführen.
- Unabhängige Änderungen im Arbeitsbaum nicht anfassen.

## Review Focus

- Cache-Hit: kein zweiter API-Aufruf innerhalb der TTL.
- API-Fehler mit vorhandenem Cache: letzter Eventtyp-Wert bleibt verfügbar.
- API-Fehler ohne Cache: leere Collection statt Exception.
- Guzzle-Konfiguration: IPv4 ohne veraltete CURL-Option.
- Logging: weder Token noch Request-Header enthalten Geheimnisse.

### Task 1: KonfiApp-Integration absichern

**Files:**
- Modify: `src/app/Integrations/KonfiApp/KonfiAppIntegration.php`
- Test: `src/tests/Unit/Integrations/KonfiApp/KonfiAppIntegrationUnitTest.php`

- [x] Failing tests für neue IPv4-Konfiguration, Cache-Hit, stale fallback und leeren Cache schreiben.
- [x] Tests isoliert ausführen und die erwarteten Fehlschläge prüfen.
- [x] Cache-Schlüssel, TTL und Fehlerfallback zentral in `listEventTypes()` implementieren.
- [x] Logging-Kontexte ohne API-Schlüssel und Request-Header ausgeben.
- [x] Tests erneut ausführen.

### Task 2: Volltext-Verhalten und Qualität

**Files:**
- Inspect: `src/app/Liturgy/LiturgySheets/FullTextLiturgySheet.php`
- Modify only if required by the focused regression test.

- [x] Der bestehende FullText-Integrationspfad erhält durch die zentrale Methode eine leere Eventtypenliste ohne Exception.
- [x] Fokussierte Tests, `git diff --check` und die relevanten Docker-Qualitätsprüfungen ausführen.
- [ ] Issue mit Ergebnis, Tests und Commit referenzieren, danach nach erfolgreicher Verifikation schließen.
