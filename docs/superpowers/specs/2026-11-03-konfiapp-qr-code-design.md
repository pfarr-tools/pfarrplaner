# KonfiApp-QR-Codes

## Ziel

QR-Codes für die KonfiApp sollen mit dem von der KonfiApp erwarteten Format
erzeugt werden. Der aktuelle Generator verwendet `qrencode`, das im Docker-
Image bislang fehlt, und überlässt Version, Maske und Fehlerkorrektur der
automatischen Auswahl.

## Umfang

- `qrencode` wird als Laufzeitpaket in das gemeinsame Debian-Image aufgenommen.
- Der bestehende Generator verwendet QR-Version 2, Maske 4 und
  Fehlerkorrektur M.
- Der Payload bleibt unverändert und wird weiterhin ohne abschließenden
  Zeilenumbruch kodiert.
- Andere QR-Endpunkte verwenden denselben Generator und erhalten dadurch
  ebenfalls das festgelegte, kompatible Format.

## Ansätze

1. `qrencode` im bestehenden Image ergänzen und dessen Optionen festlegen.
   Das nutzt den vorhandenen Pfad mit kleinstem Änderungsumfang.
2. Eine PHP-QR-Bibliothek als Ersatz einführen. Das wäre eine größere,
   unnötige Infrastrukturänderung.
3. Einen separaten KonfiApp-Generator einführen. Das würde doppelte QR-Logik
   schaffen, obwohl die Formatvorgabe für den bestehenden Generator genügt.

Gewählt ist Ansatz 1, weil er die fehlende Laufzeitabhängigkeit behebt und die
bekannten KonfiApp-Parameter direkt im etablierten Generator festlegt.

## Prüfung

Ein Unit-Test prüft die vollständige `qrencode`-Befehlszeile einschließlich
Version, Maske, Fehlerkorrektur und Byte-Modus. Der Docker-Build wird zusätzlich
darauf geprüft, dass `qrencode --version` im App-Image erfolgreich läuft.
