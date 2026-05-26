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

namespace Tests\Browser\Stage2;

use App\Models\People\User;
use Laravel\Dusk\Browser;
use Tests\AbstractPageLoadTest;

class AuthFeatureTest extends AbstractPageLoadTest
{
    public function testValidLoginRedirectsToDashboard(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->waitFor('#app', 10)
                    ->type('[name="email"]', $this->superAdminUser->email)
                    ->type('[name="password"]', 'password')
                    ->press('Anmelden')
                    ->waitFor('#app', 10)
                    ->assertDontSee('500')
                    ->assertDontSee('Whoops');
        });
    }

    public function testInvalidLoginShowsValidationError(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->waitFor('#app', 10)
                    ->type('[name="email"]', 'noone@example.com')
                    ->type('[name="password"]', 'wrongpassword')
                    ->press('Anmelden')
                    ->waitUntilMissing('#nprogress .spinner', 10)
                    ->waitFor('.invalid-feedback', 10)
                    ->assertSeeIn('.invalid-feedback', 'Diese Kombination aus Zugangsdaten');
        });
    }

    public function testEmptyLoginShowsValidationErrors(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->waitFor('#app', 10)
                    ->press('Anmelden')
                    ->waitFor('#app', 10)
                    ->assertPresent('[name="email"]');
        });
    }

    public function testPasswordChangeFormRendersFields(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit('/passwort/aendern')
                    ->waitFor('#app', 10)
                    ->assertDontSee('500')
                    ->assertPresent('[name="new_password"]');
        });
    }
}
