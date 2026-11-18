<?php

namespace Tests\Browser\Stage2;

use Laravel\Dusk\Browser;
use Tests\AbstractPageLoadTest;

/** Browser coverage for the passwordless sign-in page. */
class AuthFeatureTest extends AbstractPageLoadTest
{
    public function test_login_page_offers_passkey_and_email_link_without_password_input(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/login')
                ->waitFor('#app', 10)
                ->assertPresent('[name="email"]')
                ->assertSee('Mit Passkey anmelden')
                ->assertSee('Anmelde link per E-Mail erhalten')
                ->assertMissing('[name="password"]')
                ->assertDontSee('Passwort vergessen');
        });
    }
}
