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
 *
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

namespace App\Seating;

class SeatingModels
{
    /**
     * Collect all available seating models indexed by title.
     *
     * @return array<string, AbstractSeatingModel> Seating models indexed by title.
     */
    public static function all(): array
    {
        $seatingModels = [];
        foreach (glob(app_path('Seating').'/*SeatingModel.php') ?: [] as $file) {
            if (substr(pathinfo($file, PATHINFO_FILENAME), 0, 8) !== 'Abstract') {
                $seatingModelClass = 'App\\Seating\\'.pathinfo($file, PATHINFO_FILENAME);
                if (class_exists($seatingModelClass)) {
                    /** @var AbstractSeatingModel $seatingModel */
                    $seatingModel = new $seatingModelClass;
                    $seatingModels[$seatingModel->getTitle()] = $seatingModel;
                }
            }
        }
        ksort($seatingModels);

        return $seatingModels;
    }

    /**
     * Build a list of available seating models for select fields.
     *
     * @return array<string, string> Titles indexed by seating model class name.
     */
    public static function select(): array
    {
        $seatingModels = [];
        foreach (self::all() as $title => $class) {
            $seatingModels[get_class($class)] = $title;
        }

        return $seatingModels;
    }

    /**
     * Find a seating model by its title.
     *
     * @param  string  $title  Title of the seating model.
     * @return AbstractSeatingModel|null Matching seating model, or null if none was found.
     */
    public static function byTitle(string $title): ?AbstractSeatingModel
    {
        /** @var AbstractSeatingModel $class */
        foreach (self::all() as $class) {
            if ($class->getTitle() == $title) {
                return $class;
            }
        }

        return null;
    }
}
