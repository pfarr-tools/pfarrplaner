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

namespace Database\Factories\Leave;

use App\Models\Leave\Pool;
use App\Models\Leave\Poolmaster;
use App\Models\People\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PoolmasterFactory extends Factory
{
    protected $model = Poolmaster::class;

    public function definition(): array
    {
        return [
            'pool_id' => Pool::factory(),
            'user_id' => User::factory(),
            'start' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'end' => fake()->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
        ];
    }
}
