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

use App\Mail\User\AccountData;
use App\Models\People\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserMailUnitTest extends TestCase
{
    use RefreshDatabase;

    public function testAccountDataBuilds(): void
    {
        $user = User::factory()->create();
        $author = User::factory()->create();
        $mail = new AccountData($user, $author, 'secret123');
        $built = $mail->build();
        $this->assertInstanceOf(AccountData::class, $built);
        $this->assertEquals('Deine Zugangsdaten für den Pfarrplaner', $built->subject);
    }
}
