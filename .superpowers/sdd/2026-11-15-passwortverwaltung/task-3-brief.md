# Task 3 — Nicht umgehbares Interstitial und Passwort-Reset

## Scope

Ein Konto mit `must_change_password` darf nach der Anmeldung ausschließlich die
schlanke Passwortwechsel-Seite erreichen. Direkte URLs, Inertia-Navigation und
Zurück-Navigation dürfen keine geschützte Anwendung öffnen. Logout bleibt
erreichbar; ein kontrollierter Admin-Benutzerwechsel darf den Proxy-Fall nicht
fälschlich blockieren. Der öffentliche Reset bleibt generisch, rate-limitiert
und ohne Benutzerexistenz-Leak.

## Acceptance criteria

- Der erzwungene Wechsel verwendet ein eigenes Auth-Layout ohne Admin-Menü.
- Middleware/Redirect-Regeln schützen Web- und Inertia-Anfragen zentral.
- Erfolgreicher Passwortwechsel löscht das Flag; ungültige Eingaben lassen das
  Konto im Interstitial.
- Direkte Home-/Admin-/Profil-URLs, Browser-Zurück und Inertia-Wechsel werden
  als gesperrte Zustände getestet.
- Öffentliche Reset-Anfragen liefern unabhängig von der E-Mailadresse eine
  generische Antwort; Tokenablauf, Throttling, starker Passwortstandard und
  erfolgreiche Flag-Löschung bleiben getestet.
- Benutzer- und Administratorenhandbuch beschreiben Ablauf und Recovery.

## Review boundary

Senior Review prüft Redirect-/Session-Logik, Admin-Proxy-Ausnahme, Reset-
Sicherheit, Testisolierung, Browserabdeckung und Handbücher vor Etappe 4.
