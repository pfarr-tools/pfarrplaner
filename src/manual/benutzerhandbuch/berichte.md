[//]: # (TOC: 12. Berichte und Ausgaben)

# Berichte und Ausgaben

Berichte lesen Daten aus Pfarrplaner und erstellen daraus Listen, Dateien, Formulare, E-Mails oder HTML-Code für Websites. Sie verändern normalerweise keine Gottesdienste. Ausnahmen sind ausdrücklich beschrieben, zum Beispiel beim Senden einer Dienstanfrage.

Wenn ein Bericht unvollständig wirkt, fehlen die Angaben meistens im [Editor für Veranstaltungen und Gottesdienste](veranstaltungen.md), in [Kasualien](kasualien.md), im [Urlaubsplan](urlaubsplan.md) oder in den [Sammeleingaben](eingaben.md).
Wenn Berichte einen Opferzweck oder eine Opferanmerkung ausgeben, verwendet Pfarrplaner bei leeren Feldern automatisch die Standardwerte der Kirchengemeinde. Für normale Gottesdienste, Beerdigungen und Trauungen können dabei unterschiedliche Standardwerte gelten.

---

## Berichte aufrufen

Öffnen Sie im Hauptmenü **Ausgabeformate**. Die Seite heißt **Ausgabeformat wählen**.

![Berichte-Übersicht](media/images/berichte-uebersicht.png)

Die Berichte sind nach Gruppen sortiert. Jede Kachel zeigt:

- **Symbol**
- **Titel**
- **Kurzbeschreibung**

Klicken Sie auf eine Kachel, um die Einstellungen des Berichts zu öffnen. Welche Berichte sichtbar sind, hängt von Ihren Rechten, Ihrer Gemeinde und aktiven Integrationen ab.

---

## Berichte ausführen

Viele Berichte haben oben links eine Schaltfläche **Erstellen**. Manche heißen anders, zum Beispiel **Senden**, wenn wirklich eine Anfrage verschickt wird.

Typische Felder:

- **Kirchengemeinde / Kirchengemeinden**: Begrenzt die Daten auf eine oder mehrere Gemeinden.
- **Ort / Orte**: Begrenzt die Daten auf bestimmte Kirchen oder Veranstaltungsorte.
- **Von** oder **Gottesdienste von**: Zeitraum mit Beginn und Ende. Ein Klick auf das Feld öffnet einen Zweimonatskalender. Wählen Sie zuerst das Startdatum, dann das Enddatum. Das Feld zeigt danach den gewählten Zeitraum, z. B. „01.01.2026 – 31.12.2026".
- **Jahr**: Kalenderjahr.
- **Dienste**: Dienstkategorien wie Pfarrer:in, Organist:in, Mesner:in oder weitere Dienste.
- **Dateiformat**: PDF, Excel, Word, CSV oder andere Formate.
- **Aufrufende Website**: Website-Adresse, auf der ein erzeugter HTML-Code später eingebunden wird.

Nicht jedes Feld erscheint in jedem Bericht.

---

## Ergebnis- und Code-Seiten

Einige Berichte erzeugen direkt eine Datei. Andere zeigen nach dem Erstellen eine Ergebnis-Seite.

Bei Website-Berichten erscheint eine Seite mit:

- einer kurzen Anleitung für TYPO3 beziehungsweise Gemeindebaukasten,
- dem Feld **HTML-Code zum Kopieren**,
- der Schaltfläche **Kopieren**.

Der HTML-Code ist für die Person gedacht, die Ihre Website betreut. Prüfen Sie vor dem Einbau, welche Daten öffentlich sichtbar werden.

Das Feld **Aufrufende Website** muss bei Website-Berichten ausgefüllt werden. Pfarrplaner erlaubt den HTML-Code nur auf dieser Website. Wenn die Adresse fehlt oder nicht zur eingebundenen Seite passt, kann der Browser die Einbindung blockieren.

Der Bericht **Veranstaltungswerbung** ist das neuere Modell für Website-Einbindungen. Andere Website-Berichte können langfristig in diese Form überführt werden.

Beim **Newsletter** erscheint ebenfalls ein Feld **HTML-Code zum Kopieren** und **Kopieren**.

---

## Bekanntgaben

Der Bericht **Bekanntgaben** erstellt Bekanntgaben für einen Gottesdienst. Er ist dafür gedacht, aus Pfarrplaner Angaben für die Abkündigungen im Gottesdienst zusammenzustellen.

Seite: **Bekanntgaben erstellen**

Schaltfläche:

- **Erstellen**: Erstellt die Bekanntgaben.

Felder:

- **Kirchengemeinde**: Gemeinde, für die die Bekanntgaben erstellt werden.
- **Bekanntgaben für den folgenden Gottesdienst erstellen**: Gottesdienst, für den die Bekanntgaben gelten. Die Liste wird nach der Gemeinde geladen.
- **Wöchentlich wiederholte Veranstaltungen (nicht Gottesdienste) ausschließen**: Entfernt regelmäßige Wochenveranstaltungen aus der Ausgabe.
- **Herzlichen Dank für das Opfer der Gottesdienste vom...**: Auswahl vergangener Gottesdienste, für die ein Dank für das Opfer eingefügt werden soll.
- **... in Höhe von ...**: Betrag des Opferdanks. Der Betrag wird automatisch geladen, wenn Daten vorhanden sind.

Während Gottesdienste oder Beträge geladen werden, erscheint **Lade Daten, bitte warten...**.

---

## Kirchliche Nachrichten

Der Bericht **Kirchliche Nachrichten** erstellt Texte zum Aushang oder zur Veröffentlichung, zum Beispiel für kirchliche Nachrichten im Gemeindeblatt.

Seite: **Kirchliche Nachrichten erstellen**

Felder:

- **Kirchliche Nachrichten für folgende Kirchengemeinden erstellen**: Gemeinden, die einbezogen werden.
- **Kopfzeilen drucken**: Blendet die Kopfzeilen mit den Gemeinde- und Pfarramtsangaben ein oder aus. Aktivieren Sie die Option, wenn die Ausgabe als vollständiger Aushang mit diesen Zusatzangaben gedacht ist. Lassen Sie sie ausgeschaltet, wenn Sie nur den eigentlichen Nachrichtentext brauchen.
- **Alternative Ortsbezeichnung**: Optionaler Ortsname, wenn in der Ausgabe nicht der normale Gemeindename erscheinen soll.
- **Gottesdienste ab**: Startdatum.
- **Folgende Pfarrämter mit einbeziehen**: Pfarrämter, deren Angaben einfließen.
- **Urlaub für folgende Pfarrer:innen mit einbeziehen**: Personen, deren Urlaubszeiten berücksichtigt werden.

Die Listen der Pfarrämter und Personen hängen von den gewählten Gemeinden ab.
Pfarrplaner merkt sich die zuletzt verwendeten Gemeinden, Pfarrämter, Personen, die alternative Ortsbezeichnung und die Einstellung für **Kopfzeilen drucken** für den nächsten Aufruf dieses Berichts.

---

## Gemeindebrief

Der Bericht **Gemeindebrief** erstellt eine Gottesdienstliste für den Gemeindebrief.

Seite: **Gottesdienstliste für den Gemeindebrief erstellen**

Felder:

- **Folgende Kirchengemeinden mit einbeziehen**: Gemeinden für die Liste.
- **Gottesdienste von**: Zeitraum (Beginn und Ende).
- **Format**: Auswahl des Gemeindebrief-Formats. Die angebotenen Formate kommen aus der Berichtskonfiguration.

Prüfen Sie vor dem Export besonders Uhrzeiten, Orte, Beschreibungen und öffentliche Hinweise im [Editor für Veranstaltungen und Gottesdienste](veranstaltungen.md).

---

## Gemeindebrief (BL)

Der Bericht **Gemeindebrief (BL)** ist eine spezielle Gemeindebrief-Ausgabe für Balingen. Er ist nur aktiv, wenn Ihr Konto Schreibrechte für die entsprechende Gemeinde hat.

Zweck ist eine fertige Gottesdiensttabelle für dieses Gemeindebrief-Layout. Wenn dieser Bericht nicht erscheint, ist er für Ihre Installation oder Ihre Rechte nicht aktiv.

---

## Programm für die Kinderkirche

Der Bericht **Programm für die Kinderkirche** erstellt eine Übersicht der Kinderkirche-Termine mit Themen und Mitarbeitenden.

Seite: **Programm für die Kinderkirche erstellen**

Felder:

- **Liste für folgende Kirchengemeinde erstellen**: Gemeinde.
- **Gottesdienste von**: Zeitraum (Beginn und Ende).

Die Daten stammen aus den Kinderkirche-Feldern der Gottesdienste und aus dem Assistenten [Kinderkirche bearbeiten](eingaben.md).

---

## EKD-Statistik

Der Bericht **EKD Statistik** erstellt eine Auswertung für die jährliche Kirchenstatistik.

Seite: **EKD-Statistikbericht erstellen**

Felder:

- **Themenplan für folgende Kirchengemeinden erstellen**: Gemeinden für die Statistik.
- **Jahr**: Statistikjahr.

Prüfen Sie vorher Besucherzahlen, Kasualien, besondere Gottesdienstarten und fehlende Angaben.

---

## Terminliste

Der Bericht **Terminliste** erstellt eine Worddatei mit Veranstaltungen.

Seite: **Terminliste erstellen**

Felder:

- **Liste für folgende Kirchengemeinde erstellen**: Gemeinde.
- **Von**: Zeitraum (Beginn und Ende).
- **Veranstaltungen aus dem Outlook-Kalender mit aufnehmen.**: Bezieht Outlook-Termine ein, wenn eine entsprechende Integration vorhanden ist.
- **Veranstaltungen aus dem Online Planer mit aufnehmen.**: Bezieht Termine aus dem Online Planer ein.

Die beiden Kontrollkästchen sind nur sinnvoll, wenn die jeweilige Quelle in Ihrer Installation genutzt wird.

---

## Liste der Angehörigen

Der Bericht **Liste der Angehörigen** erstellt eine Adressliste von Angehörigen zu Beerdigungen.

Seite: **Adressliste der Angehörigen erstellen**

Felder:

- **Adressliste für Beerdigungen in folgender Kirchengemeinde erstellen**: Gemeinde.
- **Gottesdienste von**: Startdatum. Der Bericht verwendet daraus den relevanten Zeitraum der Beerdigungen.

Die Daten stammen aus den Beerdigungsdaten in [Kasualien](kasualien.md).

---

## GiroCodes für digitales Gottesdienstopfer

Der Bericht **GiroCodes für digitales Gottesdienstopfer** erstellt QR-Codes, mit denen digital für einen Opferzweck gespendet werden kann.

Seite: **GiroCodes für Gottesdienste erstellen**

Felder:

- **GiroCodes für folgende Kirchengemeinde erstellen**: Gemeinde.
- **Gottesdienste von**: Zeitraum (Beginn und Ende).
- **Anzahl Kopien pro QR-Code**: Wie oft jeder QR-Code ausgegeben werden soll.

Voraussetzung ist, dass die nötigen Zahlungsdaten für die Gemeinde gepflegt sind.
Wenn im Gottesdienst kein eigener Opferzweck eingetragen ist, verwendet der QR-Code automatisch den hinterlegten Standardwert der Kirchengemeinde.

---

## QR-Codes für Gottesdienste / KonfiApp

Der Bericht **QR-Codes für Gottesdienste** erstellt QR-Codes, die Konfis mit der [KonfiApp](https://www.konfiapp.de) scannen können. Die KonfiApp ist ein externes Angebot für die Konfi-Arbeit.

Der Bericht ist nur sinnvoll, wenn in der Administration für die Gemeinde eine KonfiApp-Anbindung eingerichtet ist. Außerdem müssen die betreffenden Gottesdienste eine **Veranstaltungsart in der KonfiApp** haben. Pfarrplaner legt dann beim Speichern des Gottesdienstes den passenden QR-Code an.

Seite: **QR-Codes für Gottesdienste erstellen**

Felder:

- **QR-Codes für folgende Kirchengemeinde erstellen**: Gemeinde.
- **Gottesdienste von**: Zeitraum (Beginn und Ende).
- **Anzahl Kopien pro QR-Code**: Anzahl der Ausdrucke pro Gottesdienst.

Die Ausgabe enthält nur Gottesdienste, für die bereits ein KonfiApp-QR-Code vorhanden ist. Gibt es im gewählten Zeitraum keine passenden Gottesdienste, kehrt Pfarrplaner zur Eingabeseite zurück.

Dieser Bericht erscheint nur, wenn mindestens eine für Sie verfügbare Gemeinde eine KonfiApp-Anbindung hat. Die Anbindung wird in der Administration bei der jeweiligen Kirchengemeinde eingerichtet.

---

## Urlaubsantrag

Der Bericht **Urlaubsantrag** erstellt ein vorausgefülltes Formular für einen Abwesenheitseintrag.

Dieser Bericht ist für Pfarrer:innen gedacht und erscheint nur, wenn Ihr Konto die entsprechende Rolle hat. Er verwendet Ihre eigenen zukünftigen Abwesenheitseinträge.

Seite: **Urlaubsantrag erstellen**

Feld:

- **Abwesenheitseintrag**: Abwesenheit, für die der Antrag erstellt wird.

Die auswählbaren Einträge kommen aus dem [Urlaubsplan](urlaubsplan.md).

---

## Dienstreiseantrag

Der Bericht **Dienstreiseantrag** erstellt ein Formular für einen passenden Abwesenheitseintrag.

Dieser Bericht ist für Pfarrer:innen gedacht und erscheint nur, wenn Ihr Konto die entsprechende Rolle hat. Er verwendet Ihre eigenen zukünftigen Abwesenheitseinträge.

Seite: **Dienstreiseantrag erstellen**

Feld:

- **Abwesenheitseintrag**: Abwesenheit, für die der Antrag erstellt wird.

Auch dieser Bericht verwendet Daten aus dem [Urlaubsplan](urlaubsplan.md).

---

## Dienstanfrage senden

Der Bericht **Dienstanfrage senden** verschickt eine Anfrage an Personen oder Teams für offene Dienste. Anders als viele andere Berichte kann diese Funktion tatsächlich Nachrichten senden.

Seite: **Dienstanfrage senden**

Schaltfläche:

- **Senden**: Sendet die Anfrage. Die Schaltfläche ist deaktiviert, solange keine Gottesdienste und keine Empfänger ausgewählt sind.

Felder:

- **Kirchengemeinde**: Gemeinde.
- **Auf folgende Orte beschränken**: Optional. Begrenzt die Gottesdienste auf Orte.
- **Gottesdienste von**: Zeitraum (Beginn und Ende).
- **Anfrage für folgenden Dienst senden**: Dienst, für den angefragt wird.
- **Empfänger**: Personenauswahl. Teams können ebenfalls angeboten werden.
- **Folgende Gottesdienste anfragen**: Tabelle mit Kontrollkästchen, Zeit und Ort. Neue Gottesdienste werden nach Gemeinde, Zeitraum und Orten geladen.

Beim Laden erscheinen Hinweise wie **Mitarbeiterliste wird geladen...** oder **Gottesdienstliste wird geladen...**.

Wenn eine ausgewählte Person keine E-Mail-Adresse hat, fragt Pfarrplaner beim Senden nach einer Adresse.

---

## Leerer Dienstplan

Der Bericht **Leerer Dienstplan** erstellt einen Plan für bestimmte Dienste, in den sich Personen später eintragen können.

Seite: **Leeren Dienstplan für einen Dienst erstellen**

Felder:

- **Plan für folgende Kirchengemeinden erstellen**: Gemeinden.
- **Plan für folgende Dienste erstellen**: Dienste.
- **Gottesdienste von**: Zeitraum (Beginn und Ende).

Der Bericht ist für Umlaufverfahren oder Aushänge gedacht.

---

## Organist:innensuche

Der Bericht **Organist:innensuche** erstellt eine Liste zur Suche nach fehlenden Organist:innen.

Seite: **Suche nach Organist:innen**

Felder:

- **Organist:innen für folgende Kirchengemeinden erstellen**: Gemeinden.
- **Gottesdienste von**: Zeitraum (Beginn und Ende).

Nutzen Sie die Liste, um Gottesdienste ohne Orgeldienst zu prüfen und anschließend im [Editor für Veranstaltungen und Gottesdienste](veranstaltungen.md) zu ergänzen.

---

## Newsletter

Der Bericht **Newsletter** erstellt HTML-Code für eine Gottesdienstliste im Newsletter.

Seite: **Gottesdienstliste für den Newsletter erstellen**

Felder:

- **Newsletter für folgende Kirchengemeinden erstellen**: Gemeinden.
- **Auf folgende Orte beschränken**: Optional. Zeigt nur Orte aus den gewählten Gemeinden. Leer bedeutet, dass alle Orte der gewählten Gemeinden einbezogen werden.
- **Wochenspruch mit aufnehmen.**: Fügt den Wochenspruch ein.
- **Gottesdienste von**: Zeitraum (Beginn und Ende).

Nach dem Erstellen erscheint die Seite **Gottesdienstliste für den Newsletter** mit:

- **HTML-Code zum Kopieren**
- **Kopieren**

---

## Übersicht der eingenommenen Opfer

Der Bericht **Übersicht der eingenommenen Opfer** erstellt eine Tabelle der Opferbeiträge nach Kategorien.

Seite: **Übersicht der eingenommenen Opfer erstellen**

Felder:

- **Bericht für folgende Kirchengemeinden erstellen**: Gemeinden.
- **Auf folgende Orte beschränken**: Optional. Zeigt nur Orte aus den gewählten Gemeinden. Leer bedeutet, dass alle Orte der gewählten Gemeinden einbezogen werden.
- **Gottesdienste von**: Zeitraum (Beginn und Ende).

Die Beträge stammen aus den Opferfeldern der Gottesdienste und aus dem Assistenten [Opferplan bearbeiten](eingaben.md).
Die Gruppierung nach Opferzweck verwendet ebenfalls automatisch hinterlegte Standardwerte der Kirchengemeinde, wenn beim einzelnen Gottesdienst kein eigener Opferzweck eingetragen ist.

---

## Opferplan

Der Bericht **Opferplan** gibt eine Jahresübersicht der Opferzwecke aus.

Seite: **Opferplan ausgeben**

Felder:

- **Opferplan für folgende Kirchengemeinden erstellen**: Gemeinden.
- **Auf folgende Orte beschränken**: Optional. Zeigt nur Orte aus den vorher gewählten Gemeinden. Leer bedeutet, dass alle Orte der gewählten Gemeinden einbezogen werden.
- **Jahr**: Jahr des Opferplans.
- **Opferzähler mit ausgeben**: Nimmt die Namen der Opferzähler auf.
- **Fehlende Einträge hervorheben**: Markiert Gottesdienste ohne vollständige Opferangaben.

Wenn bei einem Gottesdienst kein eigener Opferzweck oder keine eigene Opferanmerkung eingetragen ist, zeigt der Bericht automatisch die hinterlegten Standardwerte der Kirchengemeinde an. Die zusätzliche Übersicht unter der Tabelle zeigt diese Standardwerte noch einmal getrennt nach allgemeinem Gottesdienst, Beerdigung und Trauung.

---

## Alle Gottesdienste einer Person

Der Bericht **Alle Gottesdienste einer Person** erstellt eine Liste der Gottesdienste, für die eine Person eingeteilt ist.

Seite: **Gottesdienstliste für eine Person erstellen**

Felder:

- **Nach folgender Person suchen**: Person.
- **Gottesdienste von**: Zeitraum (Beginn und Ende).

---

## Prädikant:innenanforderung

Der Bericht **Prädikant:innenanforderung** erstellt ein vorausgefülltes Formular für das Dekanatamt. Die genaue Bezeichnung verwendet die in Ihrer Installation gesetzten Dienstnamen.

Seite: **Prädikant:innenanforderung erstellen**

Felder:

- **Prädikant:innen für folgende Kirchengemeinden anfordern**: Gemeinden.
- **Gottesdienste von**: Zeitraum (Beginn und Ende).

---

## Quartalsprogramm

Der Bericht **Quartalsprogramm** erstellt eine Übersicht aller Termine für ein Quartal an einem bestimmten Veranstaltungsort.

Seite: **Quartalsprogramm erstellen**

Felder:

- **Titel**: Überschrift des Programms.
- **Liste für folgenden Veranstaltungsort**: Ort.
- **Quartal**: Auszuwertendes Quartal.
- **Pfarrer:in**, **Organist:in**, **Mesner:in**: Kontrollkästchen für Dienste, die mit ausgegeben werden. Die Beschriftungen folgen den Einstellungen Ihrer Installation.
- **Besonderheiten**: Gibt Beschreibungen oder besondere Hinweise aus.
- **Hinweise vor der Übersicht der Gottesdienste**: Freitext vor der Liste.
- **Hinweise nach der Übersicht der Gottesdienste**: Freitext nach der Liste.
- **Meine Kontaktdaten für weitere Informationen mit aufnehmen**: Fügt Kontaktdaten der angemeldeten Person ein.

---

## Zu vertretende Dienste

Der Bericht **Zu vertretende Dienste** listet Dienste einer Person auf, für die eine Vertretung gesucht werden könnte.

Seite: **Zu vertretende Dienste für eine Person finden**

Schaltflächen:

- **Erstellen**: Erstellt die Liste.
- **Direkt eintragen**: Öffnet eine Bearbeitungsübersicht, in der Vertretungen direkt eingetragen werden können.

Felder:

- **Nach folgender Person suchen**: Person.
- **Dienste von**: Zeitraum (Beginn und Ende).

### Direkt eintragen

Die Seite heißt **Vertretungen für ... bearbeiten**.

Spalten:

- **Gottesdienst**: Titel, Datum, Uhrzeit und Ort.
- **Dienstspalte**: Personenauswahl für den zu vertretenden Dienst.
- **Stift**: Öffnet den Gottesdienst.
- **Papierkorb**: Löscht den Gottesdienst nach Rückfrage vollständig.

Änderungen werden automatisch gespeichert.

---

## Freud & Leid

Der Bericht **Freud & Leid** erstellt Kasualien-Texte für den Gemeindebrief.

Seite: **Freud & Leid für den Gemeindebrief erstellen**

Felder:

- **Folgende Kirchengemeinden mit einbeziehen**: Gemeinden.
- **Auflisten von** (Kausalien): Zeitraum (Beginn und Ende).
- **Auflisten von** (Tauftermine): Zeitraum für die nächsten Tauftermine (Beginn und Ende). Dieser zweite Datumsbereich erscheint unterhalb der Trennlinie.

Die Daten stammen aus [Kasualien](kasualien.md).

---

## Excel-Tabelle der Gottesdienste

Der Bericht **Excel-Tabelle der Gottesdienste** erstellt eine große Excel-Tabelle mit Gottesdiensten, liturgischen Angaben, Veranstaltungsdaten, Opfern und Diensten.

Seite: **Excel-Tabelle der Gottesdienste erstellen**

![Excel-Tabelle der Gottesdienste](media/images/berichte-excel-tabelle-gottesdienste.png)

Auf dieser Seite sehen Sie oben links die Schaltfläche **Erstellen**. Darunter folgen die Filterfelder für Gemeinden und Orte. Danach kommt der Zeitraum als gemeinsames Datumsfeld für Beginn und Ende. Standardmäßig ist dort immer der komplette Zeitraum der nächsten zwei Monate vorbelegt. Weiter unten wählen Sie zusätzliche Dienste aus, die als eigene Spalten in der Excel-Datei erscheinen sollen. Danach folgen die Optionen **Lesbare Überschriften** und **Nur Gottesdienste**. Wenn **Lesbare Überschriften** eingeschaltet ist, verwendet der Bericht normale deutsche Spaltenüberschriften. Wenn die Option ausgeschaltet ist, werden die Überschriften als kompakte PascalCase-Begriffe ohne Leerzeichen ausgegeben.

Felder:

- **Tabelle für folgende Kirchengemeinden erstellen**: Gemeinden.
- **Auf folgende Orte beschränken**: Optional. Zeigt nur Orte aus den gewählten Gemeinden. Leer bedeutet, dass alle Orte der gewählten Gemeinden einbezogen werden.
- **Zeitraum**: Beginn und Ende der auszugebenden Termine.
- **Folgende weiteren Dienste mit einschließen**: Zusätzliche Dienste, die als eigene Spalten aufgenommen werden.
- **Lesbare Überschriften**: Wenn diese Option aktiv ist, werden die Spaltenüberschriften in normal lesbarer Form ausgegeben. Wenn sie nicht aktiv ist, bestehen die Überschriften aus kompakten PascalCase-Bezeichnungen ohne Leerzeichen.
- **Nur Gottesdienste**: Begrenzt die Ausgabe auf Gottesdienste. Wenn die Option ausgeschaltet ist, werden auch andere Veranstaltungen mit ausgegeben.

Die Excel-Datei enthält für jeden gefundenen Termin eine Zeile. Wichtige Angaben wie Datum und Uhrzeit werden als echte Excel-Datums- und Zeitwerte geschrieben, damit Sie in Excel damit weiterarbeiten, sortieren oder filtern können. Der Bericht enthält außerdem Spalten für liturgische Angaben, Sichtbarkeit, Tauf- und Abendmahlskennzeichen, Titel, Ort, interne Hinweise, zusätzliche Bekanntgaben, Pfarrperson, Organist:innen, Mesner:innen, weitere ausgewählte Dienste und Opferangaben. Die Spalte für den Veranstaltungstyp erscheint nur dann, wenn **Nur Gottesdienste** ausgeschaltet ist. Wenn bei Opferzweck oder Opferanmerkung nichts direkt eingetragen ist, verwendet der Bericht automatisch die hinterlegten Standardtexte der gewählten Kirchengemeinde für allgemeine Gottesdienste, Beerdigungen oder Trauungen. Sind auch dort keine Standardtexte hinterlegt, bleiben die Felder leer.

Der Pfarrplaner merkt sich die Auswahl der zusätzlichen Dienste, die Einstellung für die Überschriften und die Auswahl **Nur Gottesdienste** für die aktuelle Person. Beim nächsten Öffnen sind diese Angaben deshalb wieder vorbelegt.

---

## Jahresplan der Gottesdienste

Der Bericht **Jahresplan der Gottesdienste** erstellt weiterhin die bisherige große Excel-Tabelle mit Jahresübersicht zu Gottesdiensten, liturgischen Farben, Opfern und Diensten.

Seite: **Jahresplan der Gottesdienste erstellen**

![Jahresplan der Gottesdienste](media/images/berichte-jahresplan-gottesdienste.png)

Auf dieser Seite steht ebenfalls oben links die Schaltfläche **Erstellen**. Darunter wählen Sie die Kirchengemeinden und optional einzelne Orte aus. Danach geben Sie das gewünschte Kalenderjahr an. Im Feld für die Dienste legen Sie fest, welche weiteren Dienste als zusätzliche Spalten erscheinen sollen. Mit **Namen ausgeben als** wählen Sie, ob Namen kurz oder ausführlich dargestellt werden.

Felder:

- **Jahresplan für folgende Kirchengemeinden erstellen**: Gemeinden.
- **Auf folgende Orte beschränken**: Optional. Zeigt nur Orte aus den gewählten Gemeinden. Leer bedeutet, dass alle Orte der gewählten Gemeinden einbezogen werden.
- **Jahr**: Jahr.
- **Folgende Dienste mit einschließen**: Dienste, die als Spalten aufgenommen werden.
- **Namen ausgeben als**: Format der Namen.

Die erzeugte Datei ist auf eine Jahresübersicht ausgelegt. Sie zeigt die Gottesdienste in der bisherigen Tabellenform mit farblich markierten liturgischen Angaben, Spalten für Beteiligte und Opferspalten. Wenn Sie statt einer Jahresübersicht einen frei wählbaren Zeitraum und die neuen kompakten Spaltenüberschriften brauchen, verwenden Sie den Bericht **Excel-Tabelle der Gottesdienste**.

---

## Thematischer Plan der Gottesdienste

Der Bericht **Thematischer Plan der Gottesdienste** erstellt eine Excel-Tabelle mit Gottesdiensten und Themen.

Seite: **Themenplan der Gottesdienste erstellen**

Felder:

- **Themenplan für folgende Kirchengemeinden erstellen**: Gemeinden.
- **Auf folgende Orte beschränken**: Optional. Zeigt nur Orte aus den gewählten Gemeinden. Leer bedeutet, dass alle Orte der gewählten Gemeinden einbezogen werden.
- **Jahr**: Jahr.

---

## Gottesdienste nach Kirchengemeinden

Der Bericht **Gottesdienste nach Kirchengemeinden** gibt Gottesdienste für bestimmte Gemeinden als CSV-Datei aus.

Seite: **Liste der Gottesdienste für bestimmte Kirchengemeinden erstellen**

Felder:

- **Kirchengemeinden**: Gemeinden.
- **Komplette Ortsangabe bei der Bezeichung des Gottesdienstortes**: Gibt den Ort ausführlicher aus.
- **Von**: Zeitraum (Beginn und Ende).

---

## Gottesdienste nach Ort

Der Bericht **Gottesdienste nach Ort** gibt Gottesdienste für einen bestimmten Ort als CSV-Datei aus.

Seite: **Liste der Gottesdienste für einen Ort erstellen**

Felder:

- **Ort**: Veranstaltungsort.
- **Von**: Zeitraum (Beginn und Ende).

---

## Gottesdienste nach AZE-Kategorie

Der Bericht **Gottesdienste nach AZE-Kategorie** wertet Gottesdienste von hauptamtlichen Mitarbeitenden nach AZE-Kategorien aus. AZE steht für **Arbeitszeitermittlung**. Diese Auswertung hilft zum Beispiel bei Mesner:innen, Organist:innen oder Pfarrer:innen zu prüfen, wie viele Dienste in welcher Kategorie bereits geleistet wurden.

Die Kategorien kommen aus dem Feld **Einstufung für Hauptamtliche mit AZE** im [Editor für Veranstaltungen und Gottesdienste](veranstaltungen.md). Dort kann ein Eintrag als **Hauptgottesdienst**, **Weiterer Gottesdienst** oder **ohne Kategorie** markiert werden.

Seite: **Übersicht der Gottesdienste nach AZE-Kategorie**

Felder:

- **Bericht für folgende Kirchengemeinden erstellen**: Gemeinden.
- **Bericht für folgende Personen erstellen**: Personen.
- **Gottesdienste von**: Zeitraum (Beginn und Ende).

Die Excel-Datei enthält pro Person und Tätigkeit eine Zeile. Tätigkeiten sind zum Beispiel Pfarrer:in, Organist:in, Mesner:in oder weitere Dienste.

Die Spalten sind in drei Blöcke aufgeteilt:

- **Geleistet**: Zählt, wie viele Gottesdienste im gewählten Zeitraum tatsächlich eingetragen sind.
- **Laut AZE**: Platz für die Soll-Werte aus der Arbeitszeitermittlung.
- **Saldo**: Zeigt die Differenz zwischen geleisteten Gottesdiensten und Soll-Werten.

Die Spalten **Hauptgd.**, **Sonst.** und **Ohne** entsprechen den AZE-Einstufungen **Hauptgottesdienst**, **Weiterer Gottesdienst** und **ohne Kategorie**.

Der Bericht verändert keine Daten. Er erstellt nur eine Excel-Datei, die anschließend weiter geprüft oder ergänzt werden kann.

---

## Dienstplan für einzelne Dienste

Der Bericht **Dienstplan für einen bestimmten Dienst** erstellt eine Liste der eingeteilten Personen für bestimmte Dienste.

Seite: **Dienstplan für einzelne Dienste erstellen**

Felder:

- **Plan für folgende Kirchengemeinden erstellen**: Gemeinden.
- **Auf folgende Orte beschränken**: Optional. Zeigt nur Orte aus den gewählten Gemeinden. Leer bedeutet, dass alle Orte der gewählten Gemeinden einbezogen werden.
- **Dienste**: Dienste.
- **Gottesdienste von**: Zeitraum (Beginn und Ende).
- **Dateiformat**: PDF oder Excel, je nach Auswahl.
- **Überschriftenblock mit ausgeben**: Fügt einen Kopfbereich in die Ausgabe ein.

---

## Liste aller Wochensprüche

Der Bericht **Liste aller Wochensprüche** gibt Wochensprüche für einen Zeitraum aus.

Seite: **Liste der Wochensprüche erstellen**

Felder:

- **Von**: Zeitraum (Beginn und Ende).

---

## Website: Ansprechpartner finden

Der Bericht **Ansprechpartner finden** erzeugt HTML-Code für eine Ansprechpartner-Box auf der Website.

Seite: **HTML-Code für Ansprechpartnerformular erstellen**

Felder:

- **Folgende Kirchengemeinden mit einbeziehen**: Gemeinden.
- **Aufrufende Website**: Website, auf der der Code eingebunden wird, zum Beispiel `https://www.tailfingen-evangelisch.de`.

Die Adresse muss genau zu der Website passen, auf der der HTML-Code später eingebunden wird.

Nach dem Erstellen erscheint der HTML-Code mit **Kopieren**.

---

## Website: Liste von aktuellen Veranstaltungen

Der Bericht **Liste von aktuellen Veranstaltungen** erzeugt HTML-Code für eine Veranstaltungsliste.

Seite: **HTML-Code für eine Veranstaltungstabelle erstellen**

Felder:

- **Tabelle für folgende Kirchengemeinden erstellen**: Gemeinden.
- **Anzahl der angezeigten Tage**: Wie viele Tage ab heute angezeigt werden.
- **Aufrufende Website**: Website, auf der der Code laufen darf. Dieses Feld ist erforderlich, damit der Browser die Einbindung erlaubt.

---

## Website: Anmeldung zu Gottesdiensten

Der Bericht **Anmeldung zu Gottesdiensten** erzeugt HTML-Code für eine Anmeldebox.

Seite: **HTML-Code für eine Veranstaltungstabelle erstellen**

Felder:

- **Folgende Kirchengemeinden mit einbeziehen**: Gemeinden.
- **Begrenzen auf einen Tag?**: Optionales Datum. Leer bedeutet alle Tage.
- **Nur einen bestimmten Gottesdienst anzeigen?**: ID eines Gottesdienstes. Leer bedeutet alle passenden Gottesdienste.
- **Aufrufende Website**: Website, auf der der Code laufen darf. Dieses Feld ist erforderlich, damit der Browser die Einbindung erlaubt.

---

## Website: Liste von Gottesdiensten

Der Bericht **Liste von Gottesdiensten** erzeugt HTML-Code für eine Gottesdiensttabelle.

Seite: **HTML-Code für eine Liste von Gottesdiensten erstellen**

Felder:

- **Art der Liste**: Auswahl der Tabellenart.
- **Liste für folgende Kirchengemeinden erstellen**: Erscheint, wenn die Listenart nach Gemeinden arbeitet.
- **Liste für folgende Orte erstellen**: Erscheint, wenn die Listenart nach Orten arbeitet.
- **Maximale Anzahl von Taufen pro Gottesdienst**: Erscheint nur bei der Listenart für Taufgottesdienste.
- **Maximale Anzahl der anzuzeigenden Gottesdienste**: Begrenzung der Anzahl.
- **Aufrufende Website**: Website, auf der der Code laufen darf. Dieses Feld ist erforderlich, damit der Browser die Einbindung erlaubt.

Listenarten sind unter anderem Gottesdienste in Gemeinden, Gottesdienste in Kirchen und spezielle Taufgottesdienstlisten.

---

## Website: Streaming von Gottesdiensten

Der Bericht **Streaming von Gottesdiensten** erzeugt HTML-Code für Gottesdienststreams.

Seite: **HTML-Code für Gottesdienststreams erstellen**

Felder:

- **Code für folgende Kirchengemeinde erstellen**: Gemeinde.
- **Aufrufende Website**: Website, auf der der Code laufen darf. Dieses Feld ist erforderlich, damit der Browser die Einbindung erlaubt.

Die Ausgabe ist nur sinnvoll, wenn Streamingdaten in den Gottesdiensten gepflegt sind.
Falls in der Streaming-Ausgabe ein Opferzweck angezeigt wird, verwendet Pfarrplaner bei leeren Gottesdienstfeldern automatisch den passenden Standardwert der Kirchengemeinde.

---

## Website: Veranstaltungswerbung

Der Bericht **Veranstaltungswerbung** erzeugt HTML-Code für Veranstaltungswerbung auf der Website.

Seite: **HTML-Code für Veranstaltungswerbung erstellen**

Felder:

- **Kirchengemeinden**: Gemeinden. Dieses Feld ist erforderlich.
- **Veranstaltungarten**: Filter nach Art der Veranstaltung.
- **Nur aktiv auf folgendem Werbekanal beworbene Veranstaltungen anzeigen**: Filtert auf einen Werbekanal.
- **Auf folgende Veranstaltungsorte begrenzen**: Optionaler Ortsfilter.
- **Auf folgende Stichworte begrenzen**: Optionaler Stichwortfilter.
- **Anzahl der angezeigten Tage**: Zeitraum ab heute. Leer bedeutet unbegrenzt.
- **Maximale Anzahl der angezeigten Veranstaltungen**: Anzahlbegrenzung.
- **Vorlage**: Layoutvorlage. Dieses Feld ist erforderlich.
- **Aufrufende Website**: Website, auf der der Code laufen darf. Dieses Feld ist erforderlich.
- **Zusätzliche Vorlagenfelder**: Wenn die gewählte Vorlage eigene Felder hat, erscheinen diese darunter.

Wenn **Aufrufende Website** fehlt, weist die Seite darauf hin, dass dieses Feld zwingend ausgefüllt werden muss. Wenn alles ausgefüllt ist, erscheint **HTML-Code zum Kopieren** mit **Kopieren**.

---

## Inaktive oder besondere Berichte

Einige Berichte sind technisch vorhanden, aber nur in bestimmten Situationen sichtbar:

- **Gemeindebrief (BL)**: Nur für berechtigte Benutzer:innen der passenden Gemeinde sichtbar.
- **KonfiApp-Berichte**: Nur sichtbar, wenn eine KonfiApp-Anbindung für eine Gemeinde aktiv ist.
- **Website-Berichte**: Nur sinnvoll, wenn der erzeugte HTML-Code auf einer passenden Website eingebunden werden kann.
- **Veranstaltungswerbung**: Empfohlenes Modell für neue Website-Einbindungen.

---

## Verwandte Kapitel

- [Veranstaltungen und Gottesdienste](veranstaltungen.md): Daten pflegen, die in Berichten erscheinen
- [Sammeleingaben](eingaben.md): Opfer, Kinderkirche und Dienste gesammelt bearbeiten
- [Kasualien](kasualien.md): Taufen, Trauungen und Beerdigungen für Berichte erfassen
- [Urlaubsplan](urlaubsplan.md): Abwesenheiten für Urlaubs- und Dienstreiseformulare
- [Administration](administration.md): Rechte, Integrationen und Stammdaten verwalten
