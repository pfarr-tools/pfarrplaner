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

use Laravel\Dusk\Browser;
use Tests\AbstractPageLoadTest;

class UserProfileFeatureTest extends AbstractPageLoadTest
{
    public function testProfileEditorRendersWithoutError(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('user.profile'))
                    ->waitFor('#app', 10)
                    ->assertDontSee('500')
                    ->assertDontSee('Whoops');
        });
    }

    public function testProfileEditorHasSaveButton(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('user.profile'))
                    ->waitFor('button.btn-primary', 10)
                    ->assertPresent('button.btn-primary');
        });
    }

    public function testProfileTabsAreRendered(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('user.profile'))
                    ->waitFor('#app', 10)
                    ->assertSee('Profil')
                    ->assertSee('Sicherheit');
        });
    }

    public function testApiTokenPageRendersWithoutError(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('apitoken'))
                    ->waitFor('#app', 10)
                    ->assertDontSee('500')
                    ->assertDontSee('Whoops');
        });
    }

    public function testICalConnectPageRendersWithoutError(): void
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
