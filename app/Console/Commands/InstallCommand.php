<?php
/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://codeberg.org/pfarr.tools/pfarrplaner
 * @version git: $Id$
 */

namespace App\Console\Commands;

use App\Services\FirstRunInstallerService;
use Database\Seeders\RoleSeeder;
use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Str;

class InstallCommand extends Command
{
    protected $signature = 'pfarrplaner:install
        {--force : Vorhandene .env-Datei ohne Rueckfrage ersetzen}
        {--env-file=.env : Ziel-Datei fuer die Umgebungsvariablen}
        {--show-env : Generierte .env nach dem Schreiben ausgeben}
        {--app-name= : Anwendungsname}
        {--app-url= : Vollstaendige URL der Instanz}
        {--app-env= : APP_ENV, zum Beispiel production oder local}
        {--app-debug= : true oder false}
        {--db-connection= : sqlite, mysql, pgsql oder sqlsrv}
        {--db-host= : Datenbank-Host}
        {--db-port= : Datenbank-Port}
        {--db-database= : Datenbankname oder sqlite-Datei}
        {--db-username= : Datenbank-Benutzer}
        {--db-password= : Datenbank-Passwort}
        {--db-socket= : Datenbank-Socket fuer MySQL}
        {--admin-title= : Titel des ersten Super-Admins}
        {--admin-first-name= : Vorname des ersten Super-Admins}
        {--admin-last-name= : Nachname des ersten Super-Admins}
        {--admin-email= : E-Mail-Adresse des ersten Super-Admins}
        {--admin-password= : Passwort des ersten Super-Admins}
        {--must-change-password : Passwortwechsel beim ersten Login erzwingen}
        {--mail-from-address= : Absenderadresse fuer Systemmails}
        {--mail-from-name= : Anzeigename fuer Systemmails}';

    protected $description = 'Installiere Pfarrplaner fuer die erste Inbetriebnahme';

    public function __construct(protected FirstRunInstallerService $installer)
    {
        parent::__construct();
    }

    /**
     * @return int
     */
    public function handle(): int
    {
        $templatePath = base_path('.env.example');
        $envPath = $this->resolveEnvironmentPath((string)$this->option('env-file'));

        if (!file_exists($templatePath)) {
            $this->components->error('Die Vorlage .env.example wurde nicht gefunden.');
            return Command::FAILURE;
        }

        if (file_exists($envPath) && !$this->option('force')) {
            if (!$this->confirm('.env ist bereits vorhanden. Soll sie ueberschrieben werden?', false)) {
                $this->components->warn('Installation abgebrochen.');
                return Command::INVALID;
            }
        }

        $template = file_get_contents($templatePath);
        $defaults = $this->extractDefaults($template);
        $values = $this->collectEnvironmentValues($defaults);
        $adminData = $this->collectAdminData($values['APP_ADMINISTRATOR']);
        $environment = $this->installer->buildEnvironmentFile($template, $values);

        $this->line('');
        $this->line('Schreibe Konfiguration nach ' . $envPath);

        if (!$this->installer->writeEnvironmentFile($envPath, $environment)) {
            $this->components->error('Die .env-Datei konnte nicht geschrieben werden.');
            $this->line('Bitte folgenden Inhalt manuell nach ' . $envPath . ' kopieren und den Befehl danach erneut ausfuehren:');
            $this->newLine();
            $this->line($environment);
            return Command::FAILURE;
        }

        if ($this->option('show-env')) {
            $this->newLine();
            $this->line($environment);
        }

        $this->installer->applyRuntimeConfiguration($values);

        if ($values['DB_CONNECTION'] === 'sqlite') {
            $this->installer->ensureSqliteDatabaseExists($values['DB_DATABASE']);
        }

        $this->components->info('Migrationen werden jetzt mit der konfigurierten Datenbank ausgefuehrt.');
        if ($this->call('migrate', ['--force' => true]) !== Command::SUCCESS) {
            return Command::FAILURE;
        }

        if ($this->call('db:seed', ['--class' => RoleSeeder::class, '--force' => true]) !== Command::SUCCESS) {
            return Command::FAILURE;
        }

        $user = $this->installer->createOrUpdateAdminUser($adminData);

        $this->newLine();
        $this->components->info('Installation abgeschlossen.');
        $this->line('Admin-Benutzer: ' . $user->email);
        $this->line('Rolle: Super-Administrator:in');
        $this->line('Konfiguration: ' . $envPath);

        return Command::SUCCESS;
    }

    /**
     * @param string $administratorEmail
     * @return array
     */
    protected function collectAdminData(string $administratorEmail): array
    {
        $firstName = $this->askRequiredOption('admin-first-name', 'Vorname des ersten Super-Admins');
        $lastName = $this->askRequiredOption('admin-last-name', 'Nachname des ersten Super-Admins');
        $email = $administratorEmail;
        $title = $this->askOption('admin-title', 'Titel des ersten Super-Admins', '');
        $password = $this->askSecretOption('admin-password', 'Passwort fuer den ersten Super-Admin');
        $mustChangePassword = $this->option('must-change-password')
            || $this->confirm('Soll beim ersten Login ein Passwortwechsel erzwungen werden?', true);

        return [
            'title' => $title,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password' => $password,
            'must_change_password' => $mustChangePassword,
        ];
    }

    /**
     * @param array $defaults
     * @return array
     */
    protected function collectEnvironmentValues(array $defaults): array
    {
        $appName = $this->askRequiredOption('app-name', 'Anwendungsname', $defaults['APP_NAME'] ?? 'Pfarrplaner');
        $appEnv = $this->askChoiceOption('app-env', 'Anwendungsumgebung', ['production', 'local'], $defaults['APP_ENV'] ?? 'production');
        $appUrl = $this->askRequiredOption('app-url', 'Vollstaendige URL der Instanz', $defaults['APP_URL'] ?? 'http://localhost');
        $appDebug = $this->askBooleanOption('app-debug', 'Debug-Modus aktivieren?', $appEnv !== 'production');
        $dbConnection = $this->askChoiceOption('db-connection', 'Datenbanktyp', ['mysql', 'sqlite', 'pgsql', 'sqlsrv'], $defaults['DB_CONNECTION'] ?? 'mysql');
        $databaseValues = $this->collectDatabaseValues($dbConnection, $defaults);
        $mailFromAddress = $this->askRequiredOption('mail-from-address', 'Absenderadresse fuer Systemmails', $defaults['MAIL_FROM_ADDRESS'] ?? $this->defaultMailFromAddress($appUrl));
        $mailFromName = $this->askRequiredOption('mail-from-name', 'Anzeigename fuer Systemmails', $defaults['MAIL_FROM_NAME'] ?? $appName);
        $administratorEmail = $this->askRequiredOption('admin-email', 'E-Mail-Adresse fuer APP_ADMINISTRATOR', $defaults['APP_ADMINISTRATOR'] ?? $mailFromAddress);

        return array_merge([
            'APP_NAME' => $appName,
            'APP_ENV' => $appEnv,
            'APP_KEY' => $this->generateKey(config('app.cipher')),
            'APP_DEBUG' => $appDebug,
            'APP_URL' => $appUrl,
            'TOKEN_SALT' => Str::random(64),
            'APP_ADMINISTRATOR' => $administratorEmail,
            'MAIL_FROM_ADDRESS' => $mailFromAddress,
            'MAIL_FROM_NAME' => $mailFromName,
            'DATABASE_KEY' => $this->generateKey(config('database.cipher')),
        ], $databaseValues);
    }

    /**
     * @param string $connection
     * @param array $defaults
     * @return array
     */
    protected function collectDatabaseValues(string $connection, array $defaults): array
    {
        if ($connection === 'sqlite') {
            return [
                'DB_CONNECTION' => 'sqlite',
                'DB_DATABASE' => $this->askRequiredOption('db-database', 'Pfad zur SQLite-Datei', $defaults['DB_DATABASE'] ?? database_path('database.sqlite')),
            ];
        }

        $defaultPort = match ($connection) {
            'pgsql' => $defaults['DB_PORT'] ?? '5432',
            'sqlsrv' => $defaults['DB_PORT'] ?? '1433',
            default => $defaults['DB_PORT'] ?? '3306',
        };

        return [
            'DB_CONNECTION' => $connection,
            'DB_HOST' => $this->askRequiredOption('db-host', 'Datenbank-Host', $defaults['DB_HOST'] ?? '127.0.0.1'),
            'DB_PORT' => $this->askRequiredOption('db-port', 'Datenbank-Port', $defaultPort),
            'DB_DATABASE' => $this->askRequiredOption('db-database', 'Datenbankname', $defaults['DB_DATABASE'] ?? 'pfarrplaner'),
            'DB_USERNAME' => $this->askRequiredOption('db-username', 'Datenbank-Benutzer', $defaults['DB_USERNAME'] ?? 'pfarrplaner'),
            'DB_PASSWORD' => $this->askOption('db-password', 'Datenbank-Passwort', $defaults['DB_PASSWORD'] ?? ''),
            'DB_SOCKET' => $connection === 'mysql'
                ? $this->askOption('db-socket', 'MySQL-Socket (optional)', $defaults['DB_SOCKET'] ?? '')
                : '',
        ];
    }

    /**
     * @param string $cipher
     * @return string
     */
    protected function generateKey(string $cipher): string
    {
        return 'base64:' . base64_encode(Encrypter::generateKey($cipher));
    }

    /**
     * @param string $option
     * @param string $question
     * @param string $default
     * @return string
     */
    protected function askOption(string $option, string $question, string $default = ''): string
    {
        $value = $this->option($option);

        if (is_string($value) && $value !== '') {
            return $value;
        }

        if (!$this->input->isInteractive()) {
            return $default;
        }

        return (string)$this->ask($question, $default);
    }

    /**
     * @param string $option
     * @param string $question
     * @param string $default
     * @return string
     */
    protected function askRequiredOption(string $option, string $question, string $default = ''): string
    {
        do {
            $value = trim($this->askOption($option, $question, $default));

            if ($value !== '') {
                return $value;
            }

            $this->components->warn('Dieser Wert wird benoetigt.');
            $default = '';
        } while ($this->input->isInteractive());

        throw new \RuntimeException('Fehlende Pflichtangabe fuer --' . $option . '.');
    }

    /**
     * @param string $option
     * @param string $question
     * @return string
     */
    protected function askSecretOption(string $option, string $question): string
    {
        $value = $this->option($option);

        if (is_string($value) && $value !== '') {
            return $value;
        }

        if (!$this->input->isInteractive()) {
            throw new \RuntimeException('Fehlende Pflichtangabe fuer --' . $option . '.');
        }

        do {
            $first = (string)$this->secret($question);
            $second = (string)$this->secret('Passwort wiederholen');

            if (($first !== '') && ($first === $second)) {
                return $first;
            }

            $this->components->warn('Die Passwoerter waren leer oder stimmen nicht ueberein.');
        } while (true);
    }

    /**
     * @param string $option
     * @param string $question
     * @param array $choices
     * @param string $default
     * @return string
     */
    protected function askChoiceOption(string $option, string $question, array $choices, string $default): string
    {
        $value = $this->option($option);

        if (is_string($value) && in_array($value, $choices, true)) {
            return $value;
        }

        if (!$this->input->isInteractive()) {
            return $default;
        }

        return (string)$this->choice($question, $choices, $default);
    }

    /**
     * @param string $option
     * @param string $question
     * @param bool $default
     * @return bool
     */
    protected function askBooleanOption(string $option, string $question, bool $default): bool
    {
        $value = $this->option($option);

        if (is_string($value) && $value !== '') {
            return filter_var($value, FILTER_VALIDATE_BOOL);
        }

        if (!$this->input->isInteractive()) {
            return $default;
        }

        return $this->confirm($question, $default);
    }

    /**
     * @param string $appUrl
     * @return string
     */
    protected function defaultMailFromAddress(string $appUrl): string
    {
        $host = parse_url($appUrl, PHP_URL_HOST);

        if (!$host) {
            return 'noreply@example.com';
        }

        return 'noreply@' . preg_replace('/^www\./', '', $host);
    }

    /**
     * @param string $path
     * @return string
     */
    protected function resolveEnvironmentPath(string $path): string
    {
        if (Str::startsWith($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return base_path($path);
    }

    /**
     * @param string $template
     * @return array
     */
    protected function extractDefaults(string $template): array
    {
        $defaults = [];

        foreach (preg_split('/\r\n|\r|\n/', $template) as $line) {
            if ((!$line) || Str::startsWith(trim($line), '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $defaults[$key] = trim($value, " \t\n\r\0\x0B\"");
        }

        return $defaults;
    }
}
