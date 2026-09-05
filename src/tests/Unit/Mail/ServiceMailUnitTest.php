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

namespace Tests\Unit\Mail;

use App\Mail\ServiceCreated;
use App\Mail\ServiceCreatedMultiple;
use App\Mail\ServiceUpdated;
use App\Models\People\User;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceMailUnitTest extends TestCase
{
    use RefreshDatabase;

    public function testServiceCreatedBuilds(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create();
        $originating = User::factory()->create();
        $mail = new ServiceCreated($user, $service, $originating, []);
        $built = $mail->build();
        $this->assertInstanceOf(ServiceCreated::class, $built);
        $this->assertStringContainsString('Neuer Gottesdienst', $built->subject);
    }

    public function testServiceUpdatedBuilds(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create();
        $originating = User::factory()->create();
        $mail = new ServiceUpdated($user, $service, $originating, []);
        $built = $mail->build();
        $this->assertInstanceOf(ServiceUpdated::class, $built);
        $this->assertStringContainsString('Änderungen', $built->subject);
    }

    public function testServiceCreatedMultipleBuilds(): void
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
