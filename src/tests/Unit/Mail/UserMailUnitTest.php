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
        $mail = new AccountData($user, $author, 'secret123');
        $built = $mail->build();
        $this->assertInstanceOf(AccountData::class, $built);
        $this->assertEquals('Deine Zugangsdaten für den Pfarrplaner', $built->subject);
    }

    /** Account credentials render as formatted HTML instead of Markdown source. */
    public function test_account_data_renders_markdown_components_and_credentials(): void
    {
        config(['app.url' => 'https://pfarrplaner.example']);
        $user = User::factory()->create([
            'first_name' => 'Mara',
            'email' => 'mara@example.org',
        ]);

        $html = (new AccountData($user, User::factory()->create(), 'Start1234!'))->render();

        $this->assertMatchesRegularExpression('/<h1[^>]*>Deine Zugangsdaten für den Pfarrplaner<\/h1>/', $html);
        $this->assertMatchesRegularExpression('/<strong[^>]*>Benutzername<\/strong>/', $html);
        $this->assertStringContainsString('<code>mara@example.org</code>', $html);
        $this->assertStringContainsString('<code>Start1234!</code>', $html);
        $this->assertStringContainsString('class="button"', $html);
        $this->assertStringNotContainsString('<pre', $html);
        $this->assertStringNotContainsString('**Benutzername**', $html);
    }
}
