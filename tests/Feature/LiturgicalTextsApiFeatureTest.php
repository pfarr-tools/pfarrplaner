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

use App\Models\Liturgy\Text;
use App\Models\People\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class LiturgicalTextsApiFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    public function testListReturnsAllTexts()
    {
        $user = User::factory()->create();
        Text::factory()->count(2)->create();

        $response = $this->actingAs($user, 'api')
            ->getJson(route('api.liturgy.text.list'));

        $response->assertOk();
        $this->assertCount(2, $response->json());
    }

    /**
     * @return void
     */
    public function testListRequiresAuth()
    {
        $response = $this->getJson(route('api.liturgy.text.list'));
        $response->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function testImportFromWordReturnsEmptyStringWhenNoFileProvided()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->postJson(route('api.liturgy.text.import'));

        $response->assertOk();
        $this->assertSame('', $response->json());
    }

    /**
     * @return void
     */
    public function testImportRequiresAuth()
    {
        $response = $this->postJson(route('api.liturgy.text.import'));
        $response->assertUnauthorized();
    }
}
