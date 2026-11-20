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

namespace Tests\Feature;

use App\Models\People\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Verifies access control for the authenticated iCalendar connection page.
 */
class ICalConnectFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Guests are redirected to the login page before user cities are accessed.
     */
    public function test_connect_requires_an_authenticated_user(): void
    {
        $this->get(route('ical.connect'))->assertRedirect(route('login'));
    }

    /**
     * Authenticated users can load the iCalendar connection page.
     */
    public function test_authenticated_user_can_load_connect_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('ical.connect'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OutlookExport/Connect')
                ->has('calendarLinks')
                ->has('cities'));
    }
}
