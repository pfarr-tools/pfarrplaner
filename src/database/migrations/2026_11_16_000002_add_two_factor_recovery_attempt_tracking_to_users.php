<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Store recovery password throttling on the shared, locked actor row. */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedTinyInteger('two_factor_recovery_attempts')->default(0);
            $table->timestamp('two_factor_recovery_attempted_at')->nullable();
        });
    }

    /** Refuse to discard active recovery throttling state during rollback.
     *
     * Restore the verified database backup and deploy the previous image instead.
     *
     * @throws LogicException Always; this migration is intentionally forward-only.
     */
    public function down(): void
    {
        throw new LogicException(
            'Die Wiederherstellungsbegrenzung ist vorwärtsgerichtet; verwenden Sie den dokumentierten Datenbank-Restore.'
        );
    }
};
