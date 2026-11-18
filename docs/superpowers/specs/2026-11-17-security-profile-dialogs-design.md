# Sicherheitsaktionen im Profil – Entwurf

> **Entscheidungsänderung vom 18.11.2026:** Die nachfolgende frühere Festlegung zu Passwortdialogen ist aufgehoben. Der Profil-Editor und sein Sicherheitsreiter enthalten keinerlei Passwortfelder, Passwortanforderungen oder Passwortbestätigungsdialoge. Die eigenständige Passwortänderungsseite unter `/passwort/aendern` bleibt erhalten. Persönliche Passkey-Aktionen erfordern nur eine angemeldete Sitzung und die bestehende Eigentumsprüfung; TOTP-Einrichtung und -Deaktivierung verlangen kein Kontopasswort. Die TOTP-Einrichtung wird weiterhin erst durch einen gültigen App-Code aktiviert; Deaktivieren verlangt eine einfache Fehlklickbestätigung. Super-Admin-Recovery bleibt ein separater, passwortgeschützter Ablauf.

## Ziel

Passwortgeschützte Sicherheitsaktionen sollen direkt aus der Sicherheitsansicht mit einem verständlichen Dialog bestätigt werden. Das Profil bleibt dabei sichtbar; die Passwortbestätigung wird nicht als dauerhaftes Formular in der Seite oder als separate Navigationsseite dargestellt.

## Umfang

- Beim Deaktivieren von TOTP öffnet die Schaltfläche einen Passwortdialog. Das aktuelle Passwort wird nur für die Anfrage verwendet und danach aus dem UI-Speicher gelöscht.
- Wenn der Passkey-Optionsaufruf wegen abgelaufener Passwortbestätigung `423` liefert, öffnet sich derselbe Dialog. Nach erfolgreicher Bestätigung bleibt der Dialog geschlossen und die Person startet die Passkey-Einrichtung mit einem neuen Klick. Dadurch beginnt `navigator.credentials.create()` aus einer aktuellen Nutzeraktion.
- Die bestehenden Rate-Limits, serverseitige Passwortprüfung, Faktor- und Kontoschutzregeln bleiben unverändert.
- Das Sicherheitshinweis-Interstitial bleibt für geeignete Passwort-only-Konten aktiv. In `local` und `dusk` wird es zur Vorschau mit `?show_interstitial=1` sichtbar; ohne diesen Schalter unterdrückt die Anwendung Interstitials in diesen Umgebungen absichtlich.
- Das Interstitial erklärt Passkey und Authenticator-App ohne vorausgesetzte Fachkenntnisse, definiert TOTP in Alltagssprache, nennt Microsoft Authenticator und die quelloffene Alternative 2FAS Auth mit offiziellen Produktlinks und erklärt die Wiederherstellungscodes.
- Im TOTP-Assistenten ist der Einrichtungsschlüssel immer direkt sichtbar, weil manche Apps wie Bitwarden ihn standardmäßig benötigen.
- Der Einrichtungsschlüssel erhält einen eigenen Kopierbutton. Recovery-Codes werden newline-getrennt in einem schreibgeschützten Textfeld gezeigt und gemeinsam kopiert; Erfolg und Fehler werden sichtbar gemeldet.
- Die jeweils zusammengehörenden Aktionen im TOTP-Assistenten, im Passwort-Login und im Login-Challenge-Ablauf stehen in derselben responsiven Aktionszeile.
- Der Primärbutton des Interstitials speichert den 21-Tage-Aufschub vor der Navigation. Der Aufschub bleibt wirksam, solange das Konto Passwort-only ist; die Erinnerung kommt nach Ablauf wieder.

## Festgelegter Hinweistext

- Ein Passkey wird als digitaler Schlüssel auf Smartphone, Computer oder Passwortmanager erklärt. Anmeldung erfolgt durch Geräte-PIN oder Biometrie, ohne Passwort einzutippen; der Passkey funktioniert nur auf der echten Pfarrplaner-Seite und hilft so gegen gefälschte Anmeldeseiten.
- Die Authenticator-App wird als Alternative oder zusätzliche Stufe beschrieben. Die Person scannt einen QR-Code, die App erzeugt alle 30 Sekunden einen neuen sechsstelligen Code, und dieser wird nach dem Passwort eingegeben. Der Code funktioniert offline. TOTP wird als technischer Name für ein zeitbasiertes Einmalpasswort erklärt.
- Es werden Microsoft Authenticator und 2FAS Auth als quelloffene Alternative genannt und auf ihre offiziellen Seiten verlinkt.
- Die Person wird aufgefordert, Wiederherstellungscodes getrennt vom Gerät sicher aufzubewahren. Passkey oder Authenticator-App genügt zum Beenden des Hinweises; beides ist möglich.

## Gewählte Umsetzung

Eine wiederverwendbare, zugängliche Passwortdialog-Komponente übernimmt Eingabe, Abbrechen, Fehlerdarstellung, Fokus und Geheimnisbereinigung. `SecurityTab` öffnet sie für TOTP-Widerruf und erneute Passkey-Bestätigung. Der Bestätigungscontroller gibt bei JSON-Anfragen eine JSON-Antwort bzw. einen strukturierten Validierungsfehler zurück und behält den bestehenden Redirect-Ablauf für normale Formularanfragen.

Die Passkey-Einrichtung wird nach einer erfolgreichen Passwortbestätigung nicht automatisch fortgesetzt. Der zusätzliche Klick stellt sicher, dass die Browser-API mit einer frischen Nutzeraktion aufgerufen wird.

## Ansätze

- Passwortfelder dauerhaft in der Profilansicht: verworfen, weil sie die Hauptansicht aufblähen und nicht erst bei der jeweiligen Aktion erscheinen.
- Separate Passwortbestätigungsseite: bestehender Ablauf, verworfen, weil er den Kontext der Sicherheitsansicht verlässt.
- Wiederverwendbarer Dialog für beide Aktionen: gewählt, weil er den Kontext erhält, nur bei Bedarf erscheint und dasselbe zugängliche Interaktionsmuster bietet.

## Erfolgskriterien

- TOTP-Widerruf und Passkey-Reauthentifizierung sind ohne Navigationswechsel möglich.
- Falsche Passwörter, Rate-Limits, Abbrechen, erneutes Öffnen und erfolgreiche Bestätigung sind verständlich und löschen Passwörter aus dem UI-Zustand.
- Nach Passkey-Reauthentifizierung ist ein erneuter ausdrücklicher Klick nötig und ausreichend, um eine neue Registrierung zu starten.
- Das Interstitial bleibt in Produktion an die bereits vereinbarten Passwort-only-, 21-Tage-Aufschub- und Ausschlussregeln gebunden; die lokale Vorschau ist dokumentiert.
- Nach Klick auf „Sicherheitseinstellungen öffnen“ ist der Hinweis auch ohne eingerichteten Faktor für 21 Tage verborgen; Profil-Props nach der Navigation enthalten ihn in dieser Zeit nicht.
- Der manuelle TOTP-Schlüssel ist ohne Dropdown sichtbar; TOTP- und Login-Aktionspaare verwenden gemeinsame Flex-Zeilen.
- Das Interstitial erklärt Anmeldung, Einrichtung, Offline-Codes und Wiederherstellungscodes verständlich und verlinkt auf offizielle App-Seiten.
- Benutzer-, Administratoren- und Technisches Handbuch werden auf Relevanz geprüft und konsistent aktualisiert.

## Nicht-Ziele

- Keine Änderung an Passkey- oder TOTP-Kryptografie, Recovery- und Rate-Limit-Regeln.
- Keine dauerhafte Ausblendung der Sicherheitsempfehlung und keine Änderung der Laufzeitbedingungen in Produktion.
- Kein automatischer Start der WebAuthn-Browserabfrage direkt nach dem asynchronen Passwort-POST.

## Ergänzung: Passwortloser Login per Passkey oder E-Mail-Link

### Ziel und UI

Der reguläre Login fragt nur noch die E-Mail-Adresse ab. Darunter stehen zwei gleich breite Aktionen: zuerst **Mit Passkey anmelden** mit Fingerabdrucksymbol als primäre Schaltfläche, danach eine zentrierte Trennzeile **oder** und **Anmeldelink per E-Mail erhalten** mit Briefsymbol als sekundäre Schaltfläche. Das Passwortfeld und der Link zum Zurücksetzen des Passworts erscheinen im Login nicht. Der Demo-Login bleibt als separater Sonderfall erhalten.

### Authentifizierungsregeln

- Passkey-Login bleibt discoverable und funktioniert unabhängig von der eingegebenen E-Mail-Adresse.
- Der E-Mail-Link ist ein kurzlebiger, einmalig verwendbarer Faktor für den normalen passwortlosen Login. Für Konten ohne TOTP meldet die Bestätigung des Links direkt an.
- Für Konten mit aktiviertem TOTP führt die Linkbestätigung in die bestehende TOTP-Challenge. Dort werden nur Authenticator-Code und Recovery-Code angeboten. Der bisherige TOTP-Ersatz per E-Mail-Code wird entfernt.
- Die Anforderung des Links verrät nicht, ob die E-Mail-Adresse ein Konto besitzt. Antworttext und Ablauf bleiben für bekannte und unbekannte Adressen gleich.
- E-Mail-Links enthalten einen kryptografisch zufälligen 256-Bit-Nonce, laufen nach 10 Minuten ab und werden in der Datenbank nur als SHA-256-Hash gespeichert. Ein Queue-Mailjob erhält den Nonce verschlüsselt. Die E-Mail-Link-Ankunft per GET verbraucht den Token nicht: Ein Bestätigungsschritt per POST schützt vor Linkscannern; erst dieser POST verbraucht ihn unter Zeilensperre atomar und genau einmal.
- Anfragen werden pro IP und normalisierter E-Mail-Adresse begrenzt. Fehler, Ablauf und Wiederverwendung führen zurück zum Login mit einer allgemeinen Meldung.
- Der bestehende E-Mail-Codepfad auf der TOTP-Challenge und dessen aktive Mailvorlage werden entfernt. Die existierende Challenge-Tabelle und Bereinigung bereits vorhandener Datensätze bleiben erhalten, da Migrationen auf Installationen bereits ausgeführt worden sein können.

### Ansätze

- Einmal-Link direkt beim GET verbrauchen: verworfen, weil E-Mail-Sicherheits- und Vorschau-Scanner den Link vor der Person öffnen können.
- Einmal-Link im Browser bestätigen und erst per POST verbrauchen: gewählt, weil dies denselben komfortablen E-Mail-Link bietet, ohne GET-Scanner den Login zu konsumieren.
- E-Mail-Code statt Link: verworfen, weil die gewünschte Oberfläche ausdrücklich einen Anmeldelink anbietet und der bisherige Code als TOTP-Ersatz entfallen soll.

### Erfolgskriterien

- Die Standard-Loginseite enthält kein Passwortfeld und keinen Passwort-zurücksetzen-Link; Reihenfolge, Breite, Beschriftung und Symbole der drei gewünschten UI-Elemente stimmen.
- Bekannte und unbekannte E-Mail-Adressen erhalten dieselbe Antwort; nur bei einem passenden Konto wird ein kurzlebiger Link versendet.
- Link-GET verbraucht nichts; ein gültiger Bestätigungs-POST verbraucht den gehashten Nonce genau einmal und authentifiziert ein Konto ohne TOTP. Ein Replay wird abgewiesen.
- Ein Link für ein TOTP-Konto führt zur TOTP-Challenge; nur sechsstelliger Authenticator-Code oder Recovery-Code kann diese abschließen. E-Mail-Codes funktionieren dort nicht mehr.
- Abgelaufene und wiederverwendete Links authentifizieren nicht; Passkey-Login und Demo-Login bleiben funktionsfähig.
