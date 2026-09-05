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

use App\Mail\MinistryRequest;
use App\Mail\MinistryRequestFilled;
use App\Models\People\User;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MinistryMailUnitTest extends TestCase
{
    use RefreshDatabase;

    public function testMinistryRequestBuilds(): void
    {
        $user = User::factory()->create();
        $sender = User::factory()->create();
        $services = Service::factory()->count(1)->create();
        $mail = new MinistryRequest($user, $sender, 'P', $services, 'Test text');
        $built = $mail->build();
        $this->assertInstanceOf(MinistryRequest::class, $built);
        $this->assertStringContainsString('Anfrage', $built->subject);
    }

    public function testMinistryRequestFilledBuilds(): void
    {
        $user = User::factory()->create();
        $sender = User::factory()->create();
        $services = Service::factory()->count(1)->create();
        $mail = new MinistryRequestFilled($user, $sender, 'P', $services);
        $built = $mail->build();
        $this->assertInstanceOf(MinistryRequestFilled::class, $built);
        $this->assertStringContainsString('Zusage', $built->subject);
    }
}
