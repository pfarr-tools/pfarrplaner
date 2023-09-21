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

class AddSermonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sermons', function(Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title')->nullable()->default('');
            $table->string('subtitle')->nullable()->default('');
            $table->string('reference')->nullable()->default('');
            $table->string('series')->nullable()->default('');
            $table->string('image')->nullable()->default('');
            $table->string('notes_header')->nullable()->default('');
            $table->string('audio_recording')->nullable()->default('');
            $table->string('video_url')->nullable()->default('');
            $table->string('external_url')->nullable()->default('');
            $table->text('summary')->nullable();
            $table->text('text')->nullable();
            $table->text('key_points')->nullable();
            $table->text('questions')->nullable();
            $table->text('literature')->nullable();
            $table->boolean('cc_license')->nullable()->default(false);
            $table->boolean('permit_handouts')->nullable()->default(true);
            $table->timestamps();
        });

        Schema::table('services', function (Blueprint $table) {
            $table->unsignedBigInteger('sermon_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('sermons');
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('sermon_id');
        });
    }
}
