<?php

namespace App\Console\Commands;

use App\Models\People\User;
use App\Services\FirstRunInstallerService;
use App\Services\RoleConfigurationSynchronizer;
use App\Services\RoleService;
use Illuminate\Console\Command;

class InstallInitialAdmin extends Command
{
    protected $signature = 'pfarrplaner:initial-admin
        {--email= : E-Mail-Adresse des ersten Super-Admins}
        {--first-name= : Vorname des ersten Super-Admins}
        {--last-name= : Nachname des ersten Super-Admins}
        {--title= : Titel des ersten Super-Admins}
        {--password-stdin : Passwort aus STDIN lesen}
        {--must-change-password : Passwortwechsel beim ersten Login erzwingen}';

    protected $description = 'Erzeuge den ersten Super-Administrator idempotent';

    /**
     * @param  FirstRunInstallerService  $installer  First-run user installer.
     * @param  RoleConfigurationSynchronizer  $synchronizer  Role synchronization service.
     */
    public function __construct(
        protected FirstRunInstallerService $installer,
        protected RoleConfigurationSynchronizer $synchronizer,
    ) {
        parent::__construct();
    }

    /**
     * Creates or updates the initial super administrator.
     *
     * @return int Command exit status.
     */
    public function handle(): int
    {
        $email = trim((string) $this->option('email'));
        $firstName = trim((string) $this->option('first-name'));
        $lastName = trim((string) $this->option('last-name'));

        if ($email === '' || $firstName === '' || $lastName === '') {
            $this->components->error('E-Mail-Adresse, Vorname und Nachname sind erforderlich.');

            return self::FAILURE;
        }

        $existing = User::query()->where('email', $email)->first();
        if ($existing) {
            $this->synchronizer->synchronize();
            $existing->assignRole(RoleService::ROLE_SUPER_ADMIN);
            $this->components->info("Super-Administrator {$email} ist bereits vorhanden.");

            return self::SUCCESS;
        }

        if (! $this->option('password-stdin')) {
            $this->components->error('Das Passwort muss mit --password-stdin übergeben werden.');

            return self::FAILURE;
        }

        $password = rtrim((string) stream_get_contents(STDIN), "\r\n");
        if ($password === '') {
            $this->components->error('Das Passwort darf nicht leer sein.');

            return self::FAILURE;
        }

        $user = $this->installer->createOrUpdateAdminUser([
            'title' => (string) ($this->option('title') ?? ''),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password' => $password,
            'must_change_password' => (bool) $this->option('must-change-password'),
        ]);

        $this->components->info("Super-Administrator {$user->email} wurde angelegt.");

        return self::SUCCESS;
    }
}
