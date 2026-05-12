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

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PixabayApiFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function testSearchRequiresAuth(): void
    {
        $this->withHeaders(['Accept' => 'application/json'])
            ->get(route('api.pixabay.query', ['query' => 'test']))
            ->assertStatus(401);
    }
}
