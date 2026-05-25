[//]: # (TOC: 7. Datenschutz)

# Datenschutz und personenbezogene Daten

Dieses Kapitel beschreibt die Verarbeitung personenbezogener Daten in Pfarrplaner so vollständig wie möglich aus Sicht der Administration. Es soll helfen, das Verzeichnis der Verarbeitungstätigkeiten, Datenschutzinformationen, Berechtigungskonzepte und Löschregeln auf einer realen Installation sauber zu führen.

Pfarrplaner ist kein einzelner Datenbestand, sondern ein Arbeitswerkzeug für viele kirchliche Abläufe. Deshalb verarbeitet die Anwendung personenbezogene Daten an mehreren Stellen gleichzeitig: in Benutzerkonten, Gottesdiensten, Kasualien, Urlaubsabläufen, öffentlichen Freigaben, Berichten, Dateien und Systemprotokollen.

## Wie dieses Kapitel zu lesen ist

Für jeden Verarbeitungsvorgang sind vor allem diese Fragen wichtig:

- **Welche Daten werden verarbeitet?**
- **Zu welchem Zweck geschieht das?**
- **Wer darf die Daten sehen oder ändern?**
- **Werden Daten nach außen weitergegeben oder öffentlich angezeigt?**
- **Welche organisatorischen Regeln sollten Sie dafür festlegen?**

Die rechtliche Bewertung selbst hängt von Ihrer kirchlichen oder staatlichen Datenschutzordnung ab. Dieses Kapitel ersetzt keine juristische Prüfung, beschreibt aber die tatsächlichen Datenflüsse in der Anwendung.

## Grundsatz: Wo in Pfarrplaner personenbezogene Daten vorkommen

Pfarrplaner verarbeitet personenbezogene Daten in diesen großen Bereichen:

- Benutzerkonten und Personenstammdaten
- Rollen, Rechte, Gemeindezuordnungen und Startseiten-Einstellungen
- Gottesdienste, Veranstaltungen und Mitwirkende
- Predigt- und Liturgievorbereitung, soweit Personenbezug enthalten ist
- Taufen, Trauungen und Beerdigungen
- Abwesenheiten, Vertretungen und Pool-Zuständigkeiten
- Anmeldungen, Platzreservierungen und Kontaktlisten
- Kommentare, Dateien und automatisch erzeugte Berichte
- öffentliche Seiten, Freigabelinks und Streaming-Angaben
- technische Protokolle, Backups, Mailversand und API-Zugänge

---

## 1. Benutzerkonten und Personenstammdaten

### Welche Daten verarbeitet werden

In Benutzer- und Personenkonten kommen je nach Nutzung unter anderem vor:

- Titel
- Vorname und Nachname
- E-Mail-Adresse
- Passwort beziehungsweise Passwort-Reset-Status
- Telefonnummer
- Adresse
- Benutzerbild
- Rollen
- Gemeindezuordnungen
- Pfarramtszuordnungen
- Menü- und Startseiten-Einstellungen
- Benachrichtigungseinstellungen
- Urlaubs- und Vertretungsrelevanz
- API- oder Zugangstoken, soweit genutzt

### Zweck

- Anmeldung an der Anwendung
- Rechteprüfung
- Anzeige von Zuständigkeiten und Kontaktmöglichkeiten
- interne Kommunikation und Benachrichtigung
- Planung von Diensten, Urlaub und Vertretungen
- Personalisierung der Oberfläche

### Wer Zugriff hat

- Administratorinnen und Administratoren
- Personen mit passenden Rechten in der Benutzerverwaltung
- in Teilbereichen andere berechtigte Nutzerinnen und Nutzer, wenn Namen, Rollen oder Kontaktdaten in Planungsansichten erscheinen

### Datenschutz-Hinweise für die Administration

- Vergeben Sie Administrationsrechte sparsam.
- Prüfen Sie, ob wirklich alle Benutzerkonten Adress- und Telefondaten brauchen.
- Halten Sie gesperrte und gelöschte Konten organisatorisch auseinander.
- Dokumentieren Sie, wie mit ehemaligen Mitarbeitenden verfahren wird.

---

## 2. Rollen, Rechte und Gemeindezuordnungen

### Welche Daten verarbeitet werden

Pfarrplaner speichert nicht nur Identitätsdaten, sondern auch Zuordnungen, aus denen sich ein personenbezogenes Profil ergibt:

- Rolle einer Person
- Schreib- oder Leserechte in Gemeinden
- Benachrichtigungsregeln
- Zuständigkeit für Urlaub, Prüfung oder Genehmigung
- Sichtbarkeit von Menüpunkten und Startseiten-Reitern

### Zweck

- Steuerung des Datenzugriffs
- Abbildung organisatorischer Zuständigkeiten
- Anzeige arbeitsrelevanter Inhalte

### Datenschutz-Hinweise für die Administration

- Rollen und Detailrechte sollten regelmäßig geprüft werden.
- Sichtbarkeit im Menü ersetzt keine Rechteprüfung und darf organisatorisch nicht missverstanden werden.
- Rechte sollten immer an tatsächliche Aufgaben gebunden sein, nicht an Bequemlichkeit.

---

## 3. Gottesdienste, Veranstaltungen und Mitwirkende

### Welche Daten verarbeitet werden

In Gottesdiensten und Veranstaltungen können personenbezogene Daten vieler Beteiligter stehen, zum Beispiel:

- Namen der Pfarrpersonen, Organist:innen, Mesner:innen und weiterer Mitwirkender
- dienstbezogene Zuordnungen
- Kommentare und interne Hinweise
- predigt- oder liturgierelevante Notizen mit Personenbezug
- Telefonnummer für telefonische Anmeldung
- Ansprechpartnerdaten in Einzelfällen
- Dateien und Anhänge

### Zweck

- Planung und Durchführung von Gottesdiensten und Veranstaltungen
- interne Abstimmung
- Vorbereitung von Ausgaben, Plänen und Berichten

### Wer Zugriff hat

- Personen mit Zugriffsrechten auf die betroffenen Gemeinden und Gottesdienste
- in Sonderfällen Empfänger öffentlicher oder halböffentlicher Ausgaben

### Datenschutz-Hinweise für die Administration

- Prüfen Sie regelmäßig, welche Felder wirklich für öffentliche Ausgaben vorgesehen sind.
- Interne Kommentare dürfen nicht mit öffentlichen Beschreibungen verwechselt werden.
- Datei-Anhänge können wesentlich sensibler sein als die sichtbaren Stammdaten eines Gottesdiensts.

---

## 4. Predigten, Liturgie und vorbereitende Inhalte

### Welche Daten verarbeitet werden

Dieser Bereich wirkt auf den ersten Blick nicht stark personenbezogen, kann aber personenbezogene Inhalte enthalten:

- Namen verantwortlicher Personen in Liturgieelementen
- Predigtnotizen mit Bezug auf konkrete Personen
- interne Vorbereitungstexte
- importierte oder angehängte Dateien mit Personenbezug

### Zweck

- Vorbereitung und Durchführung von Gottesdiensten
- Zusammenarbeit zwischen Beteiligten

### Datenschutz-Hinweise für die Administration

- Schulen Sie Nutzerinnen und Nutzer darin, sensible Seelsorge- oder Gesprächsinhalte nicht unbedacht in freie Textfelder zu schreiben.
- Prüfen Sie Berechtigungen für Liturgie- und Predigtbereiche besonders sorgfältig.

---

## 5. Taufen

### Welche Daten verarbeitet werden

Bei Taufen werden je nach Fall verarbeitet:

- Name des Täuflings
- Geburtsdatum
- Geburtsort
- Adresse
- Postleitzahl und Wohnort
- Telefonnummer
- E-Mail-Adresse
- Pronomen
- Kirchengemeinde
- Erstkontakt mit Datum und Kontaktperson
- Angaben zur Dimissoriale
- Taufgespräch, Taufspruch und Vorbereitung
- Urkunden- und Kirchenbuchstatus
- Dateien und Unterlagen

### Zweck

- Vorbereitung, Durchführung und Nacharbeit einer Taufe
- Verbindung mit einem Taufgottesdienst
- Dokumentation des kirchlichen Verwaltungsvorgangs

### Besondere Sensibilität

Taufdaten betreffen oft Kinder und Familien. Damit ist besondere Zurückhaltung wichtig bei:

- Zugriffsrechten
- Dateianhängen
- Exporten und Ausdrucken
- Speicherfristen in Arbeitsdateien

---

## 6. Trauungen

### Welche Daten verarbeitet werden

Bei Trauungen verarbeitet Pfarrplaner insbesondere:

- Namen beider Ehepartner:innen
- gegebenenfalls Geburtsnamen
- E-Mail-Adressen
- Telefonnummern
- Angaben zum Traugespräch
- Trautext
- Dimissoriale-Status je Person
- Genehmigungsstatus
- Urkunden- und Kirchenbuchstatus
- Dateien und Unterlagen

### Zweck

- Vorbereitung, Durchführung und Nacharbeit einer Trauung
- organisatorische und kirchenrechtliche Begleitung

### Datenschutz-Hinweise für die Administration

- Prüfen Sie besonders, wer Zugriff auf Dateien und Begleitunterlagen hat.
- Klären Sie intern, welche Unterlagen dauerhaft im System bleiben sollen und welche nur vorübergehend hochgeladen werden.

---

## 7. Beerdigungen

### Welche Daten verarbeitet werden

Bei Beerdigungen ist der Personenbezug besonders weitreichend. Typische Daten sind:

- Name der verstorbenen Person
- Geburtsname
- Rufname
- Geburtsdatum und Sterbedatum
- Geburtsort und Sterbeort
- Adresse
- Bestattungsart
- Kirchenzugehörigkeit und kirchliche Zusatzangaben
- Daten zur wichtigsten Kontaktperson oder zu Angehörigen
- Kontaktdaten von Angehörigen
- Trauergespräch
- Abkündigung
- Predigttext und vorbereitende Notizen
- Dimissoriale-Angaben
- Kirchenbuchstatus
- Dateien, Formulare und weitere Unterlagen

### Zweck

- Vorbereitung und Durchführung einer Bestattung
- Kommunikation mit Angehörigen und beteiligten Stellen
- Dokumentation kirchlicher Verwaltungsabläufe

### Besondere Sensibilität

Beerdigungsdaten gehören zu den sensibelsten Daten im System. Administratorisch wichtig sind deshalb:

- strenge Rechtevergabe
- sehr bewusster Umgang mit Exporten
- regelmäßige Prüfung alter Dateien
- klare Regeln für lokale Kopien und Ausdrucke

---

## 8. Abwesenheiten, Urlaub und Vertretungen

### Welche Daten verarbeitet werden

Im Urlaubs- und Vertretungsbereich werden verarbeitet:

- Name der betroffenen Person
- Abwesenheitszeiträume
- Workflow-Status
- zuständige prüfende oder genehmigende Personen
- Vertretungspersonen
- Vertretungshinweise
- Pool- und Poolmaster-Zeiten
- gegebenenfalls Dateien

### Zweck

- Personal- und Vertretungsplanung
- Genehmigungs- und Prüfabläufe
- Erreichbarkeit während Abwesenheiten

### Öffentliche oder externe Sichtbarkeit

Ein Teil dieser Daten kann über Pool- oder Vertretungsübersichten sichtbar werden. Je nach Konfiguration erscheinen dort zum Beispiel:

- Name
- Amt
- Telefonnummer
- E-Mail-Adresse
- Vertretungszeitraum

### Datenschutz-Hinweise für die Administration

- Prüfen Sie genau, welche Abwesenheitsdaten intern bleiben und welche auf öffentlichen Übersichten erscheinen dürfen.
- Dokumentieren Sie, ob Vertretungsseiten nur intern oder auch für Dritte wie Bestatter gedacht sind.

---

## 9. Teams, Pools und Kontaktstellen

### Welche Daten verarbeitet werden

In Teams und Pools verarbeitet Pfarrplaner:

- Namen von Mitgliedern
- Gemeindezuordnungen
- Ansprechpartner:innen
- Telefonnummern
- E-Mail-Adressen
- Institution oder Amt

### Zweck

- Zuordnung von Gruppen
- Vertretungsorganisation
- Kontaktaufnahme über zentrale Stellen oder konkrete Personen

### Datenschutz-Hinweise für die Administration

- Prüfen Sie, ob besser zentrale Kontaktstellen statt privater Personendaten angezeigt werden sollen.
- Halten Sie öffentliche und interne Pools organisatorisch getrennt.

---

## 10. Anmeldungen, Platzreservierungen und Kontaktlisten

### Welche Daten verarbeitet werden

Bei Anmeldungen und Sitzplatzverwaltung können personenbezogene Daten verarbeitet werden wie:

- Name der anmeldenden oder teilnehmenden Person
- Telefonnummer für Rückfragen oder telefonische Anmeldung
- Reservierungs- oder Sitzplatzdaten
- Teilnehmerzahlen mit möglichem Personenbezug

### Zweck

- Organisation von Veranstaltungen
- Teilnahmebegrenzungen
- Nachverfolgung von Reservierungen

### Datenschutz-Hinweise für die Administration

- Klären Sie intern, wie lange Anmeldedaten nach einer Veranstaltung noch gebraucht werden.
- Prüfen Sie besonders Exportlisten und Ausdrucke, weil sie leicht außerhalb des Systems weitergegeben werden.

---

## 11. Kommentare, Dateien und Anhänge

### Welche Daten verarbeitet werden

In vielen Bereichen können Dateien oder Kommentare gespeichert werden:

- Kommentare zu Gottesdiensten und Vorgängen
- hochgeladene Dateien zu Kasualien, Urlaub, Veranstaltungen und weiteren Objekten
- automatisch erzeugte Formulare
- eingescannte Unterlagen

### Zweck

- interne Zusammenarbeit
- Dokumentation
- Vorbereitung und Ablage von Unterlagen

### Besondere Risiken

Kommentare und Dateien sind datenschutzrechtlich oft kritischer als die sichtbaren Listenfelder, weil dort sehr leicht:

- freie personenbezogene Notizen
- Gesundheits- oder Familiendaten
- unterschriebene Formulare
- Schriftwechsel

gespeichert werden können.

### Datenschutz-Hinweise für die Administration

- Definieren Sie klare Regeln, welche Unterlagen ins System gehören.
- Prüfen Sie Download-Rechte.
- Denken Sie bei Löschkonzepten immer auch an Anhänge mit.

---

## 12. Berichte, Exporte und erzeugte Dokumente

### Welche Daten verarbeitet werden

Pfarrplaner erzeugt PDF-, Word-, Excel-, CSV- und HTML-Ausgaben. Darin können nahezu alle im System vorhandenen personenbezogenen Daten wieder auftauchen, zum Beispiel:

- Namen und Dienstzuordnungen
- Kontaktlisten
- Kasualdaten
- Abwesenheits- oder Vertretungsangaben
- Anmeldungen

### Zweck

- Arbeitslisten
- Auswertungen
- Druckausgaben
- Weitergabe an berechtigte Stellen

### Datenschutz-Hinweise für die Administration

- Prüfen Sie nicht nur, wer im System sehen darf, sondern auch, wer Exporte erzeugen darf.
- Legen Sie fest, wie lange exportierte Dateien außerhalb des Systems aufbewahrt werden.
- Schulen Sie Nutzerinnen und Nutzer im sicheren Umgang mit lokal gespeicherten Dateien.

---

## 13. Öffentliche Seiten, Freigabelinks und Streaming

### Welche Daten verarbeitet oder veröffentlicht werden

Pfarrplaner kann personenbezogene Daten auf öffentlichen oder halböffentlichen Seiten anzeigen, etwa:

- Namen zuständiger Pfarrpersonen
- Telefonnummern und E-Mail-Adressen
- Vertretungs- und Poolinformationen
- Kontaktlisten zu Gottesdiensten
- Daten zu gestreamten Gottesdiensten
- YouTube- oder Kinderkirche-Streaming-Links

### Zweck

- Information der Öffentlichkeit
- Erreichbarkeit
- Einbindung externer Darstellungen

### Datenschutz-Hinweise für die Administration

- Öffentliche Seiten müssen besonders streng geprüft werden.
- Nutzen Sie möglichst dienstliche statt private Kontaktdaten.
- Kontrollieren Sie Freigabelinks regelmäßig.
- Prüfen Sie, ob öffentliche Darstellungen wirklich nur die minimal nötigen Daten zeigen.

---

## 14. Kalenderverbindungen, Benachrichtigungen und externe Dienste

### Welche Daten verarbeitet werden

Je nach Einrichtung können Daten aus Pfarrplaner nach außen fließen oder von außen eingebunden werden, zum Beispiel über:

- Kalenderverbindungen
- Benachrichtigungs-E-Mails
- YouTube-Streaming
- KonfiApp oder CommuniApp
- eingebettete oder freigegebene Inhalte

### Zweck

- technische Integration
- Veröffentlichung
- Kommunikation

### Datenschutz-Hinweise für die Administration

- Dokumentieren Sie jede aktive Integration separat.
- Prüfen Sie, welche Daten an welchen externen Dienst übergeben werden.
- Verwenden Sie nur nötige Zugangsdaten und speichern Sie sie geschützt.

---

## 15. API-Zugänge, Tokens und technische Identifikatoren

### Welche Daten verarbeitet werden

Im technischen Betrieb kommen zusätzlich personenbezogene oder personenbeziehbare Daten vor:

- E-Mail-Adresse als Benutzername
- API-Tokens
- Passwort-Reset-Vorgänge
- Benutzerbezug in Änderungs- oder Workflow-Aktionen
- gegebenenfalls IP-Adressen in technischen Logs

### Zweck

- Authentifizierung
- technische Absicherung
- Nachvollziehbarkeit von Zugriffen

### Datenschutz-Hinweise für die Administration

- API-Tokens nur gezielt vergeben und regelmäßig prüfen.
- Passwörter nie außerhalb sicherer Prozesse weitergeben.
- Logfiles mit Personenbezug nicht länger als nötig aufbewahren.

---

## 16. Protokolle, Backups und Wiederherstellung

### Welche Daten verarbeitet werden

Auch wenn Nutzerinnen und Nutzer sie nicht direkt sehen, enthalten diese Bereiche regelmäßig personenbezogene Daten:

- Anwendungslogs
- Mail- und Fehlerlogs
- Datenbank-Backups
- Dateisicherungen aus `storage/`
- temporäre Exporte oder Renderdateien

### Zweck

- Fehleranalyse
- Betriebssicherheit
- Wiederherstellung nach Störung

### Datenschutz-Hinweise für die Administration

- Backups sind keine datenschutzfreien Kopien, sondern vollständige oder teilweise Duplikate des Systems.
- Sichern Sie Backups genauso streng wie das Livesystem.
- Definieren Sie Aufbewahrungsfristen und Zugriffsrechte.

---

## 17. Organisatorische Prüfliste für Administrator:innen

Für eine produktive Pfarrplaner-Instanz sollten Sie mindestens diese Punkte dokumentiert haben:

1. Welche Benutzergruppen welche Daten sehen dürfen.
2. Welche öffentlichen Seiten aktiv sind.
3. Welche externen Dienste angebunden sind.
4. Welche Dateien und Exporte mit personenbezogenen Daten typischerweise entstehen.
5. Wer Backups lesen oder wiederherstellen darf.
6. Wie lange alte Konten, Anhänge, Logs und Exporte aufbewahrt werden.
7. Wie Berichtigungen, Sperrungen und Löschungen organisatorisch abgewickelt werden.

## Was dieses Kapitel bewusst nicht festlegt

Dieses Kapitel beschreibt die tatsächlichen Verarbeitungsvorgänge in Pfarrplaner, legt aber nicht selbst fest:

- welche Rechtsgrundlage jeweils gilt
- welche Aufbewahrungsfrist im Einzelfall richtig ist
- welche kirchliche oder staatliche Datenschutzordnung für Ihre Einrichtung maßgeblich ist

Diese Entscheidungen müssen Sie für Ihre Organisation zusätzlich sauber dokumentieren.
