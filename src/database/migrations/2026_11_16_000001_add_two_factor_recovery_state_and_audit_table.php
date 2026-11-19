<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Add the reenrolment marker and durable two-factor recovery audit. */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('two_factor_recovery_required_at')->nullable();
        });

        Schema::create('two_factor_recovery_audits', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('actor_user_id');
            $table->unsignedBigInteger('target_user_id');
            $table->string('support_reference', 100);
            $table->string('ip_address', 45);
            $table->timestamps();
            $table->index(['target_user_id', 'created_at']);
            $table->index(['actor_user_id', 'created_at']);
        });
    }

    /** Refuse to destroy recovery state or audit history during rollback.
     *
     * Restore the verified database backup and deploy the previous image instead.
     *
     * @throws LogicException Always; this migration is intentionally forward-only.
     */
    public function down(): void
    {
        throw new LogicException(
            'Die Zwei-Faktor-Wiederherstellungsmigration ist vorwärtsgerichtet; verwenden Sie den dokumentierten Datenbank-Restore.'
        );
    }
};
