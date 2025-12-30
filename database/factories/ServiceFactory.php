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

namespace Database\Factories;

use App\Models\Location;
use App\Models\Places\City;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory {

    protected $model = Service::class;
    public function definition()
    {
        $church = fake()->name();
        $church .= (substr($church, -1) == 's' ? '' : 's') . 'kirche';
        return [
            'date' => fake()->dateTime(),
            'time' => fake()->time('H:i'),
            'description' => fake()->sentence(),
            'need_predicant' => fake()->numberBetween(0, 1),
            'baptism' => fake()->numberBetween(0, 1),
            'eucharist' => fake()->numberBetween(0, 1),
            'offerings_counter1' => fake()->name(),
            'offerings_counter2' => fake()->name(),
            'offering_goal' => fake()->sentence(),
            'offering_description' => fake()->sentence(),
            'offering_type' => fake()->randomElement(['PO', 'eO', '']),
            'cc' => fake()->numberBetween(0, 1),
            'cc_alt_time' => fake()->time('H:i'),
            'cc_location' => $church,
            'cc_lesson' => fake()->sentence(),
            'cc_staff' => fake()->firstName() . ', ' . fake()->firstName(),
            'internal_remarks' => fake()->sentence(),
            'offering_amount' => fake()->randomFloat(2),
            'meeting_url' => fake()->url,
            'location_id' => Location::factory(),
        ];
    }


}
