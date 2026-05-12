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

namespace Tests\Unit;

use App\Models\People\Team;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Tests\AbstractSimpleModelUnitTest;

class TeamUnitTest extends AbstractSimpleModelUnitTest
{
    protected string $modelClass = Team::class;
    protected bool $hasPolicy = true;
    protected bool $hasFactory = true;

    public function testTeamHasCityRelationship(): void
    {
        $team = Team::factory()->create();
        $this->assertInstanceOf(BelongsTo::class, $team->city());
    }

    public function testTeamHasUsersRelationship(): void
    {
        $team = Team::factory()->create();
        $this->assertInstanceOf(BelongsToMany::class, $team->users());
    }

    public function testTeamCanBeCreatedViaFactory(): void
    {
        $team = Team::factory()->create();
        $this->assertCount(1, Team::all());
        $this->assertNotEmpty($team->name);
    }
}
