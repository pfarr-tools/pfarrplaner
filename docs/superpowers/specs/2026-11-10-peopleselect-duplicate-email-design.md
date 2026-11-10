# PeopleSelect: Warnung bei doppelter E-Mailadresse

## Ziel

Beim Anlegen einer Person über `PeopleSelect` soll eine bereits verwendete
E-Mailadresse nicht mehr unbemerkt zu einer zweiten Person führen. Die
anlegende Person erhält stattdessen die Wahl, die vorhandene Person zu
verwenden oder eine andere E-Mailadresse einzugeben.

## Umfang und Abgrenzung

- Die Prüfung erfolgt nur beim Anlegen über `PeopleSelect`.
- Bestehende Namenssuche, Gemeinde-Sichtbarkeit und Übernahme aus einer
  anderen Kirchengemeinde bleiben erhalten.
- Die E-Mailprüfung erkennt jede bereits verwendete Adresse, damit auch
  Personen aus der aktuellen Gemeinde als vorhandene Personen angeboten
  werden.
- Andere Benutzerverwaltungswege und bereits vorhandene doppelte Adressen
  werden nicht automatisch verändert.

## Gewählte Umsetzung

Der authentifizierte PeopleSelect-API-Bereich erhält einen dedizierten
E-Mail-Prüfendpunkt. Vor dem eigentlichen Anlegen fragt die Vue-Komponente
diesen Endpunkt ab. Bei einem Treffer zeigt das vorhandene Modal die
gefundenen Personendaten und zwei Aktionen: Die vorhandene Person übernehmen
oder die Eingabe mit einer anderen E-Mailadresse fortsetzen. Ohne Treffer
bleibt der bisherige Speichervorgang unverändert.

## Ansätze

- Eine globale Datenbank-Unique-Regel würde bestehende Daten und andere
  Anlegewege beeinflussen und verhindert nicht die gewünschte Auswahl einer
  vorhandenen Person.
- Eine reine Suche im bereits geladenen Frontend-Array wäre unvollständig,
  weil dort nicht alle sichtbaren oder neu aktivierten Personen enthalten sein
  müssen.
- Der dedizierte, serverseitige Endpunkt ist deshalb die passende Lösung für
  die bestehende Architektur und prüft jede bereits verwendete Adresse.

## Prüfung

Der Endpunkt wird mit einem Feature-Test auf Sichtbarkeit und Treffer geprüft;
die Vue-Komponente erhält einen fokussierten Test für Warnung, Übernahme und
Fortsetzen mit anderer Adresse. Die technische API-Dokumentation und das
Benutzerhandbuch werden um den neuen Ablauf ergänzt.
