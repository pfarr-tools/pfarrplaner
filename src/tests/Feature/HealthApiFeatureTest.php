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

use Tests\TestCase;

class HealthApiFeatureTest extends TestCase
{
    /**
     * @return void
     */
    public function testHealthReturnsOkStatus()
    {
        $response = $this->getJson(route('api.health'));

        $response->assertOk();
        $response->assertJsonFragment(['status' => 'ok']);
    }

    /**
     * @return void
     */
    public function testHealthReturnsDatabaseStatus()
    {
        $response = $this->getJson(route('api.health'));

        $response->assertOk();
        $response->assertJsonStructure(['status', 'app', 'db']);
        $this->assertSame('connected', $response->json('db'));
    }
}
