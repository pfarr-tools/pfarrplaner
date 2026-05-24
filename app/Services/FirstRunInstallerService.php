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

namespace App\Services;

use App\Models\People\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class FirstRunInstallerService
{
    /**
     * @param string $template
     * @param array $values
     * @return string
     */
    public function buildEnvironmentFile(string $template, array $values): string
    {
        foreach ($values as $key => $value) {
            $template = $this->replaceEnvironmentValue($template, $key, $value);
        }

        return rtrim($template) . PHP_EOL;
    }

    /**
     * @param string $path
     * @param string $contents
     * @return bool
     */
    public function writeEnvironmentFile(string $path, string $contents): bool
    {
        return false !== @file_put_contents($path, $contents);
    }

    /**
     * @param string $path
     * @return void
     */
    public function ensureSqliteDatabaseExists(string $path): void
    {
        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        if (!file_exists($path)) {
            touch($path);
        }
    }

    /**
     * @param array $values
     * @return void
     */
    public function applyRuntimeConfiguration(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->setEnvironmentValue($key, $value);
        }

        config([
            'app.name' => $values['APP_NAME'],
            'app.env' => $values['APP_ENV'],
            'app.debug' => $this->toBoolean($values['APP_DEBUG']),
            'app.url' => $values['APP_URL'],
            'app.key' => $values['APP_KEY'],
            'app.administrator' => $values['APP_ADMINISTRATOR'],
            'database.default' => $values['DB_CONNECTION'],
            'database.key' => $values['DATABASE_KEY'],
            'mail.from.address' => $values['MAIL_FROM_ADDRESS'],
            'mail.from.name' => $values['MAIL_FROM_NAME'],
        ]);

        switch ($values['DB_CONNECTION']) {
            case 'sqlite':
                config([
                    'database.connections.sqlite.database' => $values['DB_DATABASE'],
                ]);
                break;
            case 'pgsql':
                config([
                    'database.connections.pgsql.host' => $values['DB_HOST'],
                    'database.connections.pgsql.port' => $values['DB_PORT'],
                    'database.connections.pgsql.database' => $values['DB_DATABASE'],
                    'database.connections.pgsql.username' => $values['DB_USERNAME'],
                    'database.connections.pgsql.password' => $values['DB_PASSWORD'],
                ]);
                break;
            case 'sqlsrv':
                config([
                    'database.connections.sqlsrv.host' => $values['DB_HOST'],
                    'database.connections.sqlsrv.port' => $values['DB_PORT'],
                    'database.connections.sqlsrv.database' => $values['DB_DATABASE'],
                    'database.connections.sqlsrv.username' => $values['DB_USERNAME'],
                    'database.connections.sqlsrv.password' => $values['DB_PASSWORD'],
                ]);
                break;
            case 'mysql':
            default:
                config([
                    'database.connections.mysql.host' => $values['DB_HOST'],
                    'database.connections.mysql.port' => $values['DB_PORT'],
                    'database.connections.mysql.database' => $values['DB_DATABASE'],
                    'database.connections.mysql.username' => $values['DB_USERNAME'],
                    'database.connections.mysql.password' => $values['DB_PASSWORD'],
                    'database.connections.mysql.unix_socket' => $values['DB_SOCKET'],
                ]);
                break;
        }

        DB::purge();
    }

    /**
     * @param array $data
     * @return User
     */
    public function createOrUpdateAdminUser(array $data): User
    {
        $this->ensureRoleExists(RoleService::ROLE_SUPER_ADMIN);

        /** @var User $user */
        $user = User::query()->firstOrNew(['email' => $data['email']]);
        $user->fill([
            'title' => $data['title'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'must_change_password' => $data['must_change_password'],
        ]);
        $user->email_verified_at = now();
        $user->remember_token = Str::random(10);
        $user->save();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->syncRoles([RoleService::ROLE_SUPER_ADMIN]);

        return $user;
    }

    /**
     * @param string $name
     * @return void
     */
    protected function ensureRoleExists(string $name): void
    {
        Role::query()->firstOrCreate([
            'name' => $name,
            'guard_name' => 'web',
        ]);
    }

    /**
     * @param string $template
     * @param string $key
     * @param mixed $value
     * @return string
     */
    protected function replaceEnvironmentValue(string $template, string $key, mixed $value): string
    {
        $line = $key . '=' . $this->formatEnvironmentValue($value);
        $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';

        if (preg_match($pattern, $template)) {
            return preg_replace($pattern, $line, $template, 1);
        }

        return rtrim($template) . PHP_EOL . $line . PHP_EOL;
    }

    /**
     * @param mixed $value
     * @return string
     */
    protected function formatEnvironmentValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $value = (string)$value;

        if ($value === '') {
            return '';
        }

        if (preg_match('/[\s#"\']/', $value)) {
            return '"' . addcslashes($value, "\\\"") . '"';
        }

        return $value;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function setEnvironmentValue(string $key, mixed $value): void
    {
        $stringValue = is_bool($value) ? ($value ? 'true' : 'false') : (string)$value;

        putenv($key . '=' . $stringValue);
        $_ENV[$key] = $stringValue;
        $_SERVER[$key] = $stringValue;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    protected function toBoolean(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOL);
    }
}
