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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill first_name/last_name from name where both are empty
        DB::table('users')
            ->whereRaw("(first_name IS NULL OR first_name = '') AND (last_name IS NULL OR last_name = '')")
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->orderBy('id')
            ->each(function ($user) {
                /** @var string $name */
                $name = $user->name;
                $parts = explode(' ', trim($name), 2);
                if (count($parts) === 1) {
                    DB::table('users')->where('id', $user->id)->update(['last_name' => $parts[0]]);
                } else {
                    DB::table('users')->where('id', $user->id)->update([
                        'first_name' => $parts[0],
                        'last_name' => $parts[1],
                    ]);
                }
            });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
        });

        // Restore name from first_name + last_name
        DB::table('users')->orderBy('id')->each(function ($user) {
            /** @var string $firstName */
            $firstName = $user->first_name ?? '';
            /** @var string $lastName */
            $lastName = $user->last_name ?? '';
            $name = trim($firstName.' '.$lastName);
            DB::table('users')->where('id', $user->id)->update(['name' => $name]);
        });
    }
};
