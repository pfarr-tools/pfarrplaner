# Rollen und Berechtigungen aus der UI entfernen

## Ausgangslage

Issue #6 beschreibt, dass Rollen und Berechtigungen fachlich hardcodiert sind,
aber derzeit über die Admin-Oberfläche bearbeitet werden können. Im Repository
existieren dafür mehrere Quellen: `RoleSeeder`, `InstallSetup` und die Rollen-
CRUD-Oberfläche. Die Definitionen und Zuweisungen sind nicht vollständig
deckungsgleich.

## Zielbild

- `src/config/roles.php` ist die einzige versionierte Quelle für die
  vorgesehenen Rollen, Berechtigungen und deren Zuordnung.
- Ein idempotenter Artisan-Befehl gleicht die Datenbank mit dieser Konfiguration
  ab und unterstützt eine reine Vorschau.
- Erstinstallation und bestehender Update-/Bootstrap-Ablauf verwenden denselben
  Abgleich.
- Rollen und Berechtigungen sind nicht mehr über `admin/*` editierbar.
- Die Super-Administrator-Rolle bleibt als Gate-Ausnahme erhalten.

## Sicherheits- und Datenentscheidung

Der Standardabgleich ist nicht destruktiv: konfigurierte Rollen und
Berechtigungen werden angelegt bzw. aktualisiert; nicht konfigurierte vorhandene
Rollen werden nicht automatisch gelöscht. Ein späterer, ausdrücklich
bestätigter Bereinigungsmodus kann separat ergänzt werden, ist aber nicht
Bestandteil dieses Issues. Damit werden bei einem Update keine Benutzerrollen
oder Zuweisungen unbeabsichtigt entfernt.

Die bisherige Rollenstruktur aus `InstallSetup` wird mit den vollständigen
Permission-Namen aus `RoleSeeder` abgeglichen. Die daraus entstehende
Konfiguration wird vor der Implementierung gegen die fachlich erwartete
Produktionsstruktur geprüft; unklare Abweichungen sind ein Stop-Punkt und
werden nicht stillschweigend überschrieben.

## Verifizierung

Fokussierte Tests prüfen die Konfigurationskonsistenz, den idempotenten Sync,
die Dry-Run-Ausgabe, die Erreichbarkeit des Befehls sowie das Fehlen der
Rollen-CRUD-Routen und des Admin-Menüeintrags. Anschließend werden die
relevanten Docker-isolierten Tests und der vorhandene Build-/Bootstrap-Check
ausgeführt.
