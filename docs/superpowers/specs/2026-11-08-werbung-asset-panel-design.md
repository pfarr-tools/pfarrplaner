# Werbung-Asset-Panel – Design

## Ziel

Der Werbung-Tab wird zu einer einzigen Arbeitsfläche für Quellen, Ziel-Cuts
und veröffentlichungsreife Varianten. Ein vorhandenes Bild kann als Quelle für
weitere Cuts wiederverwendet werden; jede Zielkarte zeigt unmittelbar, ob der
Cut aktiviert, belegt, bearbeitbar oder löschbar ist.

## Bedienmodell

- Eine gemeinsame Upload-Zone bietet lokale Dateien, Kamera, URL, Pixabay und
  Ablage an.
- Jede Quelle öffnet vor dem Upload genau einen Dialog:
  - Bilder: Titel, Zielauswahl, Cropper, Rotation, Spiegelung, Zoom und
    Vorschau.
  - Andere Dateien: Titel und, soweit möglich, Dateivorschau.
- Die Warteschlange zeigt nur Status, Preview, Ziel-Cut und Aktionen; Titel und
  Zielauswahl werden im Dialog bearbeitet.
- Nach erfolgreichem Upload wird der Warteschlangeneintrag entfernt.
- Ein bestehender Ziel-Cut kann direkt bearbeitet, ersetzt, gelöscht oder als
  Ausgangspunkt für einen neuen Ziel-Cut verwendet werden.

## Zielkarten

Die Karten werden ausschließlich aus `config.images.cuts` erzeugt und zeigen:

- Name, Pixelgröße und Seitenverhältnis;
- Aktiviert/deaktiviert für die jeweilige Veröffentlichung;
- aktuelle Preview oder einen klaren Leerzustand;
- Bearbeiten, Ersetzen, Löschen und „Für neuen Cut verwenden“;
- den tatsächlich verwendeten `cut` in der Veröffentlichungsübersicht.

„Verfügbare Bilder“ entfällt als eigene Sektion. Ungecutte Anhänge bleiben als
Quellen im gemeinsamen Panel erreichbar, werden aber nicht doppelt als zweite
Galerie dargestellt.

## Technische Entscheidungen

- `AdsTab` besitzt die Zielkarten und steuert nur Ziel-/Veröffentlichungslogik.
- `FormFileUploader` besitzt Quellen, Dialog, lokale Varianten und Uploadstatus.
- `AttachmentImageEditor` rendert lokal auf Canvas. Transformationszustände
  werden pro Ziel gehalten; der Zoom erlaubt Werte kleiner und größer als 1.
- Transparente Bilder erhalten im Cropper und in der Lightbox ein Schachbrett,
  PNG-Ausgaben behalten Transparenz; JPEG-Ausgaben erhalten einen weißen
  Hintergrund.
- Die Lightbox navigiert über alle Bildanhänge der aktuellen Liste, bietet
  sichtbare Chevrons, Tastaturpfeile und `Ctrl`+Mausrad-Zoom.
- Löschungen aktualisieren Attachment-Liste und `v-model` unmittelbar und
  verwenden die bestehende autorisierte Service-Route.

## Nichtziele

- Keine automatische Veröffentlichung.
- Keine zusätzliche unbearbeitete Kopie beim Erzeugen eines Ziel-Cuts.
- Keine Änderung der serverseitigen Cut-Semantik außerhalb der notwendigen
  Ersetzung-/Löschlogik.
