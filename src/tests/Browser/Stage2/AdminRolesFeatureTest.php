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
use Spatie\Permission\Models\Role;
use Tests\AbstractPageLoadTest;

class AdminRolesFeatureTest extends AbstractPageLoadTest
{
    public function test_roles_index_lists_seeded_roles(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                ->visit(route('roles.index'))
                ->waitFor('#app', 10)
                ->assertDontSee('500')
                ->assertPresent('table, .list-group, .card');
        });
    }

    public function test_role_create_form_renders_name_field(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                ->visit(route('role.create'))
                ->waitFor('[name="name"]', 10)
                ->assertPresent('[name="name"]');
        });
    }

    public function test_role_editor_renders_name_field(): void
    {
        $role = Role::first();
        $this->browse(function (Browser $browser) use ($role) {
            $browser->loginAs($this->superAdminUser, 'web')
                ->visit(route('role.edit', $role->id))
                ->waitFor('[name="name"]', 10)
                ->assertPresent('[name="name"]');
        });
    }

    public function test_role_editor_name_can_be_edited(): void
    {
        $role = Role::first();
        $this->browse(function (Browser $browser) use ($role) {
            $browser->loginAs($this->superAdminUser, 'web')
                ->visit(route('role.edit', $role->id))
                ->waitFor('[name="name"]', 10)
                ->assertInputValueIsNot('[name="name"]', '');
        });
    }
}
