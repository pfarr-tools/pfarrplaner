# Sentry-Exception-Reporting

## Ziel

Nicht erwartete Laravel-Ausnahmen aus dem produktiven HTTP-Pfad müssen trotz
der internen Produktionsnetzwerke an Sentry gemeldet werden können. Die
vorhandene Produktionskonfiguration mit `SENTRY_LARAVEL_DSN` und `SENTRY_TAG`
bleibt unverändert; geheime Werte werden weder dokumentiert noch getestet.

## Nicht-Ziele

- Keine Weiterleitung sämtlicher Laravel-Logmeldungen an Sentry.
- Keine Änderung der bewusst nicht gemeldeten 404-, Authentifizierungs- und
  Validierungsfehler.
- Keine Änderung der Sentry-DSN oder des Feedback-Endpunkts.

## Ursache und Ansatz

Der bestehende Laravel-11+-Hook `Sentry\Laravel\Integration::handles()` und
der eigene Exception-Handler funktionieren bereits korrekt. In der
Produktions-Compose-Konfiguration hängen die Anwendungskomponenten aber nur an
internen Netzwerken. Ein separates, nicht internes Egress-Netzwerk wird für
die Komponenten ergänzt, die Sentry-Ereignisse versenden können. Ein
Compose-Konfigurationstest prüft die Netzwerkanbindung; der bestehende
Exception-Regressionstest prüft weiterhin den Laravel-Report-Pfad.

## Erfolgskriterium

Eine unbehandelte Ausnahme kann im produktiven Laravel-Exception-Pfad den
konfigurierten Sentry-Host erreichen, während die bestehenden `dontReport`
Ausnahmen nicht gemeldet werden.
