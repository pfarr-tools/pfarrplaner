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

use App\Mail\User\AccountData;
use App\Models\People\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserMailUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_data_builds(): void
    {
        $user = User::factory()->create();
        $author = User::factory()->create();
        $mail = new AccountData($user, $author);
        $built = $mail->build();
        $this->assertInstanceOf(AccountData::class, $built);
        $this->assertEquals('Dein Pfarrplaner-Zugang ist eingerichtet', $built->subject);
    }

    /** Account credentials render as formatted HTML instead of Markdown source. */
    public function test_account_data_renders_passwordless_sign_in_instructions(): void
    {
        config(['app.url' => 'https://pfarrplaner.example']);
        $user = User::factory()->create([
            'first_name' => 'Mara',
            'email' => 'mara@example.org',
        ]);

        $html = (new AccountData($user, User::factory()->create()))->render();

        $this->assertMatchesRegularExpression('/<h1[^>]*>Dein Pfarrplaner-Zugang ist eingerichtet<\/h1>/', $html);
        $this->assertMatchesRegularExpression('/<strong[^>]*>E-Mail-Adresse<\/strong>/', $html);
        $this->assertStringContainsString('<code>mara@example.org</code>', $html);
        $this->assertStringNotContainsString('Passwort', $html);
        $this->assertStringContainsString('class="button"', $html);
        $this->assertStringNotContainsString('<pre', $html);
        $this->assertStringNotContainsString('**E-Mail-Adresse**', $html);
    }
}
