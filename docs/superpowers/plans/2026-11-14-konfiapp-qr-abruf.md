# KonfiApp-QR-Codes korrekt abrufen Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Bereits bei KonfiApp gespeicherte QR-Codes sollen für den konkreten Eventtyp zuverlässig abgerufen werden.

**Architecture:** Der HTTP-Transport validiert nur HTTP-Fehler. Die bestehende `requestData()`-Methode prüft weiterhin das generische `data`-Format; `listQRCodes()` verarbeitet das spezielle KonfiApp-Format mit `detail` separat und erhält die Eventtyp-ID als Parameter.

**Tech Stack:** Laravel, Guzzle, PHPUnit/Mockery.

**Spec:** GitHub Issue #83.

## Constraints

- Keine echten QR-Codes in KonfiApp erzeugen, ändern oder löschen.
- Keine produktiven Daten oder `.env`-Dateien ändern.
- Unabhängige Arbeitsbaumänderungen nicht anfassen.

## Tasks

- [x] Regressionstest für HTTP 200 mit `detail` und Eventtyp-Query schreiben und rot ausführen.
- [x] Transport-/Payload-Trennung und parametrisierten QR-Listenabruf implementieren.
- [x] Fokussierte Tests, Formatprüfung und `git diff --check` ausführen.
- [ ] Issue mit Commit und Verifikation kommentieren und schließen.
