# Plan: Weitere Models auf `AbstractModel` umstellen

## Ziel

Möglichst viele CRUD-fähige Alt-Models sollen auf das neuere Muster aus

- `AbstractModel`
- `AbstractCRUDController` / `AbstractApiCRUDController`
- modellbezogenen Contracts
- modellbezogenen Actions
- modellbezogenen Events
- autodiscovered Web- und API-Routen
- `AbstractModelUnitTest` / `AbstractModelFeatureTest`

umgestellt werden. `Parish` dient dabei als Referenz für das Zielbild.

## Ist-Zustand

### Bereits weitgehend im Zielbild

- `Parish`
- `City`
- `Location`
- `Tag`
- `Pool`
- `Poolmaster`
- `Business`
- `Motion`
- `Committee`
- `AdChannel`

Diese Models basieren bereits auf `AbstractModel`. Für diese Gruppe geht es vor allem noch um Konsolidierung von Routen, API-Controllern und Testabdeckung.

### Noch nicht auf `AbstractModel`, aber mit klarer CRUD-Nähe

Diese Gruppe bringt den größten Nutzen pro Umbau:

- `Baptism`
- `Wedding`
- `Funeral`
- `Booking`
- `Team`
- `ServiceGroup`
- `StreetRange`
- `SeatingSection`
- `SeatingRow`
- `Comment`
- `Attachment`
- `Occurence`
- `Replacement`
- `AdConfig`
- `Psalm`
- `Songbook`
- `Song`

### Vorläufig nicht erste Priorität

Diese Models sind fachlich schwerer, haben atypische Routen oder sehr viel Speziallogik. Sie sollen erst nach den obigen Wellen bewertet werden:

- `Service`
- `Absence`
- `User`
- `Role`
- `Sermon`
- `Funeral`, `Baptism`, `Wedding`-nahe Unterobjekte mit Dateihandling, falls der erste Umbau hier unerwartet tief eingreift
- reine Hilfs- oder Infrastruktur-Models ohne eigenes CRUD-UI

## Zentrale Befunde

### 1. Web-Routen sind nur teilweise autodiscovered

`routes/web.php` registriert bereits `registerRoutes()` für alle Models. Trotzdem liegen noch mehrere manuelle CRUD-Dateien unter `routes/web/*.php` und `routes/web/admin/*.php`, teils auch für bereits migrierte Models.

### 2. API-Routen sind noch nicht autodiscovered

`routes/api.php` lädt aktuell nur manuelle Dateien aus `routes/api/*.php`. Eine passende Auto-Registrierung existiert bereits in `routes/api_auto.php`, ist aber nicht eingebunden.

### 3. Das Zielmuster ist technisch vollständig vorhanden

Die nötigen Bausteine existieren bereits:

- `App\Models\AbstractModel`
- `App\Http\Controllers\AbstractCRUDController`
- `App\Http\Controllers\Api\AbstractApiCRUDController`
- `App\Providers\AutoModelsProvider`
- `App\Actions\AbstractCreateAction`
- `App\Actions\AbstractUpdateAction`
- `App\Actions\AbstractDeleteAction`
- generische Testbasen für Unit-, Feature- und Browser-Tests

### 4. Der Engpass ist nicht die Infrastruktur, sondern der Modellumbau

Pro Model müssen in der Regel parallel umgestellt werden:

- Model-Basis und statische Metadaten
- Controller und API-Controller
- Contracts
- Actions
- Events
- Routen
- Factories
- Policies
- Tests

## Zielbild pro Model

Ein Model gilt als migriert, wenn alle folgenden Punkte erfüllt sind:

1. Das Model erweitert `AbstractModel`.
2. Es definiert mindestens `prefix`, `prefixPlural`, `validationRules` und passende `fillable`-Felder.
3. Web-CRUD läuft über einen Controller auf Basis von `AbstractCRUDController`.
4. API-CRUD läuft über einen Controller auf Basis von `AbstractApiCRUDController`, sofern das Model API-seitig exposed wird.
5. Persistenzlogik liegt in `Create*`, `Update*`, `Delete*`-Actions.
6. Die Actions implementieren passende Contracts.
7. Die Actions dispatchen modellbezogene Events unter `App\Events\Models\...`.
8. Standard-CRUD-Routen kommen aus `registerRoutes()` / `registerApiRoutes()`.
9. Manuelle CRUD-Routen, die nur alte Standards duplizieren, sind entfernt.
10. Es gibt mindestens einen `AbstractModelUnitTest`; bei UI-Modellen zusätzlich Feature-Tests.

## Migrationsstrategie

### Welle 1: Bereits moderne `AbstractModel`-Klasse, aber Routen konsolidieren

Ziel: Das vorhandene neue Muster stabilisieren, bevor weitere Models darauf aufsetzen.

Betroffene Models:

- `City`
- `Location`
- `Parish`
- `Pool`
- `Poolmaster`
- `Tag`
- `Business`
- `Motion`
- `Committee`
- `AdChannel`

Arbeit:

- manuelle CRUD-Routen gegen `getRoutes()` prüfen
- doppelte oder veraltete CRUD-Definitionen entfernen
- Custom-Routen in den Route-Dateien belassen, aber klar von CRUD trennen
- API-Autodiscovery aktivieren
- für fehlende API-Controller der bereits migrierten Models nachziehen, falls nötig
- Testlücken schließen, besonders Feature-Tests

Besonders auffällig:

- `routes/web/admin/City.php` enthält noch manuelle CRUD-Routen für ein bereits migriertes Model
- `routes/web/admin/Location.php` enthält Unterobjekt-CRUD für `SeatingSection` und `SeatingRow`
- `routes/api.php` nutzt noch nicht `registerApiRoutes()`

### Welle 2: Kleine bis mittlere Admin-CRUD-Models

Ziel: Viele Models mit begrenztem Risiko auf das neue Schema ziehen.

Reihenfolge:

1. `Team`
2. `ServiceGroup`
3. `StreetRange`
4. `SeatingSection`
5. `SeatingRow`
6. `Comment`
7. `Attachment`
8. `Occurence`
9. `Replacement`
10. `AdConfig`

Pro Model:

- `extends Model` auf `extends AbstractModel`
- statische Routenmetadaten definieren
- generischen CRUD-Controller einführen oder bestehenden Controller darauf reduzieren
- `Api\...Controller` ergänzen, falls API-CRUD sinnvoll ist
- `Creates*`, `Updates*`, `Deletes*`-Contracts anlegen
- `Create*`, `Update*`, `Delete*`-Actions anlegen
- `Created*`, `Updated*`, `Deleted*`-Events anlegen
- manuelle CRUD-Routen entfernen
- Unit- und Feature-Tests hinzufügen

Hinweis:

`Comment` ist inzwischen als Teil-CRUD auf `AbstractModel` migriert; aktiv bleiben dort bewusst nur `store` und `destroy`. `Attachment` läuft ebenfalls eher als Teil-CRUD-Kandidat und sollte primär das bestehende Datei-/Crop-Update auf das Contract-/Action-Muster ziehen, ohne die fachlichen Attach/Detach-Endpunkte anderer Controller zu vereinheitlichen.

Zusatzbefund aus der ersten Sichtung:

- `Team` ist inzwischen migriert. Die manuellen Admin-CRUD-Routen wurden durch autodiscovery ersetzt; das Frontend verwendet jetzt die autodiscovered Namen unter `admin.*`. Die Sonderroute `teams.byCity` bleibt manuell bestehen.
- `ServiceGroup`, `StreetRange` und `AdConfig` bleiben ohne GUI-/API-Exposition. Für diese Gruppe ist das Zielbild also: `AbstractModel`, Contracts/Actions/Events, Policies und Tests für die Backend-Operationen, aber keine freigeschalteten autodiscovered CRUD-Routen.
- `ServiceGroup` ist inzwischen in diesem backend-only-Zielbild angekommen.
- `StreetRange` ist inzwischen ebenfalls backend-only auf `AbstractModel` umgestellt; der CSV-Import aus `Parish` nutzt jetzt die modellbezogenen Actions.
- `AdConfig` ist ebenfalls backend-only migriert; das Speichern aus `Service` läuft jetzt über Contracts/Actions statt direkte Model-CRUD-Aufrufe.
- `Replacement` ist inzwischen backend-only auf `AbstractModel` umgestellt; der Abwesenheits-Updatefluss nutzt jetzt die modellbezogenen Actions.
- `Booking` ist inzwischen als Teil-CRUD auf `AbstractModel` migriert; Standardoperationen `store`, `edit`, `update` und `destroy` laufen über Contracts/Actions, während `seatfinder`, `pin` und `finalize` Sonderrouten bleiben.
- `Psalm` ist inzwischen auf `AbstractModel` umgestellt; Web-CRUD und API-CRUD laufen über Contracts/Actions, der manuelle Split-Endpunkt bleibt gesondert.
- `Songbook` ist inzwischen auf `AbstractModel` umgestellt; Web-CRUD und API-CRUD laufen über Contracts/Actions, während Cover-Bild und Farbliste als Sonderrouten bestehen bleiben.
- `Song` ist inzwischen auf `AbstractModel` umgestellt; Web-CRUD läuft über autodiscovered Admin-Routen und Contracts/Actions, während `split`, `musicEditor`, `music`, `select`, `single` und `songbooks` als Spezialendpunkte bestehen bleiben. Die API-Schreibwege `store` und `update` verwenden bereits die neuen Actions, auch wenn ihre bestehenden Liturgie-Routen manuell bleiben.
- `Baptism` ist inzwischen auf `AbstractModel` umgestellt; die autodiscovered Web-CRUD-Routen laufen unter den bestehenden pluralen Routennamen `baptisms.*`, während `add`, `done`, `appointment.ical` und Attachment-Endpunkte manuell bleiben. Der Editor lädt keine komplette `Service`-Relation mehr und bleibt dadurch auch testbar ohne externe Liturgie-HTTP-Zugriffe.
- `Wedding` ist inzwischen als Teil-CRUD auf `AbstractModel` migriert; `create`, `edit`, `update` und `destroy` laufen über Contracts/Actions und autodiscovered `weddings.*`-Routen, während `add`, Wizard, `done` und die Attachment-Endpunkte manuell bleiben. Für den Editor wird die `Service`-Relation gezielt auf die benötigten Append-Felder reduziert, damit keine externen Liturgie-Zugriffe mehr in Tests auftreten.
- `Funeral` ist inzwischen als Teil-CRUD auf `AbstractModel` migriert; `create`, `edit`, `update` und `destroy` laufen über Contracts/Actions und autodiscovered `funerals.*`-Routen, während `add`, Wizard, PDF, `appointment.ical` und die Attachment-Endpunkte manuell bleiben. Auch hier wird die `Service`-Relation für den Editor auf die benötigten Felder reduziert, damit keine externen Liturgie-Zugriffe nötig sind.

### Welle 3: Fachlich reichere CRUD-Models

Ziel: Größere Alt-Controller in Actions zerlegen und trotzdem am `AbstractModel`-Standard ausrichten.

Reihenfolge:

1. `Booking`
2. `Psalm`
3. `Songbook`
4. `Song`
5. `Baptism`
6. `Wedding`
7. `Funeral`

Begründung:

- `Booking` hat überschaubare CRUD-Fläche, aber eigene Spezialendpunkte.
- `Psalm`, `Songbook` und `Song` sind gute Kandidaten, weil sie klaren Admin-CRUD haben, aber daneben Spezialendpunkte wie Split, Musik, Farben oder Selektoren behalten.
- `Baptism`, `Wedding` und `Funeral` haben zusätzliche Wizards, Attachments, Statuswechsel und Folgeaktionen; sie sind darum später dran.

Pro Model ist zu trennen:

- Standard-CRUD: in `AbstractCRUDController` + Actions
- Speziallogik: als zusätzliche Controller-Methoden behalten
- Dateihandling und Save-Folgeaktionen: in Actions verschieben
- Sonderrouten wie Wizard, Attachments, Export, Statuswechsel: manuell belassen

### Welle 4: Schwere Sonderfälle separat entscheiden

Nur nach den ersten drei Wellen neu bewerten:

- `Service`
- `Absence`
- `User`
- `Role`
- `Sermon`

Für diese Gruppe ist vorab zu klären, ob eine Vollmigration auf Standard-CRUD den tatsächlichen Fachfluss verbessert oder nur technische Einheitlichkeit erzwingt.

## Aktueller Abschlussstand

Der ursprünglich identifizierte CRUD-Migrationskorridor aus Welle 1 bis Welle 3 ist jetzt weitgehend umgesetzt:

- Routen-Autodiscovery für Web und API ist aktiv.
- Die kleinen und mittleren CRUD-Kandidaten aus Welle 2 sind bis auf den bewusst ausgesparten Sonderfall `Occurence` migriert oder fachlich als backend-only-Modelle eingeordnet.
- Die reicheren CRUD-Modelle aus Welle 3 (`Booking`, `Psalm`, `Songbook`, `Song`, `Baptism`, `Wedding`, `Funeral`) sind auf `AbstractModel` bzw. das zugehörige Contract-/Action-/Event-Muster umgestellt.
- Leere Alt-Route-Dateien aus der früheren manuellen Admin-CRUD-Struktur wurden entfernt.

Offen bleiben damit bewusst nur:

- `Occurence` als Sonderfall für expandierte bzw. entkoppelte Kalendertermine
- die zuvor schon separat markierten Welle-4-Kandidaten `Service`, `Absence`, `User`, `Role`, `Sermon`

## Route-Plan

### Grundregel

Nur Standard-CRUD soll über Autodiscovery laufen:

- `index`
- `create`
- `store`
- `show`
- `edit`
- `update`
- `destroy`

Alles andere bleibt manuell:

- Wizards
- Attach/Detach
- Split-Operationen
- Exporte
- Reader-/Public-Endpunkte
- Speziallisten und AJAX-Helfer

### Konkrete Routenbereinigung

1. `routes/web.php`
   - Web-Autodiscovery beibehalten
   - prüfen, ob doppelte Routennamen durch manuelle Dateien entstehen

2. `routes/api.php`
   - API-Autodiscovery aus `routes/api_auto.php` übernehmen oder direkt dort einbauen
   - manuelle API-Dateien danach nur noch für Nicht-CRUD-Endpunkte verwenden

3. Manuelle CRUD-Dateien abbauen
   - `routes/web/admin/City.php`
   - `routes/web/admin/Teams.php`
   - `routes/web/admin/Song.php`
   - `routes/web/admin/Psalm.php`
   - `routes/web/admin/Songbook.php`
   - `routes/web/admin/Location.php` für Unterobjekt-CRUD
   - `routes/web/Booking.php`
   - `routes/web/Funeral.php`
   - `routes/web/Wedding.php`
   - `routes/web/Baptism.php`
   - weitere Dateien nach Einzelprüfung

4. Route-Namen auf Standard bringen
   - wo möglich auf `singular.verb` bzw. pfadpräfixbasierte `admin.singular.verb`-Namen aus `AbstractModel` umstellen
   - Abweichungen nur behalten, wenn sie extern benutzt werden und eine Umstellung zu teuer wäre

## Controller-Plan

### Web-Controller

Jeder migrierte Controller soll am Ende nur noch enthalten:

- `protected string $modelClass = ...`
- optional `__construct()` für Middleware-Ausnahmen
- optional `getModelsForIndex()`
- optional `getSingleModel()`
- optional `getResourcesForEditor()`
- optional `preFillNewModel()`
- zusätzliche Nicht-CRUD-Methoden

Nicht mehr im Controller bleiben sollen:

- Validierung
- direkte `Model::create()`- oder `update()`-Persistenz
- Dispatch fachlicher CRUD-Events
- Redirect-Entscheidungen, die an die Action gekoppelt sind

### API-Controller

Für jedes migrierte API-Model ist zu entscheiden:

- voller Standard-CRUD über `AbstractApiCRUDController`
- oder nur Custom-API-Endpunkte ohne Standard-CRUD

Wenn Standard-CRUD gebraucht wird, soll der API-Controller minimal bleiben wie bei den bereits migrierten Models.

## Contracts-, Actions- und Event-Plan

Pro migriertem Model entstehen drei Contracts:

- `Creates...`
- `Updates...`
- `Deletes...`

Dazu drei Actions:

- `Create...`
- `Update...`
- `Delete...`

Und drei Events:

- `Created...`
- `Updated...`
- `Deleted...`

Konvention:

- Namespace wie bei `Parish`
- Validierung gegen `Model::$validationRules`
- Authorisierung in der Action
- Redirects über `redirectTo()`
- Benutzerfeedback über `$messages`

## Testplan

### Unit-Tests

Für jedes migrierte Model:

- `tests/Unit/{Model}UnitTest.php` auf Basis von `AbstractModelUnitTest`

Damit werden automatisch geprüft:

- Model ist `AbstractModel`
- Controller- und API-Controller-Klassen existieren
- Routen sind registriert
- Contracts sind bindbar
- Actions dispatchen die erwarteten Events

### Feature-Tests

Für jedes UI-CRUD-Model:

- `tests/Feature/{Model}FeatureTest.php` auf Basis von `AbstractModelFeatureTest`

Damit werden mindestens abgesichert:

- Index-Ansicht
- Editor-Ansicht
- Create
- Update
- Delete

### Browser-Tests

Nur selektiv für komplexe Editoren oder risikoreiche UI-Flows:

- `Song`
- `Funeral`
- `Wedding`
- `Baptism`
- eventuell `Booking`

## Reihenfolge der Umsetzung

1. Branch vorbereiten und diesen Plan festschreiben.
2. Route-Autodiscovery für API und bereist migrierte Models bereinigen.
3. Welle 1 abschließen und Tests grün halten.
4. Welle 2 in kleinen, jeweils testbaren Paketen umsetzen.
5. Welle 3 einzeln und vorsichtig mit Fokus auf Controller-Entflechtung umsetzen.
6. Welle 4 separat neu entscheiden.

## Arbeitspaket-Schnitt

Die Umsetzung sollte nicht als ein großer Umbau erfolgen, sondern in kleinen fachlich sauberen Paketen, idealerweise je Model oder je eng zusammengehöriger Modelgruppe:

- `Team` erledigt
- `ServiceGroup` erledigt
- `StreetRange` erledigt
- `SeatingSection` + `SeatingRow` erledigt
- `Comment` + `Attachment` erledigt
- `Occurence` + `Replacement`
  `Replacement` erledigt, `Occurence` wegen Spezialrouten und fehlendem Standard-CRUD gesondert bewerten
- `AdConfig` erledigt
- `Booking` erledigt
- `Psalm` erledigt
- `Songbook` erledigt
- `Song` erledigt
- `Baptism`
- `Wedding`
- `Funeral`

## Abnahmekriterien

Die Initiative ist erfolgreich, wenn am Ende gilt:

- ein deutlich größerer Teil der CRUD-Models basiert auf `AbstractModel`
- Standard-CRUD-Routen werden nicht mehr manuell dupliziert
- API-CRUD kann über dieselbe Modellmetadatenbasis registriert werden
- Controller sind dünn, Persistenz liegt in Actions
- Contracts werden automatisch gebunden
- neue und umgestellte Models hängen an den generischen Tests
- Speziallogik bleibt erhalten, ist aber klar vom Standard-CRUD getrennt

## Offene Prüfpunkte während der Umsetzung

- Welche manuellen Routennamen sind extern oder im Frontend hart verdrahtet?
- Für welche Models ist `show` fachlich gar nicht gewünscht und muss per `exceptRoutes` deaktiviert werden?
- Welche Models brauchen wirklich API-CRUD und welche nur einzelne API-Helfer?
- Wo müssen bestehende Policies für `index` auf `viewAny` oder umgekehrt harmonisiert werden?
- Bei welchen Models muss `DemoBuilder` wegen neuer oder geänderter personenbezogener Daten angepasst werden?
