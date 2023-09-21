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

class AlterCalendarConnections extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('calendar_connections', function (Blueprint $table) {
            $table->dropColumn('credentials');
        });
        Schema::table('calendar_connections', function (Blueprint $table) {
            $table->dropColumn('connection_type');
            $table->text('credentials1')->nullable();
            $table->text('credentials2')->nullable();
            $table->text('connection_string')->nullable();
            $table->boolean('include_hidden')->default(0);
            $table->boolean('include_alternate')->default(0);
        });

        Schema::create('calendar_connection_city', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('calendar_connection_id');
            $table->unsignedInteger('city_id');
            $table->unsignedInteger('connection_type');

            $table->foreign('calendar_connection_id')->references('id')->on('calendar_connections')
                ->onDelete('cascade');
            $table->foreign('city_id')->references('id')->on('cities')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('calendar_connections', function (Blueprint $table) {
            $table->text('credentials');
            $table->unsignedInteger('connection_type');
        });
        Schema::table('calendar_connections', function (Blueprint $table) {
            $table->dropColumn('credentials1');
        });
        Schema::table('calendar_connections', function (Blueprint $table) {
            $table->dropColumn('credentials2');
        });
        Schema::table('calendar_connections', function (Blueprint $table) {
            $table->dropColumn('connection_string');
        });

        Schema::drop('calendar_connection_city');
    }
}
