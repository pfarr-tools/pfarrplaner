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

use App\Models\Rites\Baptism;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class BaptismFactory extends Factory
{
    protected $model = Baptism::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'candidate_name' => fake()->name(),
            'candidate_address' => fake()->streetAddress(),
            'candidate_zip' => fake()->postcode(),
            'candidate_city' => fake()->city(),
            'candidate_email' => fake()->safeEmail(),
            'candidate_phone' => fake()->phoneNumber(),
            'first_contact_with' => fake()->name(),
            'registered' => 0,
            'appointment' => fake()->dateTimeBetween('+1 week', '+3 months'),
            'signed' => 0,
            'docs_ready' => 0,
            'docs_where' => '',
        ];
    }


}
