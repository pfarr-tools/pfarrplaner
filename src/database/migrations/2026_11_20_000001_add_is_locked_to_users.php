<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 * @version git: $Id$
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Add a separate account lock flag for administrator-controlled access. */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_locked')->default(false)->after('is_official_user');
        });
    }

    /** Remove the account lock flag when rolling back this migration. */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('is_locked');
        });
    }
};
