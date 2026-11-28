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
use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Service;
use App\Services\OfferingSettlementFinalizer;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Tests\TestCase;

/**
 * Verify atomic finalization and integrity metadata for signed offerings.
 */
class OfferingSettlementFinalizationFeatureTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    /**
     * Finalization writes private images, a PDF, a manifest and is idempotent.
     */
    public function test_finalization_is_integrity_bound_and_idempotent(): void
    {
        Storage::fake();
        $city = City::factory()->create();
        $service = Service::factory()->create([
            'city_id' => $city->id,
            'offering_amount' => '12.50',
            'offerings_counter1' => 'Anna',
            'offerings_counter2' => 'Bernd',
        ]);
        $finalizer = new OfferingSettlementFinalizer(
            app('App\\Services\\OfferingSignatureService'),
            static fn (Service $service, OfferingSettlement $settlement): string => 'archived-pdf',
        );

        $settlement = $finalizer->finalize($service, [
            ['name' => 'Anna', 'image' => self::PNG],
            ['name' => 'Bernd', 'image' => self::PNG],
        ]);

        $repeat = $finalizer->finalize($service->refresh(), [
            ['name' => 'Anna', 'image' => self::PNG],
            ['name' => 'Bernd', 'image' => self::PNG],
        ]);

        $this->assertSame($settlement->id, $repeat->id);
        $this->assertSame('finalized', $settlement->status);
        $this->assertNotEmpty($settlement->manifest_hash);
        $this->assertTrue($finalizer->verify($settlement));
        $this->assertCount(1, OfferingSettlement::query()->get());
        Storage::assertExists($settlement->pdf_path);
        Storage::assertExists($settlement->signatures[0]['path']);
    }

    /**
     * Finalized offering fields cannot be changed through the normal model API.
     */
    public function test_finalized_offering_fields_are_protected(): void
    {
        Storage::fake();
        $city = City::factory()->create();
        $service = Service::factory()->create([
            'city_id' => $city->id,
            'offering_amount' => '12.50',
            'offerings_counter1' => 'Anna',
            'offerings_counter2' => 'Bernd',
        ]);
        $finalizer = new OfferingSettlementFinalizer(
            app('App\\Services\\OfferingSignatureService'),
            static fn (Service $service, OfferingSettlement $settlement): string => 'archived-pdf',
        );
        $finalizer->finalize($service, [
            ['name' => 'Anna', 'image' => self::PNG],
            ['name' => 'Bernd', 'image' => self::PNG],
        ]);

        $this->expectException(LogicException::class);
        $service->offering_amount = '99.99';
        $service->save();
    }

    /**
     * Authorized corrections create an audit row and clear the editable fields.
     */
    public function test_authorized_revision_reopens_the_offering(): void
    {
        Storage::fake();
        $city = City::factory()->create();
        $service = Service::factory()->create([
            'city_id' => $city->id,
            'offering_amount' => '12.50',
            'offerings_counter1' => 'Anna',
            'offerings_counter2' => '',
        ]);
        $finalizer = new OfferingSettlementFinalizer(
            app('App\\Services\\OfferingSignatureService'),
            static fn (Service $service, OfferingSettlement $settlement): string => 'archived-pdf',
        );
        $settlement = $finalizer->finalize($service, [['name' => 'Anna', 'image' => self::PNG]]);
        $user = User::factory()->create();
        $user->assignRole(RoleService::ROLE_SUPER_ADMIN);

        $this->actingAs($user)
            ->post(route('offerings.settlement.revision', ['service' => $service]), [
                'reason' => 'Zählername war falsch erfasst.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('offering_settlement_corrections', [
            'service_id' => $service->id,
            'previous_settlement_id' => $settlement->id,
            'user_id' => $user->id,
            'reason' => 'Zählername war falsch erfasst.',
        ]);
        $this->assertSame('', $service->refresh()->offering_amount);
        $this->assertNull($service->offering_denominations);
    }

    /**
     * Certificate mail uses the immutable archived PDF when one exists.
     */
    public function test_certificate_mail_attaches_the_archived_pdf(): void
    {
        Storage::fake();
        $city = City::factory()->create();
        $service = Service::factory()->create([
            'city_id' => $city->id,
            'offering_amount' => '12.50',
            'offerings_counter1' => 'Anna',
            'offerings_counter2' => 'Bernd',
        ]);
        $finalizer = new OfferingSettlementFinalizer(
            app('App\\Services\\OfferingSignatureService'),
            static fn (Service $service, OfferingSettlement $settlement): string => 'archived-pdf',
        );
        $finalizer->finalize($service, [
            ['name' => 'Anna', 'image' => self::PNG],
            ['name' => 'Bernd', 'image' => self::PNG],
        ]);

        $mail = (new OfferingCertificateMail($service->refresh()))->build();

        $this->assertSame('archived-pdf', $mail->rawAttachments[0]['data']);
    }
}
