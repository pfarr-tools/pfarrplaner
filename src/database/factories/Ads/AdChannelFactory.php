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

namespace Database\Factories\Ads;

use App\Models\Ads\AdChannel;
use App\Models\Places\City;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AdChannelFactory extends Factory
{
    protected $model = AdChannel::class;

    public function definition(): array
    {
        return [
            'name' => $name = fake()->words(2, true),
            'slug' => Str::slug($name),
            'city_id' => City::factory(),
        ];
    }
}
