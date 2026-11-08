<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 */

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Verify that the framework-provided password-reset routes render usable forms.
 */
class PasswordResetFeatureTest extends TestCase
{
    /**
     * The password-reset request form is available to guests.
     */
    public function test_password_reset_request_form_is_available(): void
    {
        $this->get('/password/reset')
            ->assertOk()
            ->assertSee('Passwort zurücksetzen');
    }

    /**
     * The password-reset form accepts the token supplied by the broker.
     */
    public function test_password_reset_form_is_available_for_a_token(): void
    {
        $this->get('/password/reset/example-token')
            ->assertOk()
            ->assertSee('Neues Passwort');
    }
}
