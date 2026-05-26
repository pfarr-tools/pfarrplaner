[//]: # (TOC: 14. Administration)

# Administration

Der Administrationsbereich ist für Benutzer mit Administratorrechten bestimmt. Hier werden Benutzerkonten, Rollen, Gemeinden, Standorte und weitere Stammdaten verwaltet.

Änderungen in der Administration wirken oft auf viele Bereiche von Pfarrplaner. Ein neu gesperrtes Konto kann sich nicht mehr anmelden, eine geänderte Gemeinde erscheint im Kalender anders, und geänderte Berechtigungen können Schaltflächen ein- oder ausblenden. Arbeiten Sie hier deshalb besonders sorgfältig.

> **Hinweis:** Dieses Kapitel ist nur für Personen relevant, die als Administrator eingerichtet wurden. Wenn Sie im Menü keinen Punkt „Administration" sehen, haben Sie keinen Administratorzugang.

---

## Administrationsbereich aufrufen

Klicken Sie im Hauptmenü auf **„Administration"**.

![Administrationsübersicht](media/images/admin-uebersicht.png)

---

## Benutzerverwaltung

### Benutzerübersicht

![Benutzerübersicht](media/images/admin-benutzerliste.png)

Unter **Administration → Benutzer** finden Sie eine Liste aller registrierten Benutzer.

Die Liste zeigt je nach Installation **Name**, **E-Mail-Adresse**, **Rollen**, **Gemeinden**, **Status** und Schaltflächen zum Bearbeiten. Nutzen Sie die Suche oder Sortierung, falls viele Benutzer vorhanden sind.

### Neuen Benutzer anlegen

1. Klicken Sie auf **„Neuer Benutzer"**.
2. Füllen Sie die Pflichtfelder aus:
   - **Name**: Vollständiger Name (erscheint im Kalender und in Berichten)
   - **E-Mail**: Diese Adresse dient als Benutzername und für Benachrichtigungen
   - **Passwort**: Vergeben Sie ein Anfangspasswort. Die Zugangsdaten werden automatisch per E-Mail verschickt; nach der ersten Anmeldung muss die Person das Passwort ändern.
3. Weisen Sie dem Benutzer eine oder mehrere **Rollen** zu (z. B. „Pfarrer", „Organist").
4. Wählen Sie die **Gemeinden**, auf die der Benutzer Zugriff haben soll.
5. Klicken Sie auf **„Speichern"**.

Weitere mögliche Felder sind:

- **Telefon oder Kontaktdaten**: Für interne Rückfragen, falls genutzt.
- **Adresse**: Nur ausfüllen, wenn Ihre Gemeinde diese Angabe benötigt.
- **Standardgemeinde**: Gemeinde, die für diesen Benutzer vorausgewählt wird.
- **Aktiv / gesperrt**: Steuert, ob sich die Person anmelden darf.
- **Benachrichtigungen**: Legt fest, ob die Person E-Mails aus Pfarrplaner erhält, falls angeboten.

### Benutzer bearbeiten

Klicken Sie in der Benutzerliste auf den Namen eines Benutzers, um seine Daten zu bearbeiten.

Prüfen Sie beim Bearbeiten besonders E-Mail-Adresse, Rollen und Gemeinden. Diese drei Angaben bestimmen Anmeldung, Rechte und sichtbare Daten.

Oben im Fenster stehen die Schaltflächen **Speichern**, **Passwort zurücksetzen** und **Löschen**. Speichern Sie Änderungen, bevor Sie den Editor verlassen. Das Passwort setzen Sie nur zurück, wenn die Person keinen Zugang mehr hat oder ein neues Anfangspasswort benötigt. Löschen Sie Personen nur, wenn sicher ist, dass sie nicht mehr in Gottesdiensten, Kasualien oder Berichten benötigt werden.

Der Reiter **Person** enthält die persönlichen Stammdaten. Hier bearbeiten Sie Titel, Vorname, Nachname, Dienststelle, Adresse, Telefonnummer, E-Mail-Adresse und das Benutzerbild. Diese Angaben erscheinen an verschiedenen Stellen im Pfarrplaner, zum Beispiel in Listen, im Kalender oder in Ausgaben.

![Benutzer bearbeiten: Person](media/images/admin-benutzer-person.png)

Im Reiter **Benutzerkonto** sehen Sie, ob die Person ein eigenes Login-Konto besitzt. Personen ohne Login können in Pfarrplaner trotzdem als Beteiligte vorkommen, melden sich aber nicht selbst an. Bei Personen mit Konto ist die E-Mail-Adresse zugleich der Benutzername. Außerdem ordnen Sie hier die Heimatgemeinden und, falls genutzt, die Pfarrämter der Person zu.

![Benutzer bearbeiten: Benutzerkonto](media/images/admin-benutzer-konto.png)

Im Reiter **Berechtigungen** legen Sie fest, was die Person sehen und bearbeiten darf. Rollen bündeln mehrere Rechte, zum Beispiel für Administratoren, Pfarrerinnen oder Kirchenmusik. In der Gemeindeliste wählen Sie zusätzlich pro Kirchengemeinde aus, ob die Person keinen Zugriff, Leserechte, Schreibrechte oder Administrationsrechte hat. Daneben stellen Sie ein, ob die Person E-Mail-Benachrichtigungen für eigene oder alle Gottesdienste einer Gemeinde erhalten soll.

![Benutzer bearbeiten: Berechtigungen](media/images/admin-benutzer-berechtigungen.png)

Im Reiter **Menü** schalten Sie einzelne Bereiche im Hauptmenü der Person ein oder aus. Das ist hilfreich, wenn jemand nur wenige Aufgaben im Pfarrplaner erledigt und nicht alle Menüpunkte sehen soll. Ausgeblendete Menüpunkte ersetzen keine Berechtigungen; entscheidend bleiben die Rechte aus dem Reiter **Berechtigungen**.

![Benutzer bearbeiten: Menü](media/images/admin-benutzer-menue.png)

Im Reiter **Startseite** richten Sie ein, welche Startseiten-Reiter die Person sieht. Je nach Einrichtung können dort Kalender, Listen, persönliche Aufgaben oder andere Übersichten erscheinen. Nutzen Sie diese Einstellungen, um der Person die wichtigsten Informationen direkt nach dem Anmelden anzuzeigen.

![Benutzer bearbeiten: Startseite](media/images/admin-benutzer-startseite.png)

Der Reiter **Alle Einstellungen** ist nur für Ausnahmefälle gedacht. Dort werden gespeicherte Benutzereinstellungen direkt angezeigt und können geändert oder gelöscht werden. Ändern Sie hier nur etwas, wenn Sie genau wissen, welche Einstellung betroffen ist.

Im Reiter **Urlaub** legen Sie fest, ob für diese Person Abwesenheiten verwaltet werden. Wenn Urlaub aktiviert ist, können Sie einstellen, ob Abwesenheiten im Gottesdienstplan sichtbar sind, ob eine Vertretung benötigt wird und ob Anträge geprüft oder genehmigt werden müssen.

![Benutzer bearbeiten: Urlaub](media/images/admin-benutzer-urlaub.png)

### Passwort zurücksetzen

Im Benutzer-Editor finden Sie die Schaltfläche **„Passwort zurücksetzen"**. Damit erhalten Benutzer eine E-Mail mit einem Link zum Zurücksetzen ihres Passworts.

### Benutzer löschen oder sperren

- **Löschen**: Entfernt den Benutzer dauerhaft (nicht empfohlen, da damit auch Verknüpfungen zu Gottesdiensten verloren gehen können).
- **Sperren**: Deaktiviert das Konto, ohne es zu löschen. Der Benutzer kann sich dann nicht mehr anmelden.

---

## Rollen und Berechtigungen

### Was sind Rollen?

Rollen bündeln Berechtigungen. Statt jedem Benutzer einzeln Rechte zuzuweisen, weisen Sie ihm eine Rolle zu (z. B. „Kirchenmusiker"), die dann alle nötigen Rechte enthält.

### Rollen verwalten

![Rollen verwalten](media/images/admin-rollen.png)

Unter **Administration → Benutzerrollen** sehen Sie alle vorhandenen Rollen.

- **Neue Rolle anlegen**: Öffnen Sie die Benutzerrollen und legen Sie eine neue Rolle an. Geben Sie einen Namen ein und wählen Sie die zugehörigen Berechtigungen aus der Liste.
- **Rolle bearbeiten**: Klicken Sie auf eine Rolle, um ihre Berechtigungen anzupassen.
- **Rolle löschen**: Rollen löschen Sie über die Schaltfläche „Löschen" in der Rollenansicht.

### Benutzer einer Rolle zuweisen

Öffnen Sie den Benutzer-Editor über **Administration → Benutzer** und wählen Sie im Feld **Rollen** die gewünschte Rolle aus.

---

## Teams

Teams ermöglichen die Bündelung von Benutzern in Gruppen (z. B. alle Organisten eines Kirchenbezirks). Teams können für Berichte und Dienstpläne genutzt werden.

Unter **Administration → Teams** legen Sie neue Teams an und weisen ihnen Mitglieder zu. Die Übersicht zeigt **Team**, **Kirchengemeinde**, **Mitglieder** sowie Schaltflächen zum Bearbeiten und Löschen.

### Team bearbeiten

Der Team-Editor heißt nach dem Teamnamen. Oben stehen:

- **Speichern**: Speichert das Team.
- **Löschen**: Löscht das Team nach Rückfrage.

Typische Felder sind:

- **Bezeichnung**: Verständlicher Name, zum Beispiel **Organisten Bezirk Nord**.
- **Kirchengemeinde**: Gemeinde, zu der das Team gehört.
- **Mitglieder**: Benutzer:innen, die zu diesem Team gehören.

Wenn Sie ein Team später in einem Personenfeld auswählen, speichert Pfarrplaner nicht das Team selbst beim Gottesdienst. Stattdessen trägt Pfarrplaner die Mitglieder dieses Teams einzeln ein. Das ist wichtig, weil spätere Änderungen am Team nicht automatisch alte Gottesdienste verändern.

---

## Kirchengemeinden

Kirchengemeinden öffnen Sie über **Administration** und dann im Bereich **Orte** über den Namen der Gemeinde. Eine Kirchengemeinde bündelt Gottesdienstorte, Pfarrämter, Opferangaben, Streaming-Einstellungen und weitere Verknüpfungen.

Im Reiter **Allgemeines** pflegen Sie den Namen, die offizielle Bezeichnung, die Homepage und das Logo. Außerdem können Sie festlegen, ob es sich um eine Sammelgemeinde handelt. Eine Sammelgemeinde fasst mehrere Einzelgemeinden zusammen und wird verwendet, wenn mehrere Orte gemeinsam geplant oder angezeigt werden sollen.

![Kirchengemeinde bearbeiten: Allgemeines](media/images/admin-gemeinde-allgemein.png)

Im Reiter **Opfer** hinterlegen Sie Standardangaben für Opferzwecke. Diese Werte helfen beim Anlegen von Gottesdiensten, Beerdigungen und Trauungen, weil Pfarrplaner bei leeren Feldern sinnvolle Vorschläge übernehmen kann. Außerdem nutzt Pfarrplaner diese Standardwerte in Ausgaben, Berichten, E-Mails und öffentlichen Texten automatisch, wenn beim einzelnen Gottesdienst kein eigener Opferzweck oder keine eigene Opferanmerkung eingetragen ist. Die allgemeine Spendenseite sowie IBAN und BIC können für öffentliche Hinweise oder Ausgaben verwendet werden.

![Kirchengemeinde bearbeiten: Opfer](media/images/admin-gemeinde-opfer.png)

Im Reiter **Pfarrämter** verwalten Sie die Pfarrämter der Gemeinde. Ein Pfarramt hat eine Bezeichnung, ein Kürzel und Kontaktdaten. Diese Angaben werden zum Beispiel bei Zuständigkeiten, öffentlichen Informationen oder internen Listen genutzt.

![Kirchengemeinde bearbeiten: Pfarrämter](media/images/admin-gemeinde-pfarramt.png)

Im Reiter **Streaming** stehen Einstellungen für YouTube-Übertragungen. Dort tragen Sie den Kanal ein, wählen Streamschlüssel aus und legen fest, ob Sendungen automatisch gestartet oder beendet werden sollen. Die Einstellung für Kindersendungen und die Frist für private Aufzeichnungen betreffen die Veröffentlichung auf YouTube.

![Kirchengemeinde bearbeiten: Streaming](media/images/admin-gemeinde-streaming.png)

Im Reiter **Veranstaltungsorte** verwalten Sie Kirchen, Gemeindehäuser, Friedhofskapellen und andere Orte, an denen Termine stattfinden. Von hier aus legen Sie neue Orte an oder öffnen einen bestehenden Ort zur Bearbeitung.

![Kirchengemeinde bearbeiten: Veranstaltungsorte](media/images/admin-gemeinde-orte.png)

Im Reiter **Weitere Integrationen** finden Sie externe Dienste wie KonfiApp oder CommuniApp, falls Ihre Installation diese Anbindungen nutzt. Tragen Sie Zugangsdaten nur ein, wenn sie Ihnen von der jeweiligen Plattform bereitgestellt wurden.

![Kirchengemeinde bearbeiten: Weitere Integrationen](media/images/admin-gemeinde-integrationen.png)

---

## Standorte / Kirchen

Standorte sind konkrete Orte für Gottesdienste und andere Termine, zum Beispiel eine Kirche, ein Gemeindehaus oder eine Friedhofskapelle. Sie öffnen einen Standort über den Reiter **Veranstaltungsorte** einer Kirchengemeinde.

Im Reiter **Allgemeines** bearbeiten Sie den Namen des Ortes, die übliche Gottesdienstzeit, den normalen Ort für parallelen Kindergottesdienst und die Texte, mit denen der Ort in Gottesdiensten angezeigt wird. Das Feld **Wichtige Informationen für Besucher** eignet sich für Hinweise wie Barrierefreiheit, Parkplätze oder Zugang über einen Seiteneingang.

![Standort bearbeiten: Allgemeines](media/images/admin-standort-allgemein.png)

### Sitzplätze und Sitzplan

Im Reiter **Sitzplätze** verwalten Sie den Sitzplan eines Standorts. Ein Sitzplan besteht aus **Bereichen** und **Reihen**. Bereiche sind größere Teile des Raums, zum Beispiel Mittelschiff, Seitenschiff oder Empore. Reihen gehören zu einem Bereich und enthalten die eigentlichen Sitzplätze.

![Standort bearbeiten: Sitzplätze](media/images/admin-standort-sitzplaetze.png)

Mit **Bereich hinzufügen** legen Sie einen neuen Bereich an. Ein Bereich hat eine **Bezeichnung**, eine **Priorität** und eine **Farbe**. Die Priorität bestimmt die Reihenfolge, in der Bereiche bei der Sitzplatzvergabe berücksichtigt werden. Die Farbe hilft, Bereiche in Listen und Plänen schneller zu unterscheiden.

![Sitzplan: Bereich bearbeiten](media/images/admin-sitzplan-bereich.png)

Mit **Reihe hinzufügen** legen Sie eine neue Reihe an. Wählen Sie den zugehörigen Bereich, geben Sie eine Bezeichnung ein und tragen Sie die maximale Anzahl der Sitzplätze ein. Im Feld **Teilmöglichkeit** können Sie festlegen, wie eine Reihe sinnvoll aufgeteilt werden kann. Der Wert **6,6** bedeutet zum Beispiel, dass eine Reihe mit zwölf Plätzen in zwei Blöcke mit je sechs Plätzen geteilt werden kann.

![Sitzplan: Reihe bearbeiten](media/images/admin-sitzplan-reihe.png)

Sitzpläne sind vor allem dann wichtig, wenn Pfarrplaner für Gottesdienste oder Veranstaltungen Platzreservierungen nutzen soll. Ohne Sitzplan kann ein Ort trotzdem ganz normal im Kalender und in Berichten verwendet werden.

---

## Liederverwaltung

Pfarrplaner enthält eine Datenbank mit Liedern, Psalmen und liturgischen Texten, die im Liturgie-Editor genutzt werden.

### Liederbücher

Unter **Administration → Liederbücher** legen Sie fest, welche Liederbücher in Ihrer Installation verfügbar sind (z. B. EG – Evangelisches Gesangbuch, RG – Reformiertes Gesangbuch).

### Lieder

Unter **Administration → Lieder** können Sie einzelne Lieder anlegen, bearbeiten oder löschen. Für jedes Lied wird angegeben:
- Titel
- Gesangbuch und Nummer
- Strophenanzahl
- Töne / Textinhalte (optional)
- Rechte- oder Quellenhinweise, falls Ihre Installation diese Felder nutzt
- Suchbegriffe oder alternative Titel, falls vorhanden

### Psalmen

Unter **Administration → Psalmen** verwalten Sie Psalmtexte für den liturgischen Gebrauch.

Typische Felder sind **Psalmnummer**, **Titel**, **Text**, **Kehrvers** und **Quelle**.

### Liturgische Texte

Unter **Administration → Liturgische Texte** finden Sie feste liturgische Formeln und Texte (Kyrie, Gloria, Kollektengebete o. ä.), die im Liturgie-Editor als Bausteine verwendet werden können.

Pflegen Sie diese Texte so, wie sie später auf Liedblättern oder in Abläufen erscheinen sollen.

---

## Vertretungs-Pools

Vertretungs-Pools gehören zur Urlaubs- und Vertretungsplanung. Ein **Pool** ist eine Gruppe von Personen, die gemeinsam für Vertretungen zuständig sein kann. Ein Pool kann außerdem feste Kontaktangaben haben, zum Beispiel wenn ein Dekanatamt als Kontaktstelle genannt werden soll.

Pools werden im [Urlaubsplan](urlaubsplan.md) verwendet, wenn bei einer Abwesenheit nicht nur eine einzelne Vertreter:in, sondern eine Pool-Struktur eingetragen wird. Dann kann Pfarrplaner anzeigen, welche Poolmaster:innen im betreffenden Zeitraum zuständig sind.

### Pool-Übersicht

![Pool-Übersicht](media/images/admin-pools-uebersicht.png)

Unter **Administration → Vertretungs-Pools** finden Sie die Übersicht **Pools**.

Die Übersicht zeigt:

- **Neuer Pool**: Legt einen neuen Pool an, wenn Sie dazu berechtigt sind.
- **Kirchengemeinden**: Gemeinden, für die der Pool gilt.
- **Teilnehmer**: Personen, die zum Pool gehören.

### Pool bearbeiten

![Pool bearbeiten](media/images/admin-pool-editor.png)

Im Pool-Editor stehen:

- **Speichern**: Übernimmt die Änderungen.
- **Bezeichnung des Pools**: Verständlicher Name des Pools.
- **Zugehörige Kirchengemeinden**: Gemeinden, für die der Pool gilt.
- **Zugehörige Personen**: Personen im Pool.
- **Ansprechperson**: Feste Kontaktperson, falls der Pool über eine zentrale Stelle laufen soll.
- **Institution/Amt**: Amt oder Stelle der festen Kontaktperson.
- **Telefon**: Telefonnummer der Kontaktstelle.
- **E-Mailadresse**: E-Mailadresse der Kontaktstelle.

Die festen Kontaktinformationen sind optional. Wenn sie eingetragen sind, können sie in Vertretungsangaben verwendet werden, ohne dass eine konkrete Benutzer:in als Poolmaster:in hinterlegt sein muss.

### Poolmaster:innen

Ein:e **Poolmaster:in** ist eine Person, die für einen Pool in einem bestimmten Zeitraum zuständig ist. Poolmaster-Zeiten werden in der Regel aus dem [Urlaubsplan](urlaubsplan.md) heraus angelegt, über die Schaltfläche **Poolmaster:in werden**.

Im Poolmaster-Editor gibt es:

- **Speichern**: Speichert den Einsatz.
- **Löschen**: Löscht den Einsatz.
- **Pool**: Der Pool, für den die Zuständigkeit gilt.
- **Zeitraum**: Beginn und Ende der Zuständigkeit.

Poolmaster-Einsätze erscheinen im Urlaubsplan als eigene Einträge und werden bei Vertretungen über Pools berücksichtigt.

---

## Werbung / Ankündigungskanäle

Unter **Administration → Kirchengemeinden → Werbung** verwalten Sie zusätzliche Werbekanäle für eine Kirchengemeinde. Solche Kanäle erscheinen später im Reiter **Werbung** im [Editor für Veranstaltungen und Gottesdienste](veranstaltungen.md).

Pfarrplaner kennt außerdem einige feste Kanäle, die automatisch vorhanden sind:

| Kanal | Bedeutung |
|---|---|
| **Bekanntgaben: extra Text** | Der Eintrag soll in Bekanntgaben besonders erwähnt werden. |
| **CommuniApp: eigene Veranstaltung** | Der Eintrag soll als eigene Veranstaltung an die CommuniApp übergeben werden. Dieser Kanal erscheint nur, wenn bei der Kirchengemeinde eine CommuniApp-Anbindung eingerichtet ist. |
| **Newsletter: Feature (Bild + Text)** | Der Eintrag soll für einen Newsletter mit Bild und Text verwendet werden. |
| **Powerpoint: extra Folie** | Der Eintrag soll als zusätzliche Folie in einer Präsentation erscheinen. |
| **Als Story posten** | Der Eintrag soll in einem Story-Format vorbereitet werden. |

Eigene Werbekanäle sind einfache Namen, zum Beispiel **Homepage**, **Schaukasten**, **Instagram** oder **Gemeindebrief-Highlight**. Sie steuern nicht automatisch eine externe Schnittstelle, helfen aber dabei, Veranstaltungen gezielt für bestimmte Ausgaben freizugeben.

### Werbekanal bearbeiten

Die Seite heißt **Neuer Werbekanal** oder **... bearbeiten**.

Oben stehen:

- **Speichern**: Speichert den Werbekanal.
- **Löschen**: Löscht einen vorhandenen Werbekanal nach Rückfrage.

Feld:

- **Bezeichnung**: Name des Werbekanals. Dieser Name erscheint später im Reiter **Werbung** bei jeder Veranstaltung dieser Kirchengemeinde.

Wenn ein Werbekanal nicht mehr gebraucht wird, prüfen Sie vorher, ob er noch in künftigen Veranstaltungen verwendet wird. Nach dem Löschen kann er dort nicht mehr neu ausgewählt werden.

---

## Verwandte Kapitel

- [Anmeldung und Benutzerkonto](anmeldung.md): Wie Benutzer sich anmelden
- [Persönliche Einstellungen](einstellungen.md): Was Benutzer selbst ändern können
- [Kalender](kalender.md): Wo Gemeinden, Orte und Benutzer sichtbar werden
- [Liturgie-Editor](liturgie.md): Wo Lieder und liturgische Texte verwendet werden
- [Berichte und Ausgaben](berichte.md): Welche Daten aus der Administration in Ausgaben erscheinen
- [Öffentliche Seiten und Freigabelinks](oeffentliche-seiten.md): Welche Seiten ohne normale Anmeldung erreichbar sind
