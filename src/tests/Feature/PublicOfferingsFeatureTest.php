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

use App\Mail\OfferingCertificateMail;
use App\Models\OfferingSettlement;
use App\Models\Places\City;
use App\Models\Service;
use App\Services\OfferingSettlementFinalizer;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Mockery;
use Tests\TestCase;

/**
 * Verify the signed public offerings input.
 */
class PublicOfferingsFeatureTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    /**
     * A valid signed URL exposes today's services without requiring login.
     */
    public function test_guest_can_view_todays_services_with_a_valid_signature(): void
    {
        $city = City::factory()->create();
        $service = Service::factory()->create([
            'city_id' => $city->id,
            'date' => Carbon::now('Europe/Berlin')->setTime(10, 0),
            'offering_amount' => '',
        ]);

        $response = $this->get(URL::signedRoute('offerings.public', ['city' => $city]));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/City/Offerings')
                ->where('city.id', $city->id)
                ->where('services.0.id', $service->id)
                ->where('fallback', false));
    }

    /**
     * The public page receives the configured electronic-signature switch.
     */
    public function test_guest_sees_electronic_signature_configuration(): void
    {
        $city = City::factory()->create(['electronic_offering_signatures' => true]);

        $this->get(URL::signedRoute('offerings.public', ['city' => $city]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('electronicSignatures', true));
    }

    /**
     * An invalid signature cannot access the public page.
     */
    public function test_guest_cannot_view_the_page_without_a_valid_signature(): void
    {
        $city = City::factory()->create();

        $this->get(route('offerings.public', ['city' => $city]))
            ->assertForbidden();
    }

    /**
     * A nearby service is offered when the current day has no service.
     */
    public function test_nearby_services_are_offered_when_today_is_empty(): void
    {
        $city = City::factory()->create();
        $service = Service::factory()->create([
            'city_id' => $city->id,
            'date' => Carbon::now('Europe/Berlin')->addDays(3)->setTime(10, 0),
            'offering_amount' => '',
        ]);

        $response = $this->get(URL::signedRoute('offerings.public', ['city' => $city]));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('services.0.id', $service->id)
                ->where('fallback', true));
    }

    /**
     * Nearby dates are sent separately when today's services are available.
     */
    public function test_guest_can_access_nearby_services_when_today_has_services(): void
    {
        $city = City::factory()->create();
        $today = Carbon::now('Europe/Berlin')->startOfDay();
        $todayService = Service::factory()->create([
            'city_id' => $city->id,
            'date' => $today->copy()->setTime(10, 0),
            'offering_amount' => '',
        ]);
        $nearbyService = Service::factory()->create([
            'city_id' => $city->id,
            'date' => $today->copy()->addDays(3)->setTime(10, 0),
            'offering_amount' => '',
        ]);
        Service::factory()->create([
            'city_id' => $city->id,
            'date' => $today->copy()->addDays(8)->setTime(10, 0),
            'offering_amount' => '',
        ]);

        $response = $this->get(URL::signedRoute('offerings.public', ['city' => $city]));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('services.0.id', $todayService->id)
                ->where('nearbyServices.0.id', $nearbyService->id)
                ->has('nearbyServices', 1)
                ->where('fallback', false));
    }

    /**
     * The signed city boundary prevents updating another city's service.
     */
    public function test_guest_cannot_save_a_service_from_another_city(): void
    {
        $city = City::factory()->create();
        $otherCity = City::factory()->create();
        $service = Service::factory()->create(['city_id' => $otherCity->id]);

        $this->post(URL::signedRoute('offerings.save', ['city' => $city]), [
            'service_id' => $service->id,
            'offering_amount' => '12,00',
        ])->assertNotFound();
    }

    /**
     * Denomination counts are summed by the server and saved with counters.
     */
    public function test_guest_can_save_denominations_and_counter_names(): void
    {
        $city = City::factory()->create();
        $service = Service::factory()->create([
            'city_id' => $city->id,
            'date' => Carbon::now('Europe/Berlin')->setTime(10, 0),
            'offering_amount' => '',
        ]);

        $this->post(URL::signedRoute('offerings.save', ['city' => $city]), [
            'service_id' => $service->id,
            'offering_denominations' => [
                '2000' => 1,
                '500' => 2,
                '50' => 3,
            ],
            'offering_amount' => '999,99',
            'offerings_counter1' => 'Anna',
            'offerings_counter2' => 'Berta',
        ])->assertRedirect()->assertSessionHas('success', 'Die Opferangaben wurden gespeichert.');

        $service->refresh();

        $this->assertSame('31.50', $service->offering_amount);
        $this->assertSame(['2000' => 1, '500' => 2, '50' => 3], $service->offering_denominations);
        $this->assertSame('Anna', $service->offerings_counter1);
        $this->assertSame('Berta', $service->offerings_counter2);
    }

    /**
     * A configured signed form rejects malformed signature images.
     */
    public function test_configured_electronic_signatures_reject_invalid_images(): void
    {
        $city = City::factory()->create(['electronic_offering_signatures' => true]);
        $service = Service::factory()->create([
            'city_id' => $city->id,
            'date' => Carbon::now('Europe/Berlin')->setTime(10, 0),
            'offering_amount' => '',
            'offerings_counter1' => 'Anna',
            'offerings_counter2' => 'Bernd',
        ]);

        $this->post(URL::signedRoute('offerings.save', ['city' => $city]), [
            'service_id' => $service->id,
            'offering_amount' => '12,50',
            'offerings_counter1' => 'Anna',
            'offerings_counter2' => 'Bernd',
            'signatures' => [
                ['name' => 'Anna', 'image' => 'not-a-png'],
                ['name' => 'Bernd', 'image' => 'not-a-png'],
            ],
        ])->assertUnprocessable();

        $this->assertSame('', $service->refresh()->offering_amount);
    }

    /**
     * A valid configured submission is handed to the atomic finalizer.
     */
    public function test_configured_electronic_signatures_are_finalized(): void
    {
        $city = City::factory()->create(['electronic_offering_signatures' => true]);
        $service = Service::factory()->create([
            'city_id' => $city->id,
            'date' => Carbon::now('Europe/Berlin')->setTime(10, 0),
            'offering_amount' => '',
        ]);
        $finalizer = Mockery::mock(OfferingSettlementFinalizer::class);
        $finalizer->expects('finalize')
            ->once()
            ->withArgs(static function (Service $actualService, array $signatures) use ($service): bool {
                return $actualService->is($service)
                    && $signatures[0]['name'] === 'Anna'
                    && $signatures[1]['name'] === 'Bernd';
            })
            ->andReturn(OfferingSettlement::make(['status' => 'finalized']));
        $this->app->instance(OfferingSettlementFinalizer::class, $finalizer);

        $this->post(URL::signedRoute('offerings.save', ['city' => $city]), [
            'service_id' => $service->id,
            'offering_amount' => '12,50',
            'offerings_counter1' => 'Anna',
            'offerings_counter2' => 'Bernd',
            'signatures' => [
                ['name' => 'Anna', 'image' => 'data:image/png;base64,'.self::PNG],
                ['name' => 'Bernd', 'image' => 'data:image/png;base64,'.self::PNG],
            ],
        ])->assertRedirect();
    }

    /**
     * Invalid plain amounts are rejected without changing the service.
     */
    public function test_guest_cannot_save_an_invalid_amount(): void
    {
        $city = City::factory()->create();
        $service = Service::factory()->create([
            'city_id' => $city->id,
            'date' => Carbon::now('Europe/Berlin')->setTime(10, 0),
            'offering_amount' => '',
        ]);

        $this->post(URL::signedRoute('offerings.save', ['city' => $city]), [
            'service_id' => $service->id,
            'offering_amount' => 'kein Betrag',
        ])->assertUnprocessable();

        $this->assertSame('', $service->refresh()->offering_amount);
    }

    /**
     * A saved service cannot be changed through the public form again.
     */
    public function test_guest_cannot_change_a_saved_offering(): void
    {
        $city = City::factory()->create();
        $service = Service::factory()->create([
            'city_id' => $city->id,
            'date' => Carbon::now('Europe/Berlin')->setTime(10, 0),
            'offering_amount' => '4.50',
        ]);

        $this->get(URL::signedRoute('offerings.public', ['city' => $city]))
            ->assertInertia(fn ($page) => $page->has('services', 0));

        $this->post(URL::signedRoute('offerings.save', ['city' => $city]), [
            'service_id' => $service->id,
            'offering_amount' => '12,00',
        ])->assertStatus(409);

        $this->assertSame('4.50', $service->refresh()->offering_amount);
    }

    /**
     * A configured city recipient receives a queued certificate after saving.
     */
    public function test_saved_offering_queues_a_certificate_for_the_configured_recipient(): void
    {
        Mail::fake();
        config([
            'mail.from.address' => 'configured@example.test',
            'mail.from.name' => 'Configured Sender',
        ]);
        $city = City::factory()->create(['offering_certificate_email' => 'opfer@example.test']);
        $service = Service::factory()->create([
            'city_id' => $city->id,
            'date' => Carbon::now('Europe/Berlin')->setTime(10, 0),
            'offering_amount' => '',
        ]);

        $this->post(URL::signedRoute('offerings.save', ['city' => $city]), [
            'service_id' => $service->id,
            'offering_amount' => '12,50',
        ])->assertRedirect();

        Mail::assertQueued(OfferingCertificateMail::class, function (OfferingCertificateMail $mail): bool {
            return $mail->from[0]['address'] === 'configured@example.test'
                && $mail->from[0]['name'] === 'Configured Sender';
        });
    }
}
