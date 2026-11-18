[//]: # (TOC: 14. Persönliche Einstellungen)

# Persönliche Einstellungen

In den persönlichen Einstellungen verwalten Sie Ihr eigenes Benutzerprofil und weitere Optionen für Ihr Konto.

Diese Seite betrifft nur Ihr eigenes Konto. Änderungen hier können beeinflussen, wie Ihr Name angezeigt wird, wie Sie sich anmelden, welche Startseite Sie sehen und ob Pfarrplaner mit externen Kalendern verbunden ist.

---

## Einstellungen aufrufen

1. Klicken Sie oben rechts auf Ihren **Namen** (oder das Benutzersymbol).
2. Klicken Sie im Dropdown-Menü auf **„Einstellungen"**.

![Persönliche Einstellungen](media/images/einstellungen-profil.png)

---

Die Seite heißt **Mein Profil** und ist in Reiter gegliedert: **Profil**, **Sicherheit**, **E-Mailbenachrichtigungen**, **Startseite**, **Verbundene Kalender** und **Externe Inhalte**. Der Reiter **Verbundene Kalender** ist nur für ELKW-Konten verfügbar.

## Reiter: Profil

### Name

Tragen Sie Ihren Namen ein, wie er im Kalender und in Berichten erscheinen soll.

Verwenden Sie die Schreibweise, unter der Kolleginnen und Kollegen Sie erkennen. Dieser Name kann in Dienstplänen, Gottesdiensten und Ausgaben erscheinen.

### E-Mail-Adresse

Ihre E-Mail-Adresse dient gleichzeitig als Benutzername für die Anmeldung. Ändern Sie sie nur, wenn Sie auch zukünftig diese Adresse für die Anmeldung verwenden.

> **Hinweis:** Nach einer Änderung der E-Mail-Adresse müssen Sie sich beim nächsten Mal mit der neuen Adresse anmelden.

### Profilbild

Sie können ein Foto von sich hochladen, das oben rechts im Menü und in einigen Ausgaben erscheint.

1. Nutzen Sie das Feld **Benutzerbild**.
2. Wählen Sie ein Bild von Ihrem Computer (JPG oder PNG, max. 2 MB).
3. Klicken Sie auf **„Speichern"**.

---

## Reiter: Sicherheit

Hier richten Sie Passkeys und eine Authenticator-App ein. Ein Passkey ist ein digitaler Schlüssel, den Ihr Smartphone, Computer oder Passwortmanager sicher aufbewahrt. Bei der Anmeldung bestätigen Sie mit Fingerabdruck, Gesichtserkennung oder Geräte-PIN. Ein Passkey funktioniert nur auf der echten Pfarrplaner-Seite und schützt so auch vor gefälschten Anmeldeseiten.

Eine Authenticator-App erzeugt alle 30 Sekunden einen neuen sechsstelligen Code. Bei der Anmeldung über einen E-Mail-Link geben Sie diesen Code zusätzlich ein. Bei einer Passkey-Anmeldung ist kein zusätzlicher App-Code erforderlich. Die Codes funktionieren auch ohne Internetverbindung. Geeignete Apps sind zum Beispiel [Microsoft Authenticator](https://support.microsoft.com/de-de/authenticator/download-microsoft-authenticator) und [2FAS Auth](https://2fas.com/auth/), eine quelloffene App für Android und iPhone.

Pfarrplaner empfiehlt zuerst einen Passkey. Eine Authenticator-App ist eine Alternative oder kann zusätzlich eingerichtet werden. Ein Passkey oder eine bestätigte Authenticator-App genügt, um die wiederkehrende Einrichtungserinnerung zu beenden.

### Einrichtungserinnerung

Wenn Sie sich per E-Mail-Link anmelden und noch keinen Passkey oder keine Authenticator-App eingerichtet haben, zeigt Pfarrplaner eine eigene Sicherheitsseite. Dort können Sie beide Einrichtungsabläufe direkt starten. Die Erinnerung erscheint bei den ersten vier passenden E-Mail-Link-Anmeldungen. Danach erscheint sie frühestens 14 Tage nach der letzten tatsächlichen Anzeige erneut.

Mit **Später erinnern** geben Sie nur die aktuelle Sitzung frei. Sie können die Einrichtung jederzeit im Profil unter **Sicherheit** nachholen. Sobald ein Passkey oder eine bestätigte Authenticator-App eingerichtet ist, wird die Erinnerung nicht mehr angezeigt.

### Zwei-Faktor-Authentifizierung mit einer Authenticator-App

Wählen Sie **Zwei-Faktor-Authentifizierung einrichten**. Scannen Sie den angezeigten QR-Code mit einer Authenticator-App und speichern Sie die Recovery-Codes an einem sicheren Ort. Wenn Ihre App den QR-Code nicht direkt übernimmt oder wie Bitwarden standardmäßig einen Einrichtungsschlüssel benötigt, geben Sie den Schlüssel unter dem QR-Code manuell ein. Mit **Kopieren** übernehmen Sie ihn in die Zwischenablage. Die Recovery-Codes stehen in einem Textfeld und lassen sich gemeinsam kopieren. Sie sind nur während dieses Einrichtungsschritts sichtbar und können jeweils nur einmal verwendet werden.

Bestätigen Sie, dass Sie die Recovery-Codes sicher gespeichert haben, und geben Sie anschließend den aktuellen sechsstelligen Code aus Ihrer Authenticator-App ein. Die Einrichtung ist erst nach erfolgreicher Codeprüfung abgeschlossen. Wenn Sie den Dialog vor der Bestätigung schließen, können Sie die Einrichtung neu starten; dabei werden die vorherigen Einrichtungsdaten ungültig.

Bei einer Anmeldung über einen E-Mail-Link benötigen Sie nach der Einrichtung zusätzlich einen aktuellen App-Code. Alternativ können Sie einen Recovery-Code eingeben. Eine Login-Codeabfrage läuft nach fünf Minuten ab.

Zum Deaktivieren wählen Sie **Zwei-Faktor-Authentifizierung deaktivieren** und bestätigen die Nachfrage. Nach zu vielen fehlerhaften Codeversuchen wird die Prüfung vorübergehend gesperrt.

Wenn Sie Ihr Gerät und alle Recovery-Codes verloren haben, wenden Sie sich über einen bereits bekannten Kontaktweg an Ihre Administration. Diese muss Ihre Identität außerhalb von Pfarrplaner prüfen. Ein Super-Administrator kann anschließend den verlorenen Faktor widerrufen. Gespeicherte Sitzungen und API-Verbindungen werden dabei abgemeldet. Bei Ihrer nächsten Anmeldung müssen Sie die Authenticator-App neu einrichten und mit einem gültigen Code bestätigen, bevor Sie die Anwendung wieder normal nutzen können.

### Passkeys

Geben Sie im Bereich **Passkeys** einen wiedererkennbaren Gerätenamen ein und wählen Sie **Passkey einrichten**. Der Browser bittet Sie, die Geräte-PIN, Biometrie oder eine andere lokale Geräteverifikation zu verwenden. Das Gerät muss Passkeys unterstützen.

Sie können mehrere Passkeys für Ihre Geräte einrichten, vorhandene umbenennen und widerrufen. Ein widerrufener Passkey kann sich nicht mehr anmelden. Die Login-Seite bietet Passkey und E-Mail-Link an.

---

## Reiter: E-Mailbenachrichtigungen

![Persönliche Einstellungen: E-Mailbenachrichtigungen](media/images/einstellungen-benachrichtigungen.png)

Hier legen Sie fest, welche Nachrichten Pfarrplaner Ihnen per E-Mail schicken darf. Je nach Installation sehen Sie verschiedene Optionen.

- **Dienstanfragen**: Benachrichtigung, wenn Sie für einen Gottesdienst oder Dienst angefragt werden.
- **Änderungen an Gottesdiensten**: Hinweis, wenn sich ein für Sie wichtiger Eintrag ändert.
- **Kommentare oder Rückfragen**: E-Mail, wenn jemand Sie in einer internen Abstimmung informiert.
- **Zusammenfassungen**: Regelmäßige Übersichten, falls diese Funktion eingerichtet ist.

Aktivieren Sie nur Benachrichtigungen, die Ihnen im Alltag wirklich helfen. Zu viele E-Mails werden schnell unübersichtlich.

---

## Reiter: Startseite

![Persönliche Einstellungen: Startseite](media/images/einstellungen-startseite.png)

Hier legen Sie fest, was nach der Anmeldung angezeigt wird. Die meisten Benutzerinnen und Benutzer verwenden die **konfigurierbare Startseite**. Administrator:innen können für andere Personen auch einstellen, dass Pfarrplaner direkt mit einem bestimmten Modul, zum Beispiel dem Kalender, startet.

- **Startseite für diese Person**: Wählt die konfigurierbare Startseite oder ein Modul, das direkt geöffnet werden soll.
- **Schaltflächen für das schnelle Erstellen von Kasualien anzeigen**: Blendet auf der Startseite Schaltflächen für Taufe, Beerdigung und Trauung ein.
- **Aktuell von mir vertretene Kolleg:innen anzeigen**: Zeigt Hinweise zu Vertretungen und Poolmaster-Aufgaben, falls diese Funktion für Sie genutzt wird.
- **Angezeigte Reiter**: Liste der Reiter, die auf der Startseite sichtbar sind.
- **Drag-Symbol**: Ziehen Sie einen Reiter nach oben oder unten, um die Reihenfolge zu ändern.
- **Pfeil am Reiter**: Öffnet zusätzliche Einstellungen für diesen Reiter, falls vorhanden.
- **Papierkorb**: Entfernt einen Reiter von Ihrer Startseite.
- **Hinzufügen**: Fügt weitere verfügbare Reiter hinzu.

Mögliche Reiter sind zum Beispiel **Nächste Gottesdienste**, **Meine Gottesdienste**, **Fehlende Einträge**, **Kasualien**, **Taufen**, **Trauungen**, **Beerdigungen**, **Anmeldungen**, **Streaming**, **Mein Urlaub**, **Urlaubsanträge** oder **Administration**. Welche Reiter angeboten werden, hängt von Ihren Rechten ab.

Wählen Sie vor allem die Informationen, die Sie nach dem Anmelden sofort brauchen, zum Beispiel eigene Dienste, offene Aufgaben oder kommende Gottesdienste. Die Bedienung der Startseite ist im Kapitel [Startseite](startseite.md) beschrieben.

## Reiter: Verbundene Kalender

![Persönliche Einstellungen: Verbundene Kalender](media/images/einstellungen-kalender.png)

Sie können externe Kalender mit Pfarrplaner verbinden. Für Outlook weist Pfarrplaner auf das Add-in **Outlook CalDav Synchronizer** hin.

1. Öffnen Sie den Reiter **Verbundene Kalender**.
2. Klicken Sie auf **Neue Verbindung anlegen**, falls diese Funktion in Ihrer Installation freigeschaltet ist.
3. Folgen Sie den Angaben auf der Einrichtungsseite.
4. Bestehende Verbindungen können Sie über das Stift-Symbol bearbeiten oder über das Papierkorb-Symbol löschen. Vor dem Löschen fragt Pfarrplaner noch einmal nach.

Änderungen in Pfarrplaner werden dann regelmäßig in Ihr externes Kalender-Programm übernommen.

Eine Kalenderverbindung ist meist nur zum Anzeigen gedacht. Bearbeiten Sie Gottesdienste weiterhin in Pfarrplaner, damit alle Beteiligten dieselben Daten sehen.

---

## Reiter: Externe Inhalte

![Persönliche Einstellungen: Externe Inhalte](media/images/einstellungen-externe-inhalte.png)

In diesem Reiter pflegen Sie externe Links, die zu Ihrem Profil gehören. Das können zum Beispiel Links zu einer persönlichen Website, zu Veröffentlichungen oder zu anderen öffentlich erreichbaren Seiten sein, wenn Ihre Installation solche Angaben verwendet.

Tragen Sie nur Links ein, die auch von anderen Personen gesehen werden dürfen. Wenn Sie unsicher sind, fragen Sie Ihre Administratorin oder Ihren Administrator.

---

## Speichern

Vergessen Sie nicht, Ihre Änderungen oben links mit **Speichern** zu sichern, bevor Sie die Einstellungsseite verlassen.

---

## Verwandte Kapitel

- [Anmeldung und Benutzerkonto](anmeldung.md): Mit Passkey oder E-Mail-Link anmelden und abmelden
- [Startseite](startseite.md): Persönliche Übersicht und Reiter verstehen
- [Kalender](kalender.md): Gottesdienste und Veranstaltungen im Monatsüberblick
- [Administration](administration.md): Benutzerkonten und Rechte verwalten, falls Sie Administratorrechte haben
