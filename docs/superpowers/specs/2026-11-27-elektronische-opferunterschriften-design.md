# Design: Elektronische Unterschriften bei der externen Opfereingabe

## Ziel

Die externe, signierte Opfereingabe soll eine vollständige elektronische
Unterzeichnung ermöglichen. Eine verbindliche Übermittlung erzeugt einen
unveränderlichen Datenstand, verknüpft ihn kryptografisch mit den
Unterschriften und archiviert die daraus erzeugte Opferurkunde. Bestehende
Opfereingaben und bereits erzeugte Opferurkunden bleiben kompatibel.

## Umfang

### Ziele

- konfigurierbare elektronische Unterzeichnung je Kirchengemeinde;
- ein responsives Signaturfeld je erfasster Opferzähler:in;
- serverseitige Validierung der Signaturen und des signierten Datenstands;
- atomare Finalisierung mit Snapshot, Prüfsummen, Manifest und PDF-Archiv;
- unveränderliche Auslieferung archivierter Opferurkunden;
- Revisionen für spätere Korrekturen mit vollständiger Historie;
- private Speicherung, bestehende Mandanten- und Berechtigungsgrenzen sowie
  automatisierte Regressionstests.

### Nichtziele

- keine qualifizierte elektronische Signatur (QES);
- keine biometrische Auswertung von Druck, Geschwindigkeit oder Rohbewegungen;
- keine nachträgliche elektronische Signatur historischer Abrechnungen;
- keine Änderung des bisherigen Ablaufs, wenn die Funktion für eine Gemeinde
  deaktiviert ist.

## Vorgehensvarianten

1. **Ein großer End-to-End-Schritt:** Alle Datenbank-, UI-, PDF-,
   Archivierungs- und Revisionsänderungen werden gemeinsam umgesetzt. Das
   reduziert Zwischenzustände, erschwert aber die Prüfung und erhöht das
   Risiko für bestehende Opfereingaben.
2. **Vertikale, rückwärtskompatible Ausbaustufen (gewählt):** Zuerst werden
   Signaturfeld und Finalisierungsvertrag eingeführt, anschließend Manifest
   und Archiv-PDF, danach Revisionen und Berechtigungen. Jede Stufe erhält
   fokussierte Tests und lässt den bisherigen unsignierten Ablauf aktiv.
3. **Separater Signaturdienst:** Signaturbilder und Integritätsdaten würden in
   einem zusätzlichen Dienst verwaltet. Das widerspricht der Docker-first-
   Architektur und würde neue Betriebs-, Backup- und Schlüsselverwaltung
   einführen.

Variante 2 wird gewählt, weil sie die vorhandene `Service`-basierte
Opfereingabe und PDF-Erzeugung schrittweise erweitert, ohne historische Daten
zu migrieren oder einen zusätzlichen Dienst vorauszusetzen.

## Technischer Entwurf

Die Signaturbilder werden als validierte PNG-Dateien im privaten Storage unter
einem revisionsbezogenen Pfad abgelegt. Ein neuer, unveränderlicher
Opferabrechnungsdatensatz enthält den kanonisch serialisierten Snapshot,
Namen, Signaturpfade und SHA-256-Prüfsummen. Ein signiertes Manifest verbindet
Snapshot, Signaturen und archivierte PDF-Datei. Die Signatur erfolgt mit einem
serverseitig verwalteten Anwendungsschlüssel; der verwendete Schlüssel- bzw.
Versionsbezeichner wird im Manifest gespeichert, damit spätere
Schlüsselrotationen alte Belege prüfbar lassen.

Die Finalisierung läuft in einer Datenbanktransaktion. Temporäre Dateien
werden zunächst geschrieben und geprüft; erst nach erfolgreicher PDF-Erzeugung
und Manifestbildung wird die Revision als verbindlich markiert. Wiederholte
Übermittlungen erkennen eine bereits finalisierte Revision und erzeugen keinen
zweiten Beleg.

Die öffentliche Seite erhält die Signaturfelder als wiederverwendbare
Vue-Komponente. Bei Änderung abrechnungsrelevanter Eingaben werden die Felder
geleert und ein Hinweis angezeigt. Die Canvas-Größe wird bei Resize- und
Orientierungsänderungen angepasst, wobei die gespeicherte Vektordarstellung
der Komponente vorübergehend neu gerendert wird; an den Server werden nur
fertige PNG-Bilder übertragen.

Die bisherige manuelle Unterzeichnung auf der Opferurkunde bleibt erhalten,
wenn elektronische Unterzeichnung deaktiviert ist. Für signierte Revisionen
werden ausschließlich archivierte PDFs ausgeliefert.

## Tests und Qualitätsnachweis

- Vue-Tests für Signaturerfassung, Löschen, Resize, Pflichtfelder und Reset;
- Feature-Tests für Validierung, Stadtgrenze, doppelte Übermittlung,
  Snapshot/Hashes, Transaktionsfehler und private Downloads;
- Tests für Revisionen und serverseitigen Änderungsschutz;
- PDF-/Manifest-Test mit wiederholtem Downloadvergleich;
- Docker-basierte Format-, Analyse-, Lint-, Dokumentations- und Quality-Gates.

## Offene betriebliche Annahme

Die konkrete Bereitstellung eines rotierbaren Signaturschlüssels wird über die
bestehende verschlüsselte Konfiguration gelöst und im Administrationshandbuch
dokumentiert. Ein produktiver Schlüssel darf weder im Repository noch in
Logs erscheinen.
