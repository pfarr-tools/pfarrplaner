[//]: # (TOC: 10. Werbung)

# Werbung

Im Bereich **Werbung** bereiten Sie Bilder und Texte für die verschiedenen
Ankündigungskanäle eines Gottesdienstes vor. Werbung ist ein eigener Editor mit
mehr Platz für Bildbearbeitung, Zuschnitte, Vorschauen und Veröffentlichungs-
einstellungen. Sie erreichen ihn über die Schaltfläche **Werbung** oben rechts
im [Editor für Veranstaltungen und Gottesdienste](veranstaltungen.md), im
[Liturgie-Editor](liturgie.md) oder im [Predigteditor](predigt.md).

Der Editor hat zwei Tabs:

- **Werbebilder**: Bilder hochladen, vorhandene Bilder wiederverwenden und die
  benötigten Zuschnitte prüfen.
- **Veröffentlichungen**: Pro Ankündigungskanal Vorlauf, Text und die
  realistische Ausgabevorschau bearbeiten.

## Werbebilder

![AdEditor: Werbebilder](media/images/werbung-editor-werbebilder.png)

Links steht der Uploadbereich, rechts die sechs Zielgrößen in einem übersicht-
lichen Raster. Die gesamte Uploadfläche ist eine Dropzone: Ziehen Sie Dateien
auf den Bereich oder wählen Sie **Dateien auswählen**. Das funktioniert auch auf
kleineren Bildschirmen; auf dem Desktop steht mehr Platz für die Vorschauen zur
Verfügung.

### Bilder hinzufügen

Der Upload unterstützt mehrere Quellen:

- **Lokale Dateien**: Dateien vom Computer oder Mobilgerät auswählen oder
  hineinziehen.
- **Aus dem Internet**: Eine Bild-URL verwenden.
- **Von der Kamera**: Ein Foto direkt mit dem Gerät aufnehmen.
- **Von Pixabay**: Nach Bildern suchen und ein Suchergebnis auswählen. Diese
  Quelle ist nur sichtbar, wenn die Installation einen Pixabay-Schlüssel
  eingerichtet hat.

Bei Bilddateien öffnet sich vor dem Upload der Bildeditor. Dort können Sie
  einen oder mehrere Zielzuschnitte auswählen, das Bild verschieben und den
  Zoom stufenlos unter und über 1-fach einstellen. Zusätzlich stehen freie
  Drehung, Drehung in 90-Grad-Schritten sowie horizontales und vertikales
  Spiegeln zur Verfügung. Ein transparenter Hintergrund wird im Editor und in
  der Vorschau als Schachbrett dargestellt.

Die Bearbeitung findet lokal im Browser statt. Erst wenn Sie **Übernehmen**
bestätigen, werden die erzeugten Zuschnitte in die Uploadwarteschlange gelegt.
Für jeden Eintrag können Sie einen Titel eingeben. Der Upload zeigt den
Fortschritt an, kann in Teilen fortgesetzt werden und entfernt erledigte
Einträge aus der Warteschlange, damit sie nicht versehentlich ein zweites Mal
hochgeladen werden.

Auch nicht-bildliche Dateien können hochgeladen werden. Für sie genügt die
Titel- beziehungsweise Beschreibungseingabe; der Bildeditor wird nicht
geöffnet.

### Vorhandene Bilder wiederverwenden

Die Liste **Vorhandene Bild-Attachments** enthält bereits am Gottesdienst
angehängte Bilddateien. Wählen Sie dort eine Bildquelle und ein Ziel aus. Das
Original wird dabei nicht verändert: Der neue Zuschnitt wird als eigenes
Werbebild gespeichert. So kann dasselbe Attachment für mehrere Zielgrößen oder
für verschiedene Veröffentlichungen mit unterschiedlichen Ausschnitten
verwendet werden.

### Zielgrößen

Pfarrplaner stellt standardmäßig diese Formate bereit:

| Zuschnitt | Größe | Seitenverhältnis | Typische Verwendung |
|---|---:|---:|---|
| **Bildschirm (4x3)** | 1024 × 768 px | 4:3 | Präsentationsfolie |
| **Bildschirm (16x9)** | 1920 × 1080 px | 16:9 | Website, Newsletter, Bildschirm |
| **CommuniApp** | 550 × 275 px | 2:1 | CommuniApp-Veranstaltung |
| **Quadratisch** | 1024 × 1024 px | 1:1 | Kachel oder quadratische Ausgabe |
| **Story** | 1080 × 1920 px | 9:16 | Story-Ausgabe |

Ein fehlender Zuschnitt zeigt **Kein Zuschnitt**. Zuschnitte mit Bild werden
direkt in der Vorschau dargestellt und können gelöscht werden. Die Vorschaubox
begrenzt das Bild, ohne das gespeicherte Original zu vergrößern oder zu
verfälschen.

## Veröffentlichungen

![AdEditor: Veröffentlichungen](media/images/werbung-editor-veroeffentlichungen.png)
![AdEditor: Veröffentlichungen – Allgemein](media/images/werbung-editor-veroeffentlichungen-allgemein.png)

Links wählen Sie einen Kanal. Das Symbol zeigt, ob er aktiviert ist. In der
Mitte erscheint eine realistische Vorschau der jeweiligen Ausgabe mit dem
passenden Bild-Cut; rechts bearbeiten Sie die Einstellungen des ausgewählten
Kanals. Der Kanal **Allgemein** enthält die gemeinsame Kurzbeschreibung für
Werbung und ist der Fallback für Kanäle ohne eigenen Text.

Für einen Kanal stehen insbesondere diese Felder zur Verfügung:

- **Vorlauf (Tage)**: Ab wann der Kanal vor dem Termin veröffentlicht werden
  darf. Ein leerer oder ausgeschalteter Kanal wird nicht veröffentlicht.
- **Abweichender Text**: Optionaler Text, der nur für diesen Kanal verwendet
  wird. Ohne eigenen Text wird die allgemeine Kurzbeschreibung verwendet.
- **Bild-Cut**: Der für den Kanal konfigurierte Zuschnitt, zum Beispiel
  **Story** für **Als Story posten**.

Prüfen Sie Bild, Zuschnitt und Text in der Vorschau, bevor Sie den Kanal
aktivieren. Änderungen speichern Sie oben mit **Speichern**. Die Vorschau ist
eine redaktionelle Kontrolle; die tatsächliche Veröffentlichung hängt von der
jeweiligen Kanalintegration und deren Einrichtung in der Administration ab.

## Empfehlungen

- Bereiten Sie einen geeigneten Zuschnitt für jeden tatsächlich genutzten
  Kanal vor.
- Verwenden Sie kurze, öffentlich verständliche Texte und prüfen Sie Datum,
  Uhrzeit und Ort des Gottesdienstes.
- Nutzen Sie vorhandene Bildanhänge, wenn nur ein anderer Ausschnitt benötigt
  wird. Dadurch müssen Bilder nicht doppelt als Original hochgeladen werden.
- Bei großen Dateien lassen Sie den Upload vollständig abschließen. Der
  Fortschrittsbalken und der Warteschlangenstatus zeigen, ob noch Arbeit offen
  ist.
