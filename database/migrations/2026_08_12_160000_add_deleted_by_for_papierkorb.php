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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (['services', 'absences', 'baptisms', 'funerals', 'weddings', 'replacements', 'ad_configs'] as $tableName) {
            if (!Schema::hasColumn($tableName, 'deleted_by')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('deleted_by')->nullable()->after('deleted_at')->constrained('users')->nullOnDelete();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['services', 'absences', 'baptisms', 'funerals', 'weddings', 'replacements', 'ad_configs'] as $tableName) {
            if (Schema::hasColumn($tableName, 'deleted_by')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['deleted_by']);
                    $table->dropColumn('deleted_by');
                });
            }
        }
    }
};
