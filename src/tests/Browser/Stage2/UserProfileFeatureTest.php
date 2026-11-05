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

use Laravel\Dusk\Browser;
use Tests\AbstractPageLoadTest;

class UserProfileFeatureTest extends AbstractPageLoadTest
{
    public function test_profile_editor_renders_without_error(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                ->visit(route('user.profile'))
                ->waitFor('#app', 10)
                ->assertDontSee('500')
                ->assertDontSee('Whoops');
        });
    }

    public function test_profile_editor_has_save_button(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                ->visit(route('user.profile'))
                ->waitFor('button.btn-primary', 10)
                ->assertPresent('button.btn-primary');
        });
    }

    public function test_profile_tabs_are_rendered(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                ->visit(route('user.profile'))
                ->waitFor('#app', 10)
                ->assertSee('Profil')
                ->assertSee('Sicherheit');
        });
    }

    public function test_token_management_page_renders_without_error(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                ->visit(route('tokens.index'))
                ->waitFor('#app', 10)
                ->assertDontSee('Oops! An Error Occurred')
                ->assertDontSee('Whoops');
        });
    }

    public function test_i_cal_connect_page_renders_without_error(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                ->visit(route('ical.connect'))
                ->waitFor('#app', 10)
                ->assertDontSee('500')
                ->assertDontSee('Whoops');
        });
    }
}
