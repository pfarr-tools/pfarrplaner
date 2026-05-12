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

namespace Database\Factories\Rites;

use App\Models\Rites\Funeral;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class FuneralFactory extends Factory
{
    protected $model = Funeral::class;

    public function definition()
    {
        return [
            'service_id' => Service::factory(),
            'buried_name' => fake()->name,
            'buried_address' => fake()->streetAddress,
            'buried_zip' => fake()->postcode,
            'buried_city' => fake()->city,
            'text' => fake()->sentence(3),
            'announcement' => fake()->date('d.m.Y'),
            'type' => 'Erdbestattung',
            'wake' => null,
            'wake_location' => null,
            'relative_name' => fake()->name,
            'relative_address' => fake()->streetAddress,
            'relative_zip' => fake()->postcode,
            'relative_city' => fake()->city,
            'relative_contact_data' => fake()->sentence,
            'appointment' => fake()->date('d.m.Y H:i'),
            'dob' => fake()->date('d.m.Y'),
            'dod' => fake()->date('d.m.Y'),
            //
        ];
    }


}
