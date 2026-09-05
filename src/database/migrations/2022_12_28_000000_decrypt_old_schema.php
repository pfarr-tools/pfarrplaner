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

use App\Casts\EncryptedAttribute;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $pdo = DB::connection()->getPdo();

        $console = new \Symfony\Component\Console\Output\ConsoleOutput();

        // get the database encrypter
        $encrypter = EncryptedAttribute::getEncrypter();

        foreach ($this->getModels() as $model) {
            $ct = 1;
            $records = $model['class']::all();
            foreach ($records as $record) {
                $update = [];
                foreach ($model['encrypted'] as $property) {
                    $field = $property['field'];
                    $value = $record->$field ?? '';
                    if ($model['table'] == 'user_settings') {
                        if (is_string($value)) {
                            $value = $this->unquote($value);
                        }
                        if (!is_string($value)) $value = '_____' . serialize($value);
                    } else {
                        $value = $this->unquote($value);
                    }
                    $update[$field] = $property['cast']::encrypt($value);
                }
                DB::table($model['table'])->where('id', $record['id'])->update($update);
                $ct++;
            }

        }

    }

    private function unquote($value) {
        while (substr($value, 0, 1) == '\'') $value=substr($value, 1);
        while (substr($value, -1) == '\'') $value=substr($value, 0, -1);
        while (substr($value, 0, 2) == '\\'.'\'') $value = substr($value, 2);
        while (substr($value, -2) == '\\'.'\'') $value = substr($value, 0, -2);
        return $value;
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }



    private function getModels() {
        $models = [];
        foreach (glob(app_path('*.php')) as $file) {
            $class = 'App\\'.pathinfo($file, PATHINFO_FILENAME);
            $instance = new $class();
            $reflectionClass = new ReflectionClass($class);
            if ($reflectionClass->hasProperty('casts')) {

                $model = [
                    'class' => $class,
                    'classShort' => ucfirst(pathinfo($file, PATHINFO_FILENAME)),
                    'table' => $reflectionClass->getProperty('table')->getValue($instance) ?? Str::snake(Str::plural(pathinfo($file, PATHINFO_FILENAME))),
                ];

                $model['encrypted'] = [];
                foreach ($reflectionClass->getProperty('casts')->getValue($instance) as $field => $cast) {
                    if ($cast == EncryptedAttribute::class || $cast == \App\Casts\EncryptedSerializedAttribute::class) {
                        $model['encrypted'][] = ['field' => $field, 'cast' => $cast];
                    }
                }

                if (count($model['encrypted'])) $models[] = $model;
            }
        }
        return $models;
    }


};
