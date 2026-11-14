<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 * @version git: $
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add persisted denomination counts for optional offering certificates.
 */
return new class extends Migration
{
    /**
     * Add the optional denomination JSON column.
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            $table->json('offering_denominations')->nullable()->after('offering_amount');
        });
    }

    /**
     * Remove the optional denomination JSON column.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            $table->dropColumn('offering_denominations');
        });
    }
};
