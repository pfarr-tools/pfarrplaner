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
                    ->type('[name="password"]', 'secret')
                    ->press('Anmelden')
                    ->waitForLocation('/')
                    ->assertRouteIs('home')
                    ->assertDontSee('500')
                    ->assertDontSee('Whoops')
                    ->visit('/logout');
        });
    }

    public function testAutofilledCredentialsAreSubmitted(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->waitFor('#app', 10);

            $browser->script([
                "document.querySelector('[name=\\\"email\\\"]').value = '" . $this->superAdminUser->email . "';",
                "document.querySelector('[name=\\\"password\\\"]').value = 'secret';",
            ]);

            $browser
                    ->assertInputValue('[name="email"]', $this->superAdminUser->email)
                    ->assertInputValue('[name="password"]', 'secret')
                    ->press('Anmelden')
                    ->waitForLocation('/')
                    ->assertRouteIs('home')
                    ->visit('/logout');
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
                    ->assertSeeIn('.invalid-feedback', 'Diese Kombination aus Zugangsdaten')
                    ->assertInputValue('[name="email"]', 'noone@example.com');
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
