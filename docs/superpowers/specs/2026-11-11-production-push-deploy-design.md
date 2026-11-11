# Produktionsdeploy aus GitHub Actions – Design

**Ziel:** Nach einem erfolgreichen Release-Build sollen die konfigurierten Produktionshosts seriell über SSH aktualisiert werden können, ohne Hostdaten oder Zugangsdaten im Repository zu speichern.

**Ansatz:** GitHub Actions liest eine JSON-Matrix aus der Repository- oder Organisationsvariable `PRODUCTION_HOSTS`. Der Job verwendet das geschützte GitHub Environment `production`, einen separaten Deploy-Schlüssel und bekannte Hostschlüssel. Auf jedem Host laufen das vorhandene Backup- und Update-Verfahren mit dem konkreten Release-Tag; ein Fehler stoppt den weiteren Rollout.

**Nicht enthalten:** Die realen Produktionsziele, private Schlüssel, Known-Hosts-Einträge oder die automatische Anlage von GitHub-Variablen werden nicht versioniert und nicht durch CI erzeugt.

**Sicherheitsentscheidungen:**

- Hostdaten werden als nicht geheime Konfiguration in `PRODUCTION_HOSTS` gepflegt.
- Der private Schlüssel liegt ausschließlich als `PRODUCTION_DEPLOY_SSH_KEY` im `production`-Environment.
- `PRODUCTION_DEPLOY_KNOWN_HOSTS` verhindert eine unbeabsichtigte Hostschlüssel-Akzeptanz.
- Der Rollout läuft mit `max-parallel: 1`.
- Das Ziel führt vor jedem Update `./planer prod backup create` aus.
- Das Update verwendet immer `./planer prod update --ref <Release-Tag> --backup-confirmed`; `latest` wird nicht verwendet.

**JSON-Schema:**

```json
{
  "include": [
    {
      "name": "prod-a",
      "user": "deploy",
      "host": "prod-a.example.org",
      "path": "/srv/pfarrplaner"
    }
  ]
}
```
