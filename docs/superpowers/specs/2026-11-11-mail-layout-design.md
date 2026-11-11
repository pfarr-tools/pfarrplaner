# Mail-Layout für Issue #65

## Ziel

Alle HTML-Mails sollen in Outlook und modernen Mailclients stabil, lesbar und
im Pfarrplaner-Farbschema erscheinen. Die fachlichen Inhalte, Links und
iCalendar-Anhänge bleiben unverändert.

## Ansätze

- Nur die Laravel-Markdown-Komponenten überarbeiten: deckt die Markdown-Mails
  ab, lässt aber die eigenständigen Service-Mails mit ihrer zweiten Layoutbasis
  unverändert.
- Alle Mails auf einen gemeinsamen Markdown-Pfad umstellen: erzeugt unnötige
  Änderungen an vielen fachlichen Templates und riskiert Inhaltsregressionen.
- Die beiden bestehenden Layoutpfade jeweils auf dieselben tabellenbasierten,
  inline-kompatiblen Gestaltungsregeln ausrichten: erhält die vorhandenen
  Templates und ist der gewählte Ansatz.

## Umsetzung

Die Laravel-Mail-Komponenten erhalten einen gebrandeten Header, Footer,
Button- und Panel-Stil. Das Markdown-Theme liefert passende Standardfarben
und wird weiterhin von Laravel in die HTML-Ausgabe eingebettet. Das eigene
Service-Layout erhält denselben Header, Kartenrahmen, Abstände und Footer.

Render-Tests prüfen mindestens Pfarrplaner-Farbe, Kartenrahmen, Branding und
die unveränderte fachliche Mail-Erzeugung. SMTP, Queue, Mailserver und
Zustellung sind ausdrücklich nicht Teil dieses Issues.
