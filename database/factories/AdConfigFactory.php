<?php

namespace Database\Factories;

use App\Models\Ads\AdConfig;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ads\AdConfig>
 */
class AdConfigFactory extends Factory
{
    protected $model = AdConfig::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'slug' => $this->faker->slug(),
            'offset' => $this->faker->numberBetween(1, 14),
            'ad_text' => $this->faker->sentence(),
        ];
    }
}
