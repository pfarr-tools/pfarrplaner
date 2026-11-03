# HomeScreen-Tab für offene Prädikant:innen-Gottesdienste

## Ziel

Prädikant:innen, Gottesdienstbearbeiter:innen und Pfarrer:innen erhalten einen
HomeScreen-Tab mit zukünftigen Gottesdiensten aus ihren sichtbaren Gemeinden,
bei denen `need_predicant` gesetzt und noch keine Pfarrer:in eingetragen ist.
Ein:e berechtigte:r Nutzer:in kann sich dort direkt als Pfarrer:in eintragen.

## Umfang

- Neue Rolle `Prädikant:in`; der Tab ist für `Prädikant:in`,
  `Gottesdienstbearbeiter:in` und `Pfarrer:in` verfügbar.
- Die Rolle wird über Migration, Seeder und Installations-Setup angelegt.
- Neuer HomeScreen-Tab mit automatischer Factory-Erkennung.
- Gemeindeauswahl im Tab; standardmäßig werden alle sichtbaren Gemeinden
  berücksichtigt.
- Die Auswahl wird als `cities`-Konfiguration im bestehenden
  `homeScreenTabsConfig`-Benutzersetting gespeichert.
- Der Server liefert ausschließlich zukünftige, sichtbare Gottesdienste mit
  gesetztem `need_predicant`-Flag und ohne Pastor:innen-Zuordnung.
- Ein dedizierter API-Endpunkt prüft Rolle, Sichtbarkeit, Zukunft,
  `need_predicant` und fehlende Pastor:innen-Zuordnung erneut und trägt den
  aktuellen Nutzer atomar als Pastor:in ein. Anschließend wird das Flag gelöscht.

## Nicht-Ziele

- Keine Änderung der allgemeinen Gottesdienstbearbeitung oder des bestehenden
  Kalender-Selbsteintrags.
- Keine automatische Benachrichtigung und keine Vergabe einer zusätzlichen
  Berechtigung an die neue Rolle.

## Betroffene Bausteine

- `RoleService`, `RoleSeeder`, `InstallSetup` und neue Migration für die Rolle.
- PHP-HomeScreen-Tab, JSON-Ressource und Vue-Tab samt Filter- und
  Übernehmen-Aktion.
- API-Controller/Route für den geschützten Selbsteintrag.
- Feature- und JavaScript-Tests für Rollenverfügbarkeit, Filterpersistenz,
  Query-Filter und konkurrierende bzw. unzulässige Übernahmen.

## Sicherheits- und Konsistenzregeln

- Die sichtbaren Gemeinden werden immer serverseitig aus dem angemeldeten
  Nutzer bestimmt; gespeicherte Filterwerte können keinen Zugriff erweitern.
- Der Übernahmevorgang prüft den aktuellen Datensatz innerhalb einer
  Datenbanktransaktion erneut. Ist der Gottesdienst inzwischen übernommen oder
  nicht mehr offen, wird keine weitere Zuordnung vorgenommen.
- Das bestehende `need_predicant`-Flag wird nur nach erfolgreicher
  Pastor:innen-Zuordnung gelöscht.
