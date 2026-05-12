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
use Spatie\Permission\Models\Role;
use Tests\AbstractPageLoadTest;

class AdminRolesFeatureTest extends AbstractPageLoadTest
{
    public function testRolesIndexListsSeededRoles(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('roles.index'))
                    ->waitFor('#app', 10)
                    ->assertDontSee('500')
                    ->assertPresent('table, .list-group, .card');
        });
    }

    public function testRoleCreateFormRendersNameField(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('role.create'))
                    ->waitFor('[name="name"]', 10)
                    ->assertPresent('[name="name"]');
        });
    }

    public function testRoleEditorRendersNameField(): void
    {
        $role = Role::first();
        $this->browse(function (Browser $browser) use ($role) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('role.edit', $role->id))
                    ->waitFor('[name="name"]', 10)
                    ->assertPresent('[name="name"]');
        });
    }

    public function testRoleEditorNameCanBeEdited(): void
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
