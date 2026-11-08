# Gemeinsamer Anhang-Uploader – Design

## Absicht

Die Upload-Oberflächen für Anhänge sollen in Werbung, Veranstaltungen,
Abwesenheiten und Riten einheitlich funktionieren. Dateien werden zunächst
lokal in einer Warteschlange gesammelt. Dort können Titel vergeben,
Bilddateien zugeschnitten, gezoomt, gedreht und gespiegelt werden, bevor
überhaupt ein Server-Upload startet. Das behebt insbesondere die doppelte
Verarbeitung von Mehrfachauswahl und die bisherige Erzeugung eines zweiten
Anhangs beim Zuschneiden.

## Ziele

- Einen gemeinsamen Uploader für alle bestehenden
  `FormFileUploader`-Anwendungsfälle verwenden.
- Ein lokales Original als mehrere unabhängige Varianten verwalten können,
  insbesondere für die verschiedenen Werbe-Zuschnitte.
- Lokale Dateien, Kamera, URL, Pixabay und Ablage in dieselbe Warteschlange
  überführen.
- Titel direkt pro Warteschlangeneintrag bearbeiten; keine `prompt()`-Dialoge.
- Eine großflächige Drag-and-drop-Zone mit verständlichem Drag-over-Zustand
  anbieten.
- Die komplette Warteschlange und Bildbearbeitung auf kleinen Displays und
  mit Touchbedienung gut nutzbar machen.
- Bilder vor dem Upload mit Crop-UI nach dem Vorbild von Influence bearbeiten:
  Zoom, Verschieben, freie Auswahl bzw. bestehendes Seitenverhältnis,
  90° links/rechts, horizontales und vertikales Spiegeln sowie Rücksetzen.
- Genau einen Upload pro Warteschlangeneintrag ausführen und die entstandenen
  Anhänge reaktiv in der Dateiliste anzeigen.
- Bestehende Attachment-Routen und die Titel-/Mehrfachdatei-Semantik des
  Backends beibehalten, soweit kein kleiner, notwendiger API-Schritt fehlt.
- Dateilisten mit stabilen Attachment-IDs, reaktivem Löschen, Bildvorschau,
  Fehlermeldung und verständlichem Leerzustand verbessern.
- Liste, Uploader und Warteschlange als einen gemeinsamen Anhangsbereich
  darstellen: Die gesamte Fläche ist Dropzone, während Dateiaktionen und
  Vorschauen weiterhin separat klickbar bleiben.
- Eine verbesserte Lightbox mit großer Vorschau, Tastatur-/Touch-Schließen,
  Download und sicherer Navigation zwischen Bildanhängen anbieten.
- Den Upload-Fortschritt pro Datei/Variante und zusätzlich als Gesamtfortschritt
  verständlich anzeigen.
- Den Werbung-Tab als Zielarbeitsfläche für Bildvarianten neu strukturieren,
  statt für jedes Ziel einen isolierten Mini-Uploader zu zeigen.

## Nichtziele

- Keine serverseitige Bildbearbeitung.
- Kein neues Dateiformat- oder Berechtigungssystem.
- Keine Änderung der separaten Einzelbildfelder, sofern sie nicht den
  gemeinsamen Anhang-Uploader verwenden.
- Keine automatische Veröffentlichung oder Speicherung einer unbearbeiteten
  Originaldatei zusätzlich zur bearbeiteten Datei.

## Anti-Ziele

- Keine `prompt()`-Eingaben während der Dateiauswahl.
- Keine Upload-Anfragen für jedes einzelne `change`-Event, wenn der Browser
  mehrere Dateien liefert.
- Kein erneutes Anlegen eines Attachments nur wegen einer Bildbearbeitung.
- Keine Verwendung von Array-Indizes als Vue-Schlüssel für persistente Dateien.

## Geprüfte Ansätze

1. **Sofortupload je Datei mit nachgelagertem Editor:** kleinste Änderung,
   aber Bildbearbeitung geschieht weiterhin nach dem Upload und Mehrfach-
   sowie Abbruchfälle bleiben schwer verständlich.
2. **Nur `FormFileUpload` als neuer Picker:** würde die Auswahl vereinheitlichen,
   lässt aber die mehrfach vorhandene Upload- und Editorlogik in den Eltern-
   komponenten bestehen.
3. **Gemeinsame lokale Upload-Warteschlange in `FormFileUploader`:** eine
   zentrale Zustandsmaschine für alle Quellen, lokale Bildbearbeitung vor dem
   Upload, ein kontrollierter Upload je Eintrag und ein einheitliches
   Dateilisten-Verhalten. Dieser Ansatz ist ausgewählt, weil er die beiden
   Issues und den Doppelupload an derselben Stelle löst.

## Entwurf

### Warteschlange und Quellen

`FormFileUploader` übernimmt die Warteschlange. Jeder Warteschlangeneintrag
erhält eine stabile lokale Varianten-ID, eine gemeinsame lokale Quell-ID, die
ursprüngliche `File`-Instanz, den editierbaren Titel, eine lokale Vorschau-URL,
Status (`ready`, `uploading`, `done`, `error`) und eine optionale
Bildbearbeitung. Eine Variante kann aus einem bestehenden
Warteschlangeneintrag dupliziert werden; danach besitzt sie einen eigenen
Zuschnitt, eigene Transformationen, einen eigenen Titel und ein eigenes
Uploadziel. Dadurch kann ein Foto beispielsweise als quadratischer
Newsletter-Zuschnitt und als breiter Homepage-Zuschnitt hochgeladen werden,
ohne es erneut auswählen oder vorher hochladen zu müssen.

Die lokale Dateiauswahl und das Drop-Event
fügen alle Dateien in einem Durchlauf hinzu; der Input wird anschließend
zurückgesetzt, damit dieselbe Datei erneut ausgewählt werden kann, ohne ein
zweites altes Event zu verarbeiten.

URL- und Pixabay-Quellen werden als Blob in eine lokale `File`-Instanz
überführt. Ablage-Dateien werden über einen kleinen authentifizierten
Download-Endpunkt in eine lokale `File`-Instanz überführt; das bisherige
serverseitige Verschieben wird erst nach Bestätigung des Uploads ausgeführt
oder bleibt für nicht-bildliche Ablage-Dateien als bestehender Fallback
erhalten, falls der Browser die Datei nicht lesen kann.

Die Dropzone umfasst die gesamte sichtbare Uploader-Karte. Während eines
Drag-over werden nur der Zustand und eine klare Handlungsaufforderung
geändert; das Drop-Event wird nicht zusätzlich an eine darunterliegende
Dateieingabe weitergereicht.

### Bildeditor

Ein eigener, wiederverwendbarer Editor innerhalb von `FormFileUploader` nutzt
`vue-advanced-cropper` und orientiert sich beim Layout am Influence-Editor:
große Vorschaufläche, Zoom-Regler, kompakte Werkzeugleiste und mobile
Bedienbarkeit. Die Transformationszustände werden pro Warteschlangeneintrag
gehalten. Vorgaben sind:

- 90° nach links und rechts drehen;
- horizontal und vertikal spiegeln;
- Zoom und Verschieben;
- Crop-Seitenverhältnis aus einem Ziel-Preset oder den bestehenden
  `width`-/`height`-Props;
- Bearbeitung zurücksetzen, Original übernehmen oder Änderung anwenden.

Beim Anwenden wird ausschließlich lokal ein Blob/`File` erzeugt. Die
Warteschlange zeigt sofort die bearbeitete Vorschau. Erst „Alle hochladen“
sendet die finale Datei mit dem Titel und dem optionalen Ziel-`cut` an die
bestehende Upload-Route.

Für kontextabhängige Ziele erhält der Uploader optionale Zieldefinitionen.
Im Werbung-Tab entspricht jede Zieldefinition einem vordefinierten Preset mit
lesbarem Namen, Pixelgröße, Aspect Ratio und bestehendem `cut`. Aus einer
Quelle können mehrere Varianten angelegt und jeweils einem Preset zugeordnet
werden. Bereits belegte Ziele bleiben sichtbar; der Uploader bietet für sie
eine neue Variante oder eine Ersetzung nach expliziter Bestätigung an. Die
bestehende Filterung des Werbung-Tabs nach `cut` bleibt dabei kompatibel.

### Werbung-Tab

Der Werbung-Tab erhält eine gemeinsame Quellen- und Warteschlangen-Zone sowie
darunter bzw. daneben eine Zielkarten-Ansicht. Jede Karte stammt aus
`config.images.cuts` und zeigt Zielname, Pixelgröße, Aspect Ratio, Status und
die aktuelle Vorschau. Noch nicht belegte Ziele bieten direkt „Variante
hinzufügen“; belegte Ziele bieten Bearbeiten, Ersetzen, Duplizieren und
Löschen. „Duplizieren“ übernimmt die lokale Quelle, öffnet sie aber mit einem
eigenen Zuschnitt und dem ausgewählten Ziel-Preset.

Auf dem Desktop nutzt die Ansicht die verfügbare Breite für Quellen und
Zielkarten. Auf kleinen Ansichten werden Quellen und Zielkarten untereinander
angeordnet, ohne Funktionen hinter Hover-Zuständen zu verstecken. Die bisher
unter „Verfügbare Bilder“ angezeigten unbearbeiteten Anhänge bleiben als
Quellenbibliothek erreichbar; Zuschnitt-Anhänge werden ausschließlich in den
Zielkarten dargestellt.

### Upload und Ergebnis

Der Upload läuft sequenziell durch die Warteschlange. Ein Eintrag wird nur
einmal markiert und gesendet; währenddessen sind die Upload-Aktionen gesperrt,
aber Titel und Entfernen für noch nicht gesendete Einträge bleiben verständlich
behandelt. Jeder Eintrag zeigt seinen Zustand, Prozentwert und einen
Fortschrittsbalken; der Bereich zeigt zusätzlich den Fortschritt über alle
uploadbaren Varianten. Antworten aller Einzeluploads werden zusammengeführt und als
`update:modelValue` bzw. bestehendes `input`-Event an die Eltern weitergegeben.
Fehler bleiben am Eintrag sichtbar und können einzeln erneut versucht werden.

Für kleine Dateien wird der bestehende Multipart-Upload verwendet. Ab einer
konfigurierbaren Schwelle (initial 10 MiB) nutzt derselbe Uploader eine
persistierte Upload-Session mit festen Chunks, Chunk-Prüfsummen,
Wiederholungen, Fortschrittsanzeige und Fortsetzen nach einem
Netzabbruch. Die Server-Seite erhält dafür authentifizierte Start-,
Chunk-, Abschluss- und Abbruch-Endpunkte. Chunks werden temporär im
verbindlichen Objektspeicher abgelegt; beim Abschluss wird die Datei atomar
als Attachment übernommen. Verwaiste Sessions werden zeitgesteuert bereinigt.

Die lokale Warteschlange hält die Upload-Session und die bestätigten Chunks
während der laufenden Sitzung. Ein Netzabbruch kann deshalb ohne erneuten
Upload bereits bestätigter Chunks fortgesetzt werden. Ein Browser-Reload
während einer unvollständigen Auswahl bleibt außerhalb dieses ersten
Lieferumfangs, weil dafür zusätzlich die lokalen `File`-Objekte und ihre
Transformationszustände dauerhaft im Browser gespeichert werden müssten. Die
Schwelle und Chunk-Größe werden über Konfiguration festgelegt und nicht in den
Vue-Komponenten dupliziert.
Direkte Multipart-Uploads melden ihren Fortschritt über Axios-
`onUploadProgress`; Chunk-Uploads berechnen ihn aus bestätigten und aktuell
übertragenen Chunks. Ein Fehler hält den erreichten Fortschritt fest und
bietet einen erneuten Versuch ab dem letzten bestätigten Stand an.

### Desktop- und mobile Bedienung

Der Desktop ist der primäre Anwendungsfall und darf die verfügbaren Flächen
für Warteschlange, Vorschau und Werkzeuge ausnutzen. Auf schmalen Ansichten
bleibt die Uploader-Karte trotzdem bedienbar: große Touch-Ziele,
keine Hover-only-Aktionen, eine einspaltige Warteschlange, Titel-
Eingabefelder mit ausreichender Schriftgröße und eine Editor-Toolbar, die
unter der Vorschau umbrechen darf. Die wichtigsten Aktionen („Dateien
auswählen“, „Bearbeiten“, „Duplizieren“, „Entfernen“, „Alle hochladen“) sind
ohne horizontales Scrollen erreichbar. Drag-and-drop bleibt auf Desktop
vollflächig verfügbar; auf Mobilgeräten wird zusätzlich die native Datei-
und Kameraauswahl prominent angeboten. Fortschritt, Fehler und Wiederholen
werden pro Variante statt nur in einer globalen Meldung angezeigt.

Die Bildbearbeitung verwendet für einen bereits gespeicherten Attachment nur
noch die vorhandene Update-Route; im neuen Vorab-Workflow entsteht dadurch
kein zusätzlicher Attachment-Datensatz.

### Dateiliste

`AttachmentList` synchronisiert Änderungen von `modelValue`, verwendet die
Attachment-ID als Schlüssel, entfernt gelöschte Einträge sofort aus der
Ansicht und zeigt vorhandene Vorschauen, Dateityp, Größe und Fehlerzustand in
einer klareren responsiven Zeile/Karte. Die bestehende Löschbestätigung und
Routen bleiben erhalten. Optional nimmt `AttachmentList` die Props des
gemeinsamen Uploaders entgegen und rendert Uploader, Warteschlange und
bestehende Anhänge als einen Bereich. Die Dropzone umfasst dann den gesamten
Bereich einschließlich Leerzustand und Warteschlange; interaktive Elemente
wie Download, Löschen, Bearbeiten und Titel-Eingaben stoppen das Drop-Event
und bleiben normal klickbar.

Die Bild-Lightbox wird als wiederverwendbare Ansicht umgesetzt: große
responsive Vorschau, Titel/Dateiinformation, Download, Öffnen in neuem Tab,
Schließen per Escape/Klick/Touch und Vor-/Zurück-Navigation nur über die
Bildanhänge der aktuellen Liste. Nicht-Bilddateien behalten ihre direkte
Downloadaktion.

## Qualität und Tests

- Vue-Tests für Mehrfachauswahl, Dropzone, Input-Reset, Titeländerung,
  stabile Warteschlangen-IDs, einmaligen Upload, Fehler/Wiederholung und
  synchronisierte Attachment-Liste.
- Vue-Tests für die Transformationsaktionen und das Zurücksetzen des
  Bildeditors; Canvas-/Blob-Erzeugung wird über die vorhandenen Test-
  umgebungs-Hilfen isoliert geprüft.
- Vue-Tests für zwei Varianten derselben lokalen Quelle mit unterschiedlichen
  Presets, Zuschnitten und Zielen sowie für den Upload beider Varianten als
  zwei getrennte Attachments.
- Komponenten-/Integrations-Tests für das Werbung-Tab-Redesign: alle
  `config.images.cuts`-Presets erscheinen als Zielkarten, eine Quelle kann
  mehreren Zielen zugeordnet werden und die Kartenaktionen behalten ihren
  jeweiligen `cut`.
- Vue-Tests für die integrierte Attachment-Fläche: Dropzone über Liste und
  Warteschlange, ignorierte Drops auf Buttons/Eingabefelder und Lightbox-
  Navigation, Download sowie Escape-Schließen.
- Tests für die Schwellenentscheidung zwischen direktem und Chunk-Upload,
  Wiederholung eines fehlenden Chunks, Fortsetzen einer vorhandenen Session
  und Bereinigung eines fehlgeschlagenen Eintrags sowie korrekte Einzel- und
  Gesamtfortschritte.
- Mobile Viewport-/Komponententests für sichtbare Hauptaktionen, keine
  horizontale Überbreite und zugängliche Touch-Ziele.
- Feature-Test für mehrere `attachments` mit zugeordneten Titeln und für das
  Aktualisieren eines bestehenden Attachments ohne zusätzlichen Datensatz.
- Feature-/Unit-Tests für Start, Chunk-Annahme, Abschluss und Abbruch einer
  authentifizierten Upload-Session.
- Relevante JS-Tests, `git diff --check`, Docker-basierter Build sowie die
  fokussierten PHP-Tests mit explizit isolierter Testdatenbank.

## Offene Builder-Entscheidungen

Die Implementierung darf die genaue Anordnung der Toolbar, die Benennung der
Buttons und die Darstellung der Statuschips im bestehenden Bootstrap-/MDI-
Stil festlegen. Für normale Anhänge bleibt der Zuschnitt frei; im Werbung-Tab
werden die vorhandenen Konfigurationswerte aus `config.images.cuts` als
verbindliche Presets verwendet und inklusive Pixelgröße und Aspect Ratio im
Editor angezeigt.
