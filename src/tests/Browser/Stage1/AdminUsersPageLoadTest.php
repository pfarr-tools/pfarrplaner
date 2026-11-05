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

namespace Tests\Browser\Stage1;

use App\Models\People\User;
use Laravel\Dusk\Browser;
use Tests\AbstractPageLoadTest;

class AdminUsersPageLoadTest extends AbstractPageLoadTest
{
    protected User $targetUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->targetUser = User::factory()->create();
    }

    public function test_users_index_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('users.index'));
        });
    }

    public function test_user_editor_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('user.edit', $this->targetUser->id));
        });
    }

    public function test_user_join_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('user.join', $this->targetUser->id));
        });
    }

    public function test_duplicates_wizard_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('users.duplicates'));
        });
    }

    public function test_user_create_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('user.create'));
        });
    }
}
