Revision 1 des Handbuchs
========================

# Build architecture
- DONE: Build mkdocs first. Then use mkdocs-to-pdf plugin (should be installed already) to create a much better formatted pdf manual. This should be called "handbuch.pdf" and included in the mkdocs site. If this means, a dummy is needed to build mkdocs before it exists, create an empty dummy handbuch.pdf first.
- DONE: Completely remove the local copy of the manual in public/ and public/handbuch.pdf; as well as the manual-related controllers and views. Instead, point the "help button" in the navbar to the appropriate section of the manual at https://handbuch.pfarrplaner.de.
- DONE: Could the docs be somehow completely separated from the codebase, even in an own repository? (maybe as a git sub-repo?). You could use ssh://git@codeberg.org/pfarr.tools/pfarrplaner-manual.git as remote origin for that and overwrite the existing content with a force push.

# Allgemeines

DONE: Die "preface"-Kapitel 0.x sollten nicht nummeriert werden.

# Allgemeines
- Wenn mkdocs die Fonts Material Design Icons und FontAwesome einbinden kann, sollten beschriebene Symbole/Icons auch im Text erscheinen. Das gilt für alle Texte, in denen Bezug auf Symbole genommen wird! Das gilt auch für alle Stellen, an denen Titel von Schaltflächen erwähnt werden, die Icons haben! Auch für Werkzeugleisten!
- Das gilt auch für Verweise auf das Pfarrplaner-Logo. Bilder sollten immer irgendwie sichtbar werden!
- Screenshots mit Listen sollten immer mindestens 10 Beispieleinträge (--> factory/seeder) enthalten.
- Screenshots mit Kalender sollten mindestens 10 Beispielveranstaltungen enthalten, die die unterschiedlichen Kategorien/Farben/Symbole ausreichend illustrieren. Beim Gottesdienstkalender sollten beispielhaft auch Urlaubseinträge vorhanden sein.
- DONE: Screenshots mit Predigt brauchen einen Text, der auch wie eine Predigt aussieht. Schreib einen plausiblen Predigtanfang.
- Wo nur Teile einer Seite beschrieben werden (z. B. bei Reitern, aber auch bei komplexen Eingabeelementen, die aktuell noch keinen eigenen Screenshot haben), sollte über ->screenshotElement ein Teilbereich des Bildschirms abgebildet werden.
- Alle Bilder brauchen Alt-Text/Legende.
- DONE: Die "rechte Seitenleiste" ist nur ein Dropdown und braucht deshalb eine andere Bezeichnung (Seiteneinstellungen?)

# Inhaltliche Verbesserungen

## Titelseite
DONE: Die eigene Titelseite wurde entfernt; MkDocs erzeugt die PDF-Titelseite.

## Versionsangaben
DONE: Zu den Versionsangaben gehört ein Abschnitt mit den Änderungen der letzten Haupt- und Unterversionen (Was ist neu in Version ___?)

## Allgemeiner Seitenaufbau
- DONE: "Auf kleinen Bildschirmen wird das Menü über eine Schaltfläche oben geöffnet." --> mdi-menu Symbol fehlt.

## Anmeldung und Benutzerkonto
- DONE: "Ihr Administrator legt Ihr Konto an, weist Ihnen die nötigen Rechte zu und teilt Ihnen ein Anfangspasswort mit. Bitte ändern Sie dieses Passwort nach Ihrer ersten Anmeldung." -> Zugangsdaten kommen nach Anlage durch den Admin automatisch per Mail. Das Passwort MUSS nach dem ersten Anmelden geändert werden.

## Startseite
- DONE: Der Screenshot zeigt eine ErrorException (Undefined array key "ministries").
- DONE: Die einzelnen Reiter brauchen jeweils einen eigenen Screenshot!

## Kalender
- DONE: Die "wichtigen Symbole" fehlen als Abbildung.
- DONE: Beim Gottesdienstkalender fehlt ein Element-Screenshot eines gehoverten Gottesdiensts mit den Bearbeiten-Buttons.
- DONE: Beim Gottesdienstkalender fehlt ein Element-Screenshot des Dialogs/Popups für den Selbsteintrag.
- DONE: Beim Gottesdienstkalender fehlt ein Element-Screenshot des Dropdowns mit den Seiteneinstellungen.
- DONE: Beim Veranstaltungskalender fehlt ein weiterer Screenshot mit dem Bearbeiten-Dialog/Popup.

## Veranstaltungen und Gottesdienstkalender
- DONE: Der Unterschied zwischen Veranstaltungen und Gottesdiensten sollte erklärt werden. Außerdem nimmt die Seite in den Überschriften nur Bezug auf "Gottesdienst". Wie legt man eine neue Veranstaltung an? Wie bearbeitet, löscht man sie? Der "Gottesdiensteditor" bearbeitet eben auch Veranstaltungen und sollte deshalb vermutlich überall(!) seine Bezeichnung ändern!
- DONE: Vom Reiter "Wiederholungen" fehlt ein Screenshot. Dazu braucht es im Test eine "andere Veranstaltung".
- DONE: Ankündigungskanäle und ihre Funktion brauchen irgendwo(?) eine ausführlichere Dokumentation.
- DONE: Dokumentiert werden sollte hier auch, welcher Kanal welche Bildgröße verwendet. (Kasten mit Tabelle?)
- DONE: Reiter Kommentare: Bild braucht Beispielkommentare.
- DONE: Weitere Aktionen: Element-Screenshot des Dropdowns

## Liturgie-Editor
- DONE: Der Liturgie-Editor braucht unbedingt eine beispielhafte Liturgie für den Screenshot. Am besten folgst du dabei der Liturgie des Württembergischen Predigtgottesdiensts:
    - Eröffnung und Anrufung
        - Freitext: Glockengeläut / leer / Mesner:in
        - Freitext: Musik zum Eingang / leer / Organist:in
        - Lied: Lied / leer / Organist:in
        - Freitext: Eingangswort / leer / Pfarrer:in
        - Freitext: Votum / Wir feiern Gottesdienst im Namen Gottes,... / Pfarrer:in
        - Psalm: Psalmgebet / leer / Pfarrer:in
        - Freitext: Ehr sei dem Vater / Ehr sei dem Vater und dem Sohn... / Pfarrer:in
        - Freitext: Eingangsgebet / leer / Pfarrer:in
        - Freitext: Stilles Gebet / leer / Pfarrer:in
    - Verkündigung und Bekenntnis
        - Schriftlesung: Schriftlesung / leer / Schriftlesung
        - Lied: Wochen- oder Tageslied / leer / Organist:in
        - Predigt: Predigttext und Predigt / leer / Pfarrer:in
        - Lied: Lied / leer Organistin

- DONE: Erklärung: Was ist eine Liturgievorlage? Wie erstelle ich die? Wo verwende ich die?
- DONE: Bei der Ablaufübersicht braucht es einen Kasten, der etwas über die Zeitberechnung erklärt.
- DONE: Elementsymbole könnten in einer Tabelle (mit Symbol!) erklärt werden. Dazu gehört jeweils eine kurze Beschreibung, was sich hinter diesem Element verbirgt, also, was man damit machen kann.
- DONE: Es gibt kein eigenes Griffsymbol zum ziehen. Gezogen wird an den jeweiligen Elementsymbolen.
- DONE: Bei den Abschnitten zu den Elementtypen gehört jeweils das passende Symbol dazu.
- DONE: Freier Text braucht die Erklärung, dass hierzu normalerweise nichts in Liedblättern und Präsentationen erscheint, das aber über die beiden dazu gehörigen Eingabefelder optional geschehen kann.
- DONE: Schriftlesung braucht mehr Erklärung zu "freier Text" -- hier kann z.B. eine eigene Übersetzung des Texts oder eine Version, die im System nicht installiert wurde, eingegeben werden.
- DONE: Bei Psalmen und Liedern braucht es den Hinweis, dass der Pfarrplaner an sich keine solchen Inhalte bereitstellt, sondern es sich um von Benutzern erstellte Inhalte (Verweis auf Administration) handelt.
- DONE: Unter "Herunterladen und Ausgaben" fehlt eine vollständige Übersicht (Tabelle?) aller möglichen Ausgaben, jeweils mit einer kurzen Beschreibung.
- DONE: Bei den "Konfiguration: ..."- Abschnitten braucht es Screenshots der jeweiligen Dialogfelder.
- DONE: Songbeamer:
    - Platzhalter... legt im Ablauf Notizelemente an den Stellen an, wo später Jingle; Intro, usw. eingefügt werden sollen.
    - Mitwirkende anzeigen: Legt eine Textfolie mit allen Mitwirkenden an.
    - Liederliste am Anfang: Legt eine Textfolie mit Liederliste (Liednummerntafel) an.
- DONE: Powerpoint: Hier müssen die einzelnen Reiter jeweils ausführlich (alle Elemente!) dokumentiert und mit Screenshots versehen werden.
- DONE: Personenbezogene Word-Ausgabe braucht mehr Erklärung: Hier kann derselbe Gottesdienst als Volltext mehrfach in unterschiedlichen Ausgaben für verschiedene Beteiligte ausgegeben werden. Dabei werden deren Texte jeweils markiert bzw. überhaupt abgedruckt.
- DONE: Nur-Lesen-Ansicht gehört vielleicht einfach vor alle Erklärungen zur Bearbeitung.

## Kasualien
- Die Übersicht braucht eine Beispielsuche mit gefundenen Beispielinhalten.
- DONE: Die Startseitenreiter brauchen Screenshots, ebeson der Gottesdiensteditor.
- DONE: "Dimissoriale" ist die Erlaubnis, die benötigt wird, wenn für die Person(en) einer Kasualie eigentlich ein anderes Pfarramt zuständig ist.
- DONE: Löschhinweis: Füge hinzu, dass, wenn ein Gottesdienst gelöscht wird, auch alle damit verbundenen Kasualien gelöscht werden.

## Urlaubsplan
- Der Urlaubsplan braucht für den Screenshot mehr Personen und mehr Einträge. Personen sollten den verschiedenen Kategorien (Pfarrer:innen, Mitarbeitende, Ausgeblendet) zugewiesen sein und verschiedene Urlaubseinträge (bei Pfarrer:innen auch Poolmaster:inneneinträge) haben. Dabei sollte nie der komplette Monat abgedeckt sein.
- DONE: Poolmaster:in --> "Diese Zuständigkeit ist kein normaler Urlaub" = "Diese Zuständigkeit ist kein Urlaub".
- DONE: Kann bei der Beschreibung der Farben jeweils ein Feld/Symbol in der beschriebenen Farbe eingefügt werden?
- DONE: Downloads: Urlaubsantrag/Dienstreiseantrag sind mit bestimmten Rollen (Pfarrer:in?) verbunden.
- Startseitenreiter brauchen Screenshots.
- Die öffentliche Pool-Übersicht braucht Inhalt: Mehrere Pfarrer:innen, darunter welche, die aktuell im Urlaub oder Vertreter:in/Poolmaster:in sind.

## Sammeleingaben
- Brauchen Screenshots von allen zugehörigen Seiten/Views.

## Berichte und Ausgaben
- Brauchen Screenshots von allen zugehörigen Seiten/Views.
- Perfekt wäre es, wenn der Dusk-Test darüber hinaus den jeweiligen Bericht einmal abrufen könnte und dieser als Mediendatei ins Handbuch mit eingebunden wäre.
- DONE: Gottesdienste nach AZE-Kategorie. Hier braucht es mehr Erklärung: Beschäftigte wie Mesner:innen haben eine AZE (Arbeitszeitermittlung) zu ihrer Stelle. Dazu werden unterschiedliche Gottesdienstformen mit unterschiedlichen Zeitbudgets gerechnet. Diese Auswertung hilft bei der Berechnung, bei wievielen Gottesdiensten ein:e Mesner:in in welcher Kategorie schon gearbeitet hat?
- DONE: Bei den Website-Embeds fehlt der Hinweis, dass das Feld "Aufrufende Website" unbedingt ausgefüllt werden muss, weil die Einbindung sonst vom Browser (CORS) blockiert wird. Außerdem kann darauf hingewiesen werden, dass "Veranstaltungswerbung" das Embed-Modell der Zukunft ist und die anderen Einbindungen langfristig dahin migriert werden.
- DONE: Meldung an das Ordnungsamt muss nicht erwähnt werden.
- DONE: Die KonfiApp-Berichte müssen (mit Hinweis auf die Integriation und Link zu https://www.konfiapp.de) dokumentiert werden!

## Persönliche Einstellungen
- DONE: E-Mailbenachrichtigungen braucht mehr Testdaten -- der Benutzer muss für mindestens eine Gemeinde (besser mehrere) Lese- oder Schreibrechte haben, damit Einstellungen überhaupt sichtbar werden.
- DONE: Startseite könnte ruhig eine Konfiguration haben, in der der Benutzer ein paar Reiter aktiviert hat.
- DONE: Der Abschnitt API-Token kann entfernt werden. Diese Funktion ist nicht über die UI zugänglich.
- DONE: Verbundene Kalender hat einen falschen Screenshot.
- DONE: Bei Externe Inhalte geht es um externe Links, die zum eigenen Profil gehören.

## Administration
- DONE: Der Index-Screenshot braucht mehr Beispielorte
- DONE: Der Screenshot der Benutzerverwaltung enthält keine sichtbaren Benutzer.
- DONE: Bei Benutzer bearbeiten fehlt die Text-Dokumentation der einzelnen Reiter. Screenshots sind vorhanden.
- DONE: Bei Teams fehlt der Hinweis, dass wenn in einem Personenfeld ein Team ausgewählt wird, dieses nicht gespeichert, sondern durch die zugehörigen Personen ersetzt wird.
- Kirchengemeinden, Standorte, und alle weiteren Admin-Module brauchen Screenshots und eine ausführliche Dokumentation aller Seiten/Reiter basierend auf den Vue-Komponenten.
- DONE: Bei Standorten muss die Funktionalität der Sitzpläne ausführlich dokumentiert werden.
- DONE: Öffentliche Seiten sollte ein komplett eigenes Kapitel sein. Die jeweiligen Seiten brauchen für die Screenshots mehr Beispielinhalte.
- DONE: Die "Öffentliche Infoseite" muss nicht erwähnt werden.


# Neue Inhalte

## Lizenz
DONE: Nach den Versionsangaben braucht es einen eigenen Abschnitt zu Lizenzen. Dazu gehört eine Kurzbeschreibung und der vollständige Text der verwendeten Lizenz. Außerdem gehört dazu eine Liste aller verwendeten Packages und ihrer Lizenzen. license_checker sollte das via npm erzeugen können.

# Statische Site (handbuch.pfarrplaner.de)
- DONE: Die statische Site sollte das Pfarrplaner-Logo verwenden. Wenn möglich als Favicon.
- DONE: Gibt es eine Möglichkeit, im Footer Copyrightangaben usw. unterzubringen.
- DONE: Gibt es eine Blätterfunktion (vor / zurück) mit Links zum vorherigen/nächsten Kapitel?
