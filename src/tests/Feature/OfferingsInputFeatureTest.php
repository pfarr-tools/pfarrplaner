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
 */

namespace Tests\Feature;

use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Verify the server-side data flow for the offerings input.
 */
class OfferingsInputFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The input page receives the reduced service data directly.
     */
    public function test_input_page_receives_reduced_service_data(): void
    {
        Permission::firstOrCreate(['name' => 'gd-opfer-bearbeiten']);
        $user = User::factory()->create();
        $user->givePermissionTo('gd-opfer-bearbeiten');
        $city = City::factory()->create();
        $service = Service::factory()->create([
            'city_id' => $city->id,
            'date' => '2026-06-21 10:00:00',
            'offering_type' => 'eO',
        ]);
        $otherCity = City::factory()->create();
        $relatedOnlyService = Service::factory()->create([
            'city_id' => $otherCity->id,
            'date' => '2026-06-21 11:00:00',
        ]);
        $relatedOnlyService->relatedCities()->attach($city->id);

        $response = $this->actingAs($user)->post(route('inputs.input', 'offerings'), [
            'from' => '01.01.2026',
            'to' => '31.12.2026',
            'cities' => [$city->id],
            'locations' => [],
        ]);

        $response->assertInertia(fn ($page) => $page
            ->component('Inputs/Offerings/Form')
            ->has('services', 1)
            ->where('services.0.id', $service->id)
            ->where('services.0.offering_type', 'eO')
            ->missing('serviceSlugs'));
    }

    /**
     * Reloading the collected input page returns to its setup page.
     */
    public function test_getting_the_collection_url_returns_to_setup(): void
    {
        Permission::firstOrCreate(['name' => 'gd-opfer-bearbeiten']);
        $user = User::factory()->create();
        $user->givePermissionTo('gd-opfer-bearbeiten');

        $response = $this->actingAs($user)->get(route('inputs.input.reload', 'offerings'));

        $response->assertRedirect(route('inputs.setup', 'offerings'));
    }
}
