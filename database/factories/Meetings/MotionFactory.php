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
 */

namespace Database\Factories\Meetings;

use App\Models\Meetings\Business;
use App\Models\Meetings\Motion;
use Illuminate\Database\Eloquent\Factories\Factory;

class MotionFactory extends Factory
{
    protected $model = Motion::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'title' => fake()->sentence(4, true),
            'body' => fake()->paragraph(),
        ];
    }
}
