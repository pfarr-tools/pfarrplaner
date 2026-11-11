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
use App\Models\Service;
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
