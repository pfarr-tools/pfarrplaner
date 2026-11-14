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
 * Add the optional automatic certificate recipient to cities.
 */
return new class extends Migration
{
    /**
     * Add the optional recipient email column.
     */
    public function up(): void
    {
        Schema::table('cities', function (Blueprint $table): void {
            $table->string('offering_certificate_email')->nullable()->after('default_offering_url');
        });
    }

    /**
     * Remove the optional recipient email column.
     */
    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table): void {
            $table->dropColumn('offering_certificate_email');
        });
    }
};
