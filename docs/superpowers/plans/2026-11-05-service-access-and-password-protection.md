# Gottesdienst-Rechte und Passwortschutz – Implementierungsplan

> **Für agentische Bearbeitung:** Die Schritte werden sequenziell ausgeführt und jeweils mit isolierten Tests geprüft.

**Ziel:** Gottesdienst-Pfarrer:innen erhalten Schreibrechte, Leseberechtigte können die Liturgie lesen, Selbstzuordnung ist nur über die gezielte Prädikant:innen-Ausnahme möglich, und Profilbearbeitungen löschen niemals versehentlich einen Passwort-Hash.

**Architektur:** Die bestehende `ServicePolicy` bleibt die zentrale Stelle für Gottesdienstrechte. Leseaktionen des Liturgiecontrollers autorisieren über `view`, Schreibaktionen weiter über `update`; die bestehende Passwort-Mutatorlogik bleibt unverändert und die Kontolöschung wird an eine explizite Änderung gebunden.

**Technik:** Laravel Policies, FormRequests, PHPUnit mit SQLite-`:memory:` über `./planer dev test`.

**Issue:** https://github.com/pfarr-tools/pfarrplaner/issues/37

## Review-Fokus

- Pastor-Zuordnung über Pivot-Kategorie `P` muss unabhängig von Stadt-Schreibrechten Schreibzugriff geben.
- Leserechte dürfen Liturgie anzeigen, aber keine Liturgieänderung ermöglichen.
- Bestehender Hash bei leerem oder fehlendem E-Mail-Feld und unverändertem Passwort muss erhalten bleiben.
- Die Profilbearbeitung darf keine implizite Kontodeaktivierung mit Passwortlöschung auslösen.

### Aufgabe 1: Regressionstests

- [x] ServicePolicy-Test für Pfarrer-Zuordnung ohne Stadt-Schreibrecht ergänzen.
- [x] Feature-Tests für Liturgie-Lesezugriff und weiterhin verweigerten Schreibzugriff ergänzen.
- [x] Profiltest für bestehendes Passwort ohne E-Mail-Adresse ergänzen.
- [x] Tests isoliert ausführen und erwartete rote Befunde dokumentieren.

### Aufgabe 2: Autorisierung korrigieren

- [x] ServicePolicy auf die gewünschte Zuordnung ausrichten und die Tests grün machen.
- [x] Leseaktionen des `LiturgyEditorController` auf `view`, Schreibaktionen auf `update` umstellen.
- [x] Prüfen, dass Block-/Item-Write-Endpunkte weiterhin `update` verwenden.
- [x] Generische Teilnehmerzuordnung auf `update` beschränken und `claim-predicant` als Rollen-/Bedarfsausnahme testen.

### Aufgabe 3: Passwortschutz korrigieren

- [x] Die implizite Passwortlöschung in `UserController::update` entfernen.
- [x] Profil- und Passworttests ausführen.

### Aufgabe 4: Abschluss

- [ ] Pint, PHPStan, PHPDoc-Check, fokussierte PHPUnit-Tests und `git diff --check` ausführen.
- [ ] Dokumentation/Arbeitsledger aktualisieren, Review durchführen, Commit mit `Refs #37` erstellen und Branch pushen.
