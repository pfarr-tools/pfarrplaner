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

use App\Models\People\Team;
use Laravel\Dusk\Browser;
use Tests\AbstractPageLoadTest;

class AdminTeamsFeatureTest extends AbstractPageLoadTest
{
    protected Team $team;

    protected function setUp(): void
    {
        parent::setUp();
        $this->team = Team::factory()->create(['name' => 'Testgruppe']);
    }

    public function testTeamsIndexListsTeams(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('teams.index'))
                    ->waitFor('#app', 10)
                    ->assertDontSee('500')
                    ->assertSee('Testgruppe');
        });
    }

    public function testTeamEditorHasNameField(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('team.edit', $this->team->id))
                    ->waitFor('[name="name"]', 10)
                    ->assertInputValue('[name="name"]', 'Testgruppe');
        });
    }

    public function testTeamNameCanBeEdited(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('team.edit', $this->team->id))
                    ->waitFor('[name="name"]', 10)
                    ->clear('[name="name"]')
                    ->type('[name="name"]', 'Geänderte Gruppe')
                    ->assertInputValue('[name="name"]', 'Geänderte Gruppe');
        });
    }

    public function testTeamEditorHasSaveButton(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(route('team.edit', $this->team->id))
                    ->waitFor('#app', 10)
                    ->assertPresent('button.btn-primary');
        });
    }
}
