[//]: # (TOC: 3. Klassische Installation)

# Klassische Installation mit Git und CLI-Installer

Die klassische Installation ist der direkteste und transparenteste Weg. Sie eignet sich besonders, wenn Sie eine feste Linux-Umgebung mit Webserver, PHP und Datenbank selbst betreiben.

## Zielbild

Sie erhalten:

- einen lokalen Pfarrplaner-Quellcode-Checkout
- installierte PHP- und npm-Abhängigkeiten
- gebaute Frontend-Assets
- eine fertige `.env`
- eine initialisierte Datenbank
- ein erstes Super-Admin-Konto

## Server vorbereiten

Vor `git clone` sollten diese Punkte fertig sein:

- DNS-Eintrag und öffentliche URL
- HTTPS-Zertifikat
- Datenbank und Datenbankbenutzer
- Webserver-VHost auf `.../pfarrplaner/public`
- Systembenutzer, unter dem Webserver und Deployments arbeiten
- Schreibrechte für `storage/` und `bootstrap/cache/`

## Quellcode holen

```bash
git clone https://codeberg.org/pfarr.tools/pfarrplaner.git
cd pfarrplaner
```

## PHP-Abhängigkeiten installieren

```bash
composer install --no-dev --optimize-autoloader
```

Wenn Composer schon hier scheitert, sind fast immer PHP-Version oder PHP-Erweiterungen unvollständig.

## Frontend-Abhängigkeiten und Assets

```bash
npm install
npm run build
```

Planen Sie dafür genügend Speicher und CPU-Zeit ein. Auf sehr kleinen Servern ist es oft sinnvoll, Assets in einer Build-Umgebung zu erzeugen und erst dann auf das Zielsystem zu übertragen.

## Laufzeitverzeichnisse und Rechte

Typischerweise brauchen mindestens diese Verzeichnisse Schreibrechte:

- `storage/`
- `bootstrap/cache/`

Rechte sollten zum Webserver-Benutzer und zu Ihrem Deployment-Benutzer passen. Vermeiden Sie pauschale globale Schreibrechte, wenn gezieltere Rechte möglich sind.

## Neuer CLI-Installer

Die produktive Erstinstallation erfolgt mit:

```bash
php artisan pfarrplaner:install
```

Der Installer fragt unter anderem ab:

- Anwendungsname
- öffentliche URL
- Anwendungsumgebung
- Debug-Modus
- Datenbanktyp und Datenbankzugang
- Mail-Absender
- erstes Super-Admin-Konto

Er schreibt die `.env`, erzeugt Schlüssel, führt Migrationen aus, seedet Rollen und legt das erste Super-Admin-Konto an.

## Nicht-interaktive Installation

Für automatisierte Deployments kann der Installer auch mit Optionen aufgerufen werden, zum Beispiel:

```bash
php artisan pfarrplaner:install \
  --app-name="Pfarrplaner Musterstadt" \
  --app-url="https://planer.example.org" \
  --app-env=production \
  --app-debug=false \
  --db-connection=mysql \
  --db-host=127.0.0.1 \
  --db-port=3306 \
  --db-database=pfarrplaner \
  --db-username=pfarrplaner \
  --db-password="geheim" \
  --admin-first-name="Maria" \
  --admin-last-name="Muster" \
  --admin-email="admin@example.org" \
  --admin-password="sehr-geheim" \
  --mail-from-address="pfarrplaner@example.org" \
  --mail-from-name="Pfarrplaner"
```

## Nacharbeiten nach dem Installer

Nach der Erstinstallation sollten Sie mindestens noch:

1. `php artisan optimize` ausführen.
2. Mailversand testen.
3. Login mit dem Super-Admin prüfen.
4. Scheduler und Queue-Worker einrichten.
5. Backup-Strategie festlegen.
6. Chromium-gestützte Ausgaben oder Screenshots testweise ausführen.

## Scheduler und Queue

Für den Scheduler gehört ein Cronjob auf den Server, typischerweise einmal pro Minute:

```bash
* * * * * cd /pfad/zu/pfarrplaner && php artisan schedule:run >> /dev/null 2>&1
```

Falls Queue-Jobs genutzt werden, richten Sie zusätzlich einen dauerhaft laufenden Worker ein, zum Beispiel über `systemd` oder `supervisord`.

## Webserver-Prüfliste

- `APP_URL` stimmt mit der echten öffentlichen URL überein.
- Der VHost zeigt auf `public/`.
- `APP_DEBUG` ist in produktiven Umgebungen ausdrücklich auf `false` gesetzt.
- Entwickler-Helfer wie Ignition-, Boost-, Telescope- oder Dusk-Endpunkte werden nur bei bewusst gesetzten Umgebungsvariablen aktiviert.
- PHP-FPM oder mod_php nutzt die richtige PHP-Version.
- Upload-Limits passen zu Dateianhängen und Importen.
- Timeouts sind nicht zu knapp.

## Wann dieser Weg sinnvoll ist

Die klassische Installation ist meist die beste Wahl, wenn:

- Sie Linux, PHP und Webserver selbst verwalten.
- Sie Dateipfade und Systemdienste direkt kontrollieren möchten.
- Sie Updates bewusst und manuell einspielen wollen.
