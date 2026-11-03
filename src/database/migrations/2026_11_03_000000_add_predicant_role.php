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

use App\Services\RoleService;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Role::firstOrCreate(['name' => RoleService::ROLE_PREDICANT]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Role::query()
            ->where('name', RoleService::ROLE_PREDICANT)
            ->whereDoesntHave('users')
            ->delete();
    }
};
