
Installation
============

Der Pfarrplaner läuft auf einer LAMP-Plattform unter Apache 2 und MySQL/MariaDB. 
Folgende Schritte sind zur Installation einer eigenen Instanz notwendig:

### Voraussetzungen installieren

- PHP installieren (Version 8.1 oder neuer, mit folgenden Extensions: mysql, soap, mbstring)
- Apache installieren und einrichten. Der verwendete Virtualhost muss auf *Pfarrplaner-Ordner*/public zeigen.
- MySQL oder MariaDB installieren, eine Datenbank und den zugehörigen Benutzer einrichten
- Composer installieren
- Node.js/NPM installieren

### Pfarrplaner installieren

````
git clone https://codeberg.org/pfarrplaner/pfarrplaner.git
cd pfarrplaner
mkdir bootstrap/cache
chmod -R 777 bootstrap/cache
composer install
npm install
npm run prod
````

### Pfarrplaner konfigurieren

.env-Datei anlegen:
````
cp .env.example .env
````

Schlüssel für Session und Datenbank anlegen:

````
php artisan key:generate
php artisan key:database
````


Anschließend die Datei .env bearbeiten und alle wichtigen Felder 
(v.a. Datenbank-Zugangsdaten) ausfüllen.

### Datenbankstruktur anlegen
````
php artisan migrate
````

### Administratorbenutzer anlegen

````
php artisan install:admin
````

Jetzt sollte es möglich sein, sich als Administrator anzumelden. Die Zugangsdaten dazu werden als Ergebnis des letzten 
Schritts angezeigt.

### Cache vorbelegen
````
php artisan optimize
````

## Updates installieren
````
cd pfarrplaner
git pull
composer install
npm install
npm run prod
php artisan migrate
php artisan optimize
````


