# Task 2 — Admin-Passwort setzen und Initialpasswort vorschlagen

## Scope

Beim Anlegen eines Benutzerkontos soll der Super-Administrator ein starkes
Initialpasswort vorgeschlagen bekommen, es vor dem Speichern sehen und die
Kontoanlage ausdrücklich bestätigen. Der Server bleibt die Vertrauensgrenze:
Er validiert den bestätigten Wert, speichert nur den Hash, setzt
`must_change_password` und versendet die bestehende Kontodaten-Mail.

## Acceptance criteria

- Der Benutzereditor erzeugt einen Vorschlag aus Konsonant, Vokal, Konsonant,
  Vokal und vier Zahlen und zeigt ihn als Passwortfeld mit Erneuern-Aktion.
- Das Konto wird nur mit dem ausdrücklich gesendeten, starken Passwort angelegt;
  der Server überschreibt es nicht ungefragt mit einem zweiten Wert.
- Der Wert erscheint weder in URL noch Flash-Nachricht oder Inertia-Antwort.
- Nur der bereits autorisierte Super-Administrator-Pfad darf fremde Konten mit
  Passwort anlegen oder ändern.
- Tests decken Hashing, `must_change_password`, Mail und die Ablehnung schwacher
  Kontoanlage-Passwörter ab; Handbücher dokumentieren den Ablauf.

## Review boundary

Senior Review prüft UI-/Backend-Vertrag, Geheimnisweitergabe, Autorisierung,
Hashing, Mail und die aktualisierten Handbücher vor dem nächsten Task.
