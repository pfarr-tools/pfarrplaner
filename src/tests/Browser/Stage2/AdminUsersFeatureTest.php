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
use Tests\Browser\Pages\UserEditorPage;

class AdminUsersFeatureTest extends AbstractPageLoadTest
{
    protected User $targetUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->targetUser = User::factory()->create();
    }

    public function test_users_index_lists_users(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                ->visit(route('users.index'))
                ->waitFor('#app', 10)
                ->assertDontSee('500')
                ->assertSee($this->targetUser->last_name);
        });
    }

    public function test_user_editor_renders_first_name_field(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                ->visit(new UserEditorPage($this->targetUser->id))
                ->waitFor('[name="first_name"]', 10)
                ->assertPresent('[name="first_name"]');
        });
    }

    public function test_user_editor_first_name_can_be_edited(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                ->visit(new UserEditorPage($this->targetUser->id))
                ->waitFor('[name="first_name"]', 10)
                ->clear('[name="first_name"]')
                ->type('[name="first_name"]', 'Geänderter')
                ->assertInputValue('[name="first_name"]', 'Geänderter');
        });
    }

    public function test_user_editor_email_field_present(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                ->visit(new UserEditorPage($this->targetUser->id))
                ->waitFor('[name="email"]', 10)
                ->assertPresent('[name="email"]');
        });
    }

    public function test_user_create_form_renders_without_error(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                ->visit(route('user.create'))
                ->waitFor('#app', 10)
                ->assertDontSee('500')
                ->assertDontSee('Whoops');
        });
    }

    /** The editor shows and refreshes the documented initial-password pattern. */
    public function test_user_create_form_suggests_and_refreshes_an_initial_password(): void
    {
        $password = null;

        $this->browse(function (Browser $browser) use (&$password) {
            $browser->loginAs($this->superAdminUser, 'web')
                ->visit(route('user.create'))
                ->click('a[href="#account"]')
                ->waitFor('#account input[type="checkbox"]', 10)
                ->click('input[type="checkbox"]')
                ->waitFor('[name="password"]', 10)
                ->assertPresent('[name="password"]')
                ->assertScript("/^[A-Z][aeiouy][bcdfghjklmnpqrstvwxz][aeiouy][0-9]{4}$/.test(document.querySelector('[name=\\\"password\\\"]').value)", true)
                ->click('[data-testid="initial-password-suggest"]')
                ->assertScript("/^[A-Z][aeiouy][bcdfghjklmnpqrstvwxz][aeiouy][0-9]{4}$/.test(document.querySelector('[name=\\\"password\\\"]').value)", true)
                ->tap(function (Browser $browser) use (&$password) {
                    $password = $browser->value('[name="password"]');
                })
                ->click('a[href="#home"]')
                ->clear('[name="first_name"]')
                ->type('[name="first_name"]', 'Browser')
                ->clear('[name="last_name"]')
                ->type('[name="last_name"]', 'Browserkonto')
                ->clear('[name="email"]')
                ->type('[name="email"]', 'browserkonto@example.com')
                ->click('a[title="Änderungen speichern"]')
                ->pause(1000)
                ->assertScript('return !window.location.search.includes("password")', true)
                ->assertDontSee($password);
        });
    }

    public function test_duplicates_wizard_renders_without_error(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                ->visit(route('users.duplicates'))
                ->waitFor('#app', 10)
                ->assertDontSee('500')
                ->assertDontSee('Whoops');
        });
    }
}
