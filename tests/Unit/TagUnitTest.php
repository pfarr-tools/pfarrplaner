<?php
/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://codeberg.org/pfarr.tools/pfarrplaner
Is  * @version git: $Id$
 */

namespace Tests\Unit;

use App\Models\Tag;
use Tests\AbstractModelUnitTest;

class TagUnitTest extends AbstractModelUnitTest
{
    protected $modelClass = Tag::class;

    /** Override: Tag policy allows all users, no city sync needed */
    public function testUpdateViaApi()
    {
        $existing = $this->factory()->create();
        $this->assertTrue($this->testUser->can('update', $existing));

        $data = $this->factory()->raw();
        $apiRoute = 'api.' . ($this->modelClass)::singularKey() . '.update';
        $response = $this->actingAs($this->testUser, 'api')
            ->patchJson(route($apiRoute, $existing->id), $data);
        $response->assertStatus(200);
    }
}
