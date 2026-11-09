[//]: # (TOC: 7. Berechtigungen und Policies)

# Berechtigungen und Policies

Dieses Kapitel beschreibt die im Anwendungscode verfügbaren Policy-Verben. Eine Policy entscheidet immer für ein angemeldetes Benutzerkonto und gegebenenfalls für ein konkretes Objekt. Die Rolle **Super-Administrator:in** erhält über den globalen Gate-Vorentscheid Zugriff auf alle Fähigkeiten. Ein Eintrag „Alle“ bedeutet deshalb nicht automatisch „öffentlich“: Die Anmeldung und die übrigen globalen Schutzmechanismen gelten weiterhin.

## Lesende und schreibende Standardverben

`index`, `viewAny` und `view` öffnen Listen bzw. einzelne Einträge. `create` legt neue Einträge an, `update` ändert sie, `delete` verschiebt sie in den Papierkorb, `restore` holt sie zurück und `forceDelete` löscht sie endgültig. `index` und `viewAny` sind im Projekt teilweise historische Synonyme; dokumentiert sind beide, wenn der Code sie anbietet.

| Bereich / Policy | Verfügbare Verben | Wer darf es bzw. wovon hängt es ab? |
| --- | --- | --- |
| Abwesenheiten (`Absence`) | `view`: wie `update`; `create`: eigene Änderung oder `manage_absences`/`fremden-urlaub-bearbeiten`; `update`: eigener Eintrag, zuständige:r Urlaubsadministrator:in, aktiver Genehmiger oder fremde Person mit `fremden-urlaub-bearbeiten` in einer schreibbaren Gemeinde, nicht für Pfarrer:innen; `delete`, `restore`, `forceDelete`: wie `update`; `selfAdminister`: eigener Eintrag und nur wenn keine Urlaubsadministration/Genehmigung hinterlegt ist. | Die konkrete Abwesenheit und ihr Workflowstatus werden berücksichtigt. |
| Werbekanäle (`AdChannel`) | `viewAny`: Admin oder mindestens eine Gemeinde; `view`: Mitgliedschaft in der Kanalgemeinde; `create`: schreibbare Gemeinde; `update`, `delete`, `restore`, `forceDelete`: schreibbare Kanalgemeinde. | Der Zugriff unterscheidet Lesen (`cities`) und Schreiben (`writableCities`). |
| Benutzerdefinierte Werbekanäle (`CustomAdChannel`) | `viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete` | Die Policy ist als Erweiterungspunkt vorhanden; die aktuelle Implementierung verweigert diese Fähigkeiten standardmäßig. |
| Werbekonfiguration (`AdConfig`) | `viewAny`: immer verweigert; `view`, `create`, `update`, `delete`, `restore`, `forceDelete`: Berechtigung des zugehörigen Gottesdienstes (`view` bzw. `update`). | Die Verben werden nicht von einer eigenen Werberechte-Rolle, sondern vom Gottesdienstobjekt entschieden. |
| Anhänge (`Attachment`) | `update` | Wer das anhängende Objekt ändern darf; Papierkorbobjekte bleiben gesperrt. |
| Taufen (`Baptism`) | `index`, `create`, `update`, `delete`, `restore`, `forceDelete` | `gd-bearbeiten`; zusätzlich wird bei vorhandenen Daten die Gemeindezuordnung geprüft. |
| Sitzplatzbuchungen (`Booking`) | `viewAny`: immer verweigert; `view`, `create`, `update`, `delete`: `view` bzw. `update` des zugehörigen Gottesdienstes. | Buchungen werden ausschließlich über ihren Gottesdienst geschützt. |
| Kalender (`Calendar`) | `month`: gewährt; `printsetup`: gewährt; `print`: Policy-Methode ohne Rückgabewert und damit keine gewährte Fähigkeit. | Diese drei Gates heißen `calendar.month`, `calendar.printsetup` und `calendar.print`. |
| Städte (`City`) | `index`: Admin, lokaler Admin oder `ort-bearbeiten`; `view`, `viewAny`: gewährt; `create`: Admin; `update`: `gd-opfer-bearbeiten`/`ort-bearbeiten` in schreibbarer Stadt oder Admin in verwalteter Stadt; `delete`: Super-Administrator:in; `restore`, `forceDelete`: verweigert. | Lesen und Schreiben sind bewusst unterschiedlich geschützt. |
| Kommentare (`Comment`) | `create`, `delete` | Die `update`- bzw. `delete`-Berechtigung des zugehörigen Gottesdienstes. |
| Tage (`Day`) | `view`, `create`, `update`: gewährt; `delete`, `restore`, `forceDelete`: `isAdmin`. | Das Löschen von Kalendertagen bleibt administrativ. |
| Beerdigungen (`Funeral`) | `index`, `create`, `update`, `delete`, `restore`, `forceDelete` | `gd-bearbeiten` und bei Verknüpfung die Berechtigung des Gottesdienstes. |
| Orte (`Location`) | `index`: Administrator:in, `ort-bearbeiten`, `gd-opfer-bearbeiten` oder mindestens eine schreibbare Gemeinde; `view`: gewährt; `create`: schreibbare Gemeinde; `update`, `delete`, `restore`, `forceDelete`: Administrator:in der Gemeinde oder `kirche-bearbeiten` in schreibbarer Gemeinde. | `index` ist breiter als die eigentliche Änderung eines Ortes. |
| Gemeinden/Pfarrämter (`Parish`) | `index`, `create`, `update`, `delete`, `restore`, `forceDelete` | Administrator:innen bzw. `pfarramt-bearbeiten`; Gemeindezuordnung und Verwaltungskontext werden geprüft. |
| Vertretungspools (`Pool`) | `viewAny`, `index`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete` | Pfarrer:innen oder Administrator:innen. |
| Poolmaster (`Poolmaster`) | `viewAny`, `view`, `create`: gewährt; `update`: eigener Eintrag, Urlaubsadministrator:in oder `fremden-urlaub-bearbeiten` in schreibbarer Gemeinde, nicht für Pfarrer:innen; `delete`: wie `update`; `restore`, `forceDelete`: verweigert. | Die Policy enthält keinen Restore-/Permanent-Löschen-Pfad. |
| Psalmen (`Psalm`) | `viewAny`, `index`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete` | Angemeldete Personen; globale Freigabe über die Psalm-Policy. |
| Vertretungen (`Replacement`) | `viewAny`: immer verweigert; `view`: `view` der Abwesenheit; `create`: `update` der Abwesenheit; `update`: `update` der Abwesenheit; `delete`: `delete` der Abwesenheit. | Ohne Abwesenheitsrecht gibt es kein eigenständiges Vertretungsrecht. |
| Sitzplanreihen (`SeatingRow`) | `index`, `viewAny`, `create`: mindestens eine schreibbare Gemeinde; `update`: `update` des zugehörigen Ortes; `delete`: `delete` des zugehörigen Ortes. | Super-Administrator:innen werden zusätzlich über `before` global freigeschaltet. |
| Sitzplanbereiche (`SeatingSection`) | `index`, `viewAny`, `create`: mindestens eine schreibbare Gemeinde; `update`: `update` des zugehörigen Ortes; `delete`: `delete` des zugehörigen Ortes. | Super-Administrator:innen werden zusätzlich über `before` global freigeschaltet. |
| Predigten (`Sermon`) | `update`: nur wenn mindestens ein Gottesdienst verknüpft ist und die Person jeden verknüpften Gottesdienst ändern darf. | Ein nicht verknüpftes Sermon darf nicht geändert werden. |
| Gottesdienstgruppen (`ServiceGroup`) | `viewAny`, `index`, `create`, `update`, `delete` | Angemeldete Personen; globale Freigabe über die ServiceGroup-Policy. |
| Gottesdienste (`Service`) | `index`, `viewAny`: `gd-bearbeiten`; `view`: gewährt; `create`: `gd-bearbeiten`; `update`: eigener zugewiesener Pastor, Admin oder `gd-bearbeiten` in schreibbarer Gemeinde, aktueller Poolmaster oder Template mit `gd-bearbeiten`; `selfAssign`: `update` oder `claimPredicant`; `claimPredicant`: Rolle Prediger:in/Gottesdienstbearbeiter:in/Pfarrer:in, Bedarf, kein Pastor, heute/zukünftig und sichtbare Gemeinde; `delete`, `restore`, `forceDelete`: wie `update`. | `forceDelete` ist nicht pauschal auf Administrator:innen begrenzt. |
| Lieder (`Song`) | `index`, `viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete` | Angemeldete Personen; globale Freigabe über die Song-Policy. |
| Liederbücher (`Songbook`) | `index`, `viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete` | Administrator:innen, lokale Administrator:innen oder `liederbuecher-bearbeiten`. |
| Straßenbereiche (`StreetRange`) | `viewAny`, `view`, `create`, `update`, `delete` | Die entsprechende Berechtigung des übergeordneten Pfarramts. |
| Stichworte (`Tag`) | `viewAny`, `index`, `create`, `update`, `delete`, `forceDelete`, `restore` | Angemeldete Personen; globale Freigabe über die Tag-Policy. |
| Teams (`Team`) | `index`, `viewAny`, `create`: mindestens eine schreibbare Gemeinde; `update`, `delete`: die Teamgemeinde ist schreibbar. | Die Team-Policy hat keinen pauschalen Zugriff für jedes angemeldete Konto. |
| Liturgische Texte (`Text`) | `viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete` | Angemeldete Personen; globale Freigabe über die Text-Policy. |
| Benutzerkonten (`User`) | `index`: Super-/Admin, lokale Adminstadt oder `benutzerliste-lokal-sehen`/`benutzer-bearbeiten`; `view`: `benutzer-bearbeiten`; `create`: Adminstadt oder `benutzer-bearbeiten`; `update`, `resetPassword`: eigenes Konto, Admin, verwaltete Stadt oder `benutzer-bearbeiten`, niemals Ziel-Super-Admin; `delete`: Admin/`benutzer-bearbeiten` in verwalteter Zielgemeinde, niemals Ziel-Super-Admin; `restore`, `forceDelete`: `benutzer-bearbeiten`; `join`: Admin oder `benutzer-bearbeiten`; `findDuplicates`, `fixDuplicates`: Super-Administrator:in; `merge`: Admin oder gemeinsame Adminstadt, niemals Ziel-Super-Admin; `impersonate`: Admin, niemals Ziel-Super-Admin; `doJoin`: gewährt; `editAbsences`: eigenes Konto oder Fremdurlaubs-/Adminrecht in schreibbarer Zielgemeinde, nicht für Pfarrer:innen. | Selbstbearbeitung und einzelne Adminaktionen haben eigene Regeln; sie sind nicht pauschal gleichbedeutend mit `benutzer-bearbeiten`. |
| Trauungen (`Wedding`) | `index`, `create`, `update`, `delete`, `restore`, `forceDelete` | `gd-bearbeiten` und bei Verknüpfung die Berechtigung des Gottesdienstes. |

## Direkte Berechtigungen außerhalb der Modell-Policies

Einige Funktionen werden nicht als CRUD-Policy eines Modells geprüft. Dazu gehören insbesondere die fachlichen Berechtigungen für Gottesdienste und Eingaben:

| Berechtigung | Bedeutung |
| --- | --- |
| `gd-bearbeiten` | Allgemeines Bearbeiten von Gottesdiensten und abhängigen Eingaben. |
| `gd-allgemein-bearbeiten` | Allgemeine Gottesdienstangaben. |
| `gd-pfarrer-bearbeiten`, `gd-organist-bearbeiten`, `gd-mesner-bearbeiten` | Jeweilige Mitwirkungsbereiche im Gottesdienst. |
| `gd-abendmahl-bearbeiten`, `gd-kinderkirche-bearbeiten`, `gd-opfer-bearbeiten`, `gd-kasualien-bearbeiten`, `gd-taufe-bearbeiten` | Jeweilige fachliche Eingabe- oder Kasualienbereiche. |
| `ort-bearbeiten`, `kirche-bearbeiten`, `pfarramt-bearbeiten` | Schreiben in Orts-, Kirchen- bzw. Pfarramtsdaten. |
| `fremden-urlaub-bearbeiten`, `urlaub-lesen` | Fremde Abwesenheiten bearbeiten bzw. Urlaubsinformationen lesen. |
| `benutzerliste-lokal-sehen`, `benutzer-bearbeiten` | Lokale Benutzerliste sehen bzw. Benutzerkonten bearbeiten. |
| `liederbuecher-bearbeiten` | Liederbücher verwalten. |

Die verbindliche Zuordnung der Rollen zu diesen Berechtigungen steht in `src/config/roles.php`. Änderungen an Rollen oder Policies müssen dieses Kapitel mitprüfen lassen.
