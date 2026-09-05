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

class PreventOrphans extends Migration
{

    protected function getColumnType($table, $column) {
        $columns = Schema::getColumns($table);

        $col = collect($columns)->firstWhere('name', $column);

        if (! $col) {
            throw new \InvalidArgumentException("Column {$column} not found on table {$table}");
        }

        return $col['type_name']
            . (!empty($col['unsigned']) ? 'Unsigned' : 'Signed');
    }

    public function createCascadingDelete($tableName, $foreignKey, $foreignTableName) {
        $existingConstraints = $this->listTableForeignKeys($tableName);
        $constraint = $tableName.'_'.$foreignKey.'_foreign';
        if (in_array($constraint, $existingConstraints)) {
            // drop existing foreign key constraint
            Schema::table($tableName, function(Blueprint $table) use ($constraint) {
                $table->dropForeign($constraint);
            });
        }
        $keyType = $this->getColumnType($foreignTableName, 'id');
        $localKeyType = $this->getColumnType($tableName, $foreignKey);
        if ($keyType != $localKeyType) {
            Schema::table($tableName, function (Blueprint $table) use ($keyType, $foreignKey) {
                if ($keyType == 'bigint') {
                    $table->unsignedBigInteger($foreignKey)->change();
                } elseif (substr($keyType,0,7) == 'integer') {
                    $table->unsignedInteger($foreignKey)->change();
                }
            });
        }
        Schema::table($tableName, function(Blueprint $table) use ($foreignKey, $foreignTableName, $keyType) {
            $table->foreign($foreignKey)->references('id')->on($foreignTableName)->onDelete('cascade');
        });
    }


    public function listTableForeignKeys($table)
    {
        return collect(Schema::getForeignKeys($table))
            ->pluck('name')
            ->all();
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('services', function(Blueprint $table){
            $table->unsignedInteger('location_id')->change();
        });
        Schema::table('locations', function(Blueprint $table){
            $table->increments('id')->change();
        });
        $this->createCascadingDelete('services', 'day_id', 'days');
        $this->createCascadingDelete('absences', 'user_id', 'users');
        $this->createCascadingDelete('baptisms', 'service_id', 'services');
        $this->createCascadingDelete('funerals', 'service_id', 'services');
        $this->createCascadingDelete('weddings', 'service_id', 'services');
        $this->createCascadingDelete('locations', 'city_id', 'cities');
        $this->createCascadingDelete('parishes', 'city_id', 'cities');
        $this->createCascadingDelete('replacements', 'absence_id', 'absences');
        $this->createCascadingDelete('seating_rows', 'seating_section_id', 'seating_sections');
        $this->createCascadingDelete('seating_sections', 'location_id', 'locations');
        $this->createCascadingDelete('street_ranges', 'parish_id', 'parishes');
        $this->createCascadingDelete('subscriptions', 'city_id', 'cities');
        $this->createCascadingDelete('subscriptions', 'user_id', 'users');
        $this->createCascadingDelete('user_settings', 'user_id', 'users');
        $this->createCascadingDelete('user_approver', 'user_id', 'users');
        $this->createCascadingDelete('user_approver', 'approver_id', 'users');
        $this->createCascadingDelete('service_service_group', 'service_group_id', 'service_groups');
        $this->createCascadingDelete('service_tag', 'service_id', 'services');
        $this->createCascadingDelete('service_tag', 'tag_id', 'tags');
        $this->createCascadingDelete('parish_user', 'user_id', 'users');
        $this->createCascadingDelete('parish_user', 'parish_id', 'parishes');
        $this->createCascadingDelete('city_day', 'city_id', 'cities');
        $this->createCascadingDelete('city_day', 'day_id', 'days');

        $this->createCascadingDelete('service_user', 'service_id', 'services');
        $this->createCascadingDelete('service_user', 'user_id', 'users');
        $this->createCascadingDelete('city_user', 'city_id', 'cities');
        $this->createCascadingDelete('city_user', 'user_id', 'users');
        $this->createCascadingDelete('user_home', 'city_id', 'cities');
        $this->createCascadingDelete('user_home', 'user_id', 'users');
        $this->createCascadingDelete('replacement_user', 'replacement_id', 'replacements');
        $this->createCascadingDelete('replacement_user', 'user_id', 'users');
        $this->createCascadingDelete('service_service_group', 'service_id', 'services');


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
