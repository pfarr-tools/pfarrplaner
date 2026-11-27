# Sichtbarkeit von Pfarrer:innen im Urlaubsplaner – Design

## Ziel

Sekretär:innen und andere berechtigte Nicht-Pfarrer:innen sollen gezielt die
Abwesenheiten von Pfarrer:innen aus ausgewählten Pools sehen können. Die
Sichtbarkeit muss serverseitig gelten und darf nicht nur eine Filterung der
Planner-Oberfläche sein.

## Gewählte Architektur

Die Pool-Mitgliedschaft und die Sichtbarkeit werden getrennt modelliert. Eine
neue Pivot-Tabelle `pool_user_visibility` ordnet Benutzer:innen Pools zu, die
sie im Urlaubsplaner als Zielgruppen sehen dürfen. `User::visiblePools()` und
`Pool::visibilityUsers()` kapseln diese Beziehung.

Die bestehende `user_pool`-Beziehung bleibt ausschließlich die fachliche
Pool-Mitgliedschaft. Das bestehende `groupable`-Feld steuert weiterhin nur die
Darstellung im Planner und keine Berechtigung.

## Berechtigungsregeln

- Die neue Berechtigung heißt `pfarrer_urlaub_sehen`.
- Pfarrer:innen behalten die bisherige Sichtbarkeit und sehen zusätzlich
  Pfarrer:innen, mit denen sie einen Pool teilen.
- Nicht-Pfarrer:innen benötigen `pfarrer_urlaub_sehen`, um Pfarrer:innen aus
  ihren zugewiesenen Sichtbarkeitspools zu sehen.
- Mehrere zugewiesene Pools werden vereinigt; ein gemeinsamer Pool genügt.
- Die bisherige Sichtbarkeit von Mitarbeiter:innen, die eigene Person und
  bestehende Freigaben bleiben erhalten.
- `groupable` ist für diese Entscheidung irrelevant.

## Administration und Dokumentation

Die Zuweisung der Sichtbarkeitspools erfolgt im Benutzereditor. Sie wird nur
für Benutzer:innen angeboten, die keine Pfarrer:innen sind, und unterliegt
der bestehenden Benutzerverwaltungsberechtigung.

Die Änderung wird dokumentiert in:

- Benutzerhandbuch: sichtbare Personen und Poolgruppen im Urlaubsplaner
- Administratorenhandbuch: Berechtigung und Zuweisung von Sichtbarkeitspools
- Technisches Handbuch: Datenmodell und serverseitige Filterung
- Installations-, Migrations- und Betriebshandbuch: neue Pivot-Migration und
  Rollback-Hinweis

## Nicht-Ziele

- Keine Änderung der Pool-Mitgliedschaft selbst.
- Keine clientseitige Sicherheitsentscheidung.
- Keine Einschränkung der bestehenden Pfarrer:innen- oder City-Logik ohne
  einen vorhandenen Berechtigungsfall.
