[//]: # (TOC: 8. OpenAPI und Schnittstellenpflege)

# OpenAPI und Schnittstellenpflege

Pfarrplaner enthält bereits eine technische Grundlage für eine maschinenlesbare API-Beschreibung.

## Generator

Der OpenAPI-Build wird über diesen Artisan-Befehl erzeugt:

```bash
php artisan openapi:generate
```

Standardziel ist:

```text
public/openapi.json
```

## Technische Grundlage

- `app/Console/Commands/GenerateOpenApiSpec.php`
- `app/Http/Controllers/Api/OpenApiSpec.php`
- OpenAPI-Attribute direkt an API-Controllern

## Veröffentlichung im Handbuch

Wenn `public/openapi.json` vorhanden ist, wird die Datei beim Build des technischen Handbuchs zusätzlich in die statische Handbook-Site kopiert. Damit kann sie gemeinsam mit der technischen Dokumentation ausgeliefert werden.

## Empfohlener Pflegeprozess

1. API-Route anlegen oder ändern.
2. Controller und Request-Klassen anpassen.
3. OpenAPI-Attribute ergänzen oder aktualisieren.
4. `php artisan openapi:generate` ausführen.
5. Technisches Handbuch prüfen und gegebenenfalls Kapiteltexte aktualisieren.

## Zielbild

Langfristig sollte jede öffentlich oder integrationsrelevant genutzte API-Funktion sowohl:

- in `openapi.json` beschrieben sein
- als erklärender Text im technischen Handbuch auftauchen

So bleibt die Dokumentation sowohl für Menschen als auch für Werkzeuge brauchbar.

## Härtungsregel für neue Endpunkte

Bei neuen API-Routen gilt im Projekt jetzt die Regel:

- `/api` ist standardmäßig geschützt
- öffentliche Ausnahmen müssen ausdrücklich begründet und dokumentiert werden
- derzeit ist der Health-Check unter `/api/health` die vorgesehene öffentliche Ausnahme

Bei Änderungen an der API-Dokumentation ist deshalb immer mitzupflegen, ob ein Endpunkt geschützt oder bewusst öffentlich ist.
