<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RoundlyConsulting\TwoFactor\Support\TwoFactorColumns;

return new class extends Migration
{
    /**
     * Add the nullable two-factor state columns to the application's users table.
     */
    public function up(): void
    {
        // This application authenticates exclusively against `users`; do not let
        // an environment-specific TWO_FACTOR_TABLE redirect this migration.
        //
        // Only missing columns are added, so a repeated forward migration is safe.
        Schema::table('users', function (Blueprint $table): void {
            TwoFactorColumns::addMissing($table);
        });
    }

    /**
     * Refuse an automatic rollback because it would destroy enrolled factors.
     *
     * Restore the verified database backup and deploy the previous image instead.
     *
     * @throws LogicException Always; this migration is intentionally forward-only.
     */
    public function down(): void
    {
        throw new LogicException(
            'Die Zwei-Faktor-Migration ist vorwärtsgerichtet; verwenden Sie den dokumentierten Datenbank-Restore.'
        );
    }
};
