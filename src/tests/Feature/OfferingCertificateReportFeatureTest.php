<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 * @version git: $
 */

namespace Tests\Feature;

use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verify the offering certificate report and its rendered content.
 */
class OfferingCertificateReportFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Authenticated users can discover the report setup page.
     */
    public function test_authenticated_user_can_open_the_certificate_setup(): void
    {
        $user = User::factory()->create();
        $city = City::factory()->create();
        $user->cities()->attach($city->id);
        Service::factory()->create([
            'city_id' => $city->id,
            'offering_amount' => '12.50',
        ]);

        $this->actingAs($user)->get(route('reports.setup', 'offeringCertificate'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Report/OfferingCertificate/Setup')
                ->has('services', 1));
    }

    /**
     * The certificate template omits the denomination table without counts.
     */
    public function test_certificate_without_denominations_contains_only_the_total(): void
    {
        $city = City::factory()->create(['name' => 'Musterstadt']);
        $service = Service::factory()->create([
            'city_id' => $city->id,
            'offering_amount' => '12.50',
            'offering_goal' => 'Brot für die Welt',
            'offering_denominations' => null,
            'offerings_counter1' => 'Anna Beispiel',
            'offerings_counter2' => 'Berta Beispiel',
        ]);

        $html = view('reports.offeringcertificate.render', compact('service'))->render();

        $this->assertStringContainsString('OPFERURKUNDE', $html);
        $this->assertStringContainsString('12,50 €', $html);
        $this->assertStringContainsString('Brot für die Welt', $html);
        $this->assertStringContainsString('Organist:in:', $html);
        $this->assertStringContainsString('Opferzweck', $html);
        $this->assertStringContainsString('Für die Richtigkeit!', $html);
        $this->assertStringContainsString('Musterstadt, ', $html);
        $this->assertStringContainsString('Anna Beispiel, Berta Beispiel', $html);
        $this->assertStringContainsString('<small class="machine-note">', $html);
        $this->assertStringNotContainsString('Name Organist:in:', $html);
        $this->assertStringNotContainsString('Einzelaufstellung des Opfers', $html);
        $this->assertStringContainsString('maschinell erstellt', $html);
        $this->assertStringContainsString('ohne Unterschrift gültig', $html);
        $this->assertStringNotContainsString('1. Unterschrift', $html);
        $this->assertStringNotContainsString('2. Unterschrift', $html);
    }

    /**
     * An empty offering goal is rendered as the own congregation.
     */
    public function test_certificate_uses_own_congregation_for_an_empty_goal(): void
    {
        $service = Service::factory()->create([
            'offering_amount' => '12.50',
            'offering_goal' => '',
        ]);

        $html = view('reports.offeringcertificate.render', compact('service'))->render();

        $this->assertStringContainsString('Opferzweck', $html);
        $this->assertStringContainsString('Eigene Gemeinde', $html);
    }

    /**
     * The certificate template includes entered denomination counts.
     */
    public function test_certificate_with_denominations_prints_the_counts(): void
    {
        $service = Service::factory()->create([
            'offering_amount' => '31.50',
            'offering_denominations' => ['2000' => 1, '500' => 2, '50' => 3],
        ]);

        $html = view('reports.offeringcertificate.render', compact('service'))->render();

        $this->assertStringContainsString('Einzelaufstellung des Opfers', $html);
        $this->assertStringContainsString('20 €-Scheine', $html);
        $this->assertStringContainsString('31,50 €', $html);
    }
}
