# Echtzeitaktualisierung von Gottesdiensten – Design

## Absicht

Änderungen an Gottesdiensten sollen in bereits geöffneten, eingeloggten
Ansichten zeitnah sichtbar werden. Das gilt für Anlegen, Ändern und Löschen
und unabhängig davon, ob die Änderung im klassischen Editor, über die API,
über die Planungsinputs oder über ein Kasualien-Formular ausgelöst wurde.

## Ziele

- Soketi über Laravel Broadcasting und Echo für Service-Ereignisse nutzen.
- Ereignisse für `created`, `updated` und `deleted` zuverlässig aus allen
  relevanten Schreibpfaden auslösen.
- Einzelne Dienste anhand ihrer ID aktualisieren, verschieben oder entfernen.
- Benutzerabhängige Felder wie Sichtbarkeit, `isMine` und Berechtigungen nicht
  in einem gemeinsamen Broadcast-Payload vorab berechnen.
- Bestehende Service-API und `CalendarServiceResource` wiederverwenden.

## Nichtziele

- Keine Synchronisierung historischer, öffentlicher oder nicht eingeloggter
  Seiten über private Benutzerkanäle.
- Keine lokale Replikation sämtlicher Service-Fachlogik im Browser.
- Kein globaler Monats- oder Seiten-Reload als primärer Aktualisierungsweg.

## Anti-Ziele

- Keine Service-Daten oder personenbezogenen Teilnehmerdaten direkt an alle
  Benutzer eines Broadcast-Kanals senden.
- Keine unautorisierte Einzelabfrage zulassen, nur weil eine Service-ID aus
  einem Ereignis bekannt ist.

## Gewählter Ansatz

Ein neues, schlankes Broadcast-Ereignis überträgt den Änderungstyp, die
Service-ID sowie die alte und neue Stadt-/Datumszuordnung. Die Clients
abonnierten private Kanäle der für sie sichtbaren Gemeinden. Bei `created` und
`updated` ruft der jeweilige Client `api.calendar.service` mit seiner eigenen
Authentifizierung ab; dadurch erzeugt Laravel die passende
`CalendarServiceResource` pro Benutzer. Bei `deleted` entfernt der Client den
Dienst anhand der ID. Ein gemeinsamer Frontend-Dispatcher verteilt das
Ereignis an registrierte Service-Sammlungen. Für Seiten, deren lokale
Datenstruktur noch keinen Adapter besitzt, wird als sicherer Fallback ein
gezielter Inertia-Reload der aktuellen Seite verwendet.

## Alternativen

- Ein Monats-Refresh über das Broadcast-Ereignis wäre einfach, aktualisierte
  aber Startseiten, Inputs und andere servicehaltige Ansichten nicht
  zuverlässig und lädt bei einer Einzeländerung zu viele Daten.
- Ein vollständiger Service-Payload im Broadcast wäre schneller, würde aber
  benutzerabhängige Berechtigungs- und Sichtbarkeitsfelder an einen
  gemeinsamen Payload binden und die Pflege mehrerer Repräsentationen
  erzwingen.

## Sicherheits- und Betriebsgrenzen

- Channel-Autorisierung prüft, ob der angemeldete Benutzer die Gemeinde sehen
  darf.
- Bei einer Verschiebung werden altes und neues Gemeindeziel berücksichtigt,
  damit abonnierende Clients den Dienst an beiden Stellen korrekt entfernen
  bzw. neu laden.
- Der Broadcast erfolgt erst nach erfolgreichem Datenbank-Commit.
- Soketi, Pusher-Schlüssel, Frontend-Build-Variablen und Dokumentation werden
  nur über bestehende `.env`-/Compose-Konfiguration erweitert.

## Verifikation des Realtime-Stacks

Die Implementierung gilt erst als technisch belastbar, wenn nicht nur
statische Konfigurationswerte geprüft wurden. Der Dev-Stack muss mit
temporären Testwerten gerendert und gestartet werden; anschließend werden
Containerstatus, interne DNS-/Port-Erreichbarkeit, Laravel-Pusher-Publishing
und ein echter WebSocket-Roundtrip mit Echo/Pusher geprüft. Ein Test mit
absichtlich inkonsistenten Schlüsseln, einem falschen Host und einem
gestoppten Soketi-Dienst muss jeweils einen verständlichen Fehler liefern.
Secrets dürfen dabei weder in Testausgaben noch in Logs übernommen werden.
