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

namespace Tests\Unit\Mail;

use App\Mail\ServiceCreated;
use App\Mail\ServiceCreatedMultiple;
use App\Mail\ServiceUpdated;
use App\Mail\TestMail;
use App\Models\People\User;
use App\Models\Places\City;
use App\Models\Service;
use App\Services\ServiceChangeFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceMailUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_created_builds(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create();
        $originating = User::factory()->create();
        $mail = new ServiceCreated($user, $service, $originating, []);
        $built = $mail->build();
        $this->assertInstanceOf(ServiceCreated::class, $built);
        $this->assertStringContainsString('Neuer Gottesdienst', $built->subject);
    }

    /**
     * Verifies that custom service mails use the shared table-based shell.
     */
    public function test_service_mail_renders_with_shared_mail_shell(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create();
        $originating = User::factory()->create();
        $mail = new ServiceCreated($user, $service, $originating, []);

        $html = $mail->render();

        $this->assertStringContainsString('background-color: #a61380', $html);
        $this->assertStringContainsString('role="presentation"', $html);
        $this->assertStringContainsString('Pfarrplaner', $html);
    }

    /**
     * Verifies that the test mail contains the current developer contact data.
     */
    public function test_test_mail_renders_current_contact_data(): void
    {
        $html = (new TestMail)->render();

        $this->assertStringContainsString('Buchenstr. 29', $html);
        $this->assertStringContainsString('71126 Gäufelden', $html);
        $this->assertStringContainsString('07032 75567', $html);
    }

    public function test_service_updated_builds(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create();
        $originating = User::factory()->create();
        $mail = new ServiceUpdated($user, $service, $originating, []);
        $built = $mail->build();
        $this->assertInstanceOf(ServiceUpdated::class, $built);
        $this->assertStringContainsString('Änderungen', $built->subject);
    }

    /**
     * Includes changed service attributes without adding them to a mail template list.
     */
    public function test_service_update_mail_includes_future_service_attributes(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create();
        $service->changed = [
            'registration_max' => ['original' => null, 'changed' => 20],
        ];

        $user = User::factory()->create();
        $originating = User::factory()->create();
        $html = (new ServiceUpdated($user, $service, $originating, []))->render();

        $this->assertStringContainsString('Maximale Anzahl der Anmeldungen', $html);
        $this->assertStringContainsString('>20<', $html);
    }

    /**
     * Resolves model labels for nested changed paths.
     */
    public function test_service_update_mail_labels_nested_changes(): void
    {
        $service = Service::factory()->create();

        $changes = ServiceChangeFormatter::format($service, [
            'offering_denominations.coins.2000' => ['original' => 1, 'changed' => 2],
        ]);

        $this->assertSame('Opferstückelung › Münzen › 2000', $changes[0]['label']);
        $this->assertSame('1', $changes[0]['original']);
        $this->assertSame('2', $changes[0]['changed']);
    }

    /**
     * Resolves both previous and current congregation IDs to names.
     */
    public function test_service_update_mail_formats_congregation_changes(): void
    {
        $service = Service::factory()->create();
        $previousCity = City::factory()->create(['name' => 'Alte Gemeinde']);
        $currentCity = City::factory()->create(['name' => 'Neue Gemeinde']);

        $changes = ServiceChangeFormatter::format($service, [
            'city_id' => ['original' => $previousCity->id, 'changed' => $currentCity->id],
        ]);

        $this->assertSame('Alte Gemeinde', $changes[0]['original']);
        $this->assertSame('Neue Gemeinde', $changes[0]['changed']);
    }

    /**
     * Returns nested service changes as stable leaf paths.
     */
    public function test_service_diff_flattens_nested_attribute_changes(): void
    {
        $service = Service::factory()->create([
            'offering_denominations' => ['coins' => ['2000' => 1]],
        ]);
        $service->trackChanges();
        $service->offering_denominations = ['coins' => ['2000' => 2]];
        $service->save();
        $service->storeDiff();

        $this->assertSame(
            ['original' => 1, 'changed' => 2],
            $service->changed['offering_denominations.coins.2000'],
        );
    }

    /**
     * Ignores associative JSON key order when checking service changes.
     */
    public function test_service_diff_is_stable_when_nested_keys_are_reordered(): void
    {
        $service = Service::factory()->create([
            'offering_denominations' => ['coins' => ['2000' => 1, '1000' => 2]],
        ]);
        $service->trackChanges();
        $service->offering_denominations = ['coins' => ['1000' => 2, '2000' => 1]];
        $service->save();

        $this->assertFalse($service->isChanged());
    }

    public function test_service_created_multiple_builds(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create();
        $originating = User::factory()->create();
        $services = Service::factory()->count(2)->create();
        $mail = new ServiceCreatedMultiple($user, $service, $originating, ['services' => $services]);
        $built = $mail->build();
        $this->assertInstanceOf(ServiceCreatedMultiple::class, $built);
        $this->assertEquals('Mehrere neue Gottesdienste angelegt', $built->subject);
    }
}
