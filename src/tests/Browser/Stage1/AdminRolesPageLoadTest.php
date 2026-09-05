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

namespace Tests\Browser\Stage1;

use Laravel\Dusk\Browser;
use Spatie\Permission\Models\Role;
use Tests\AbstractPageLoadTest;

class AdminRolesPageLoadTest extends AbstractPageLoadTest
{
    public function testRolesIndexLoads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('roles.index'));
        });
    }

    public function testRoleEditorLoads(): void
    {
        $role = Role::first();
        $this->browse(function (Browser $browser) use ($role) {
            $this->assertPageLoads($browser, route('role.edit', $role->id));
        });
    }

    public function testRoleCreateLoads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('role.create'));
        });
    }
}
