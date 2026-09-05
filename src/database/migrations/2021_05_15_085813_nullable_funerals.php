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
 *
 * Sponsored by: Evangelischer Kirchenbezirk Balingen, https://www.kirchenbezirk-balingen.de
 *
 * Pfarrplaner is based on the Laravel framework (https://laravel.com).
 * This file may contain code created by Laravel's scaffolding functions.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class NullableFunerals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('funerals', function (Blueprint $table) {
            $table->text('buried_address')->nullable()->change();
            $table->text('buried_zip')->nullable()->change();
            $table->text('buried_city')->nullable()->change();
            $table->string('text')->nullable()->change();
            $table->string('type')->nullable()->change();
            $table->text('relative_name')->nullable()->change();
            $table->text('relative_address')->nullable()->change();
            $table->text('relative_zip')->nullable()->change();
            $table->text('relative_city')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('funerals', function (Blueprint $table) {
            $table->text('buried_address')->nullable(false)->change();
            $table->text('buried_zip')->nullable(false)->change();
            $table->text('buried_city')->nullable(false)->change();
            $table->string('text')->nullable(false)->change();
            $table->string('type')->nullable(false)->change();
            $table->text('relative_name')->nullable(false)->change();
            $table->text('relative_address')->nullable(false)->change();
            $table->text('relative_zip')->nullable(false)->change();
            $table->text('relative_city')->nullable(false)->change();
        });
    }
}
