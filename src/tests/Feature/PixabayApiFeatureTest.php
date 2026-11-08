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

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PixabayApiFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_requires_auth(): void
    {
        $this->withHeaders(['Accept' => 'application/json'])
            ->get(route('api.pixabay.query', ['query' => 'test']))
            ->assertStatus(401);
    }

    public function test_search_forwards_a_numeric_page_to_pixabay(): void
    {
        $this->withoutMiddleware();
        config(['pixabay.api_key' => 'test-key']);
        Http::fake([
            'https://pixabay.com/api/*' => Http::response([
                'totalHits' => 1,
                'hits' => [['id' => 42]],
            ]),
        ]);

        $this->getJson(route('api.pixabay.query.page', ['query' => 'moon', 'page' => 2]))
            ->assertOk()
            ->assertJsonPath('hits.0.id', 42);

        Http::assertSent(function (Request $request): bool {
            return rtrim((string) parse_url($request->url(), PHP_URL_PATH), '/') === '/api'
                && $request['key'] === 'test-key'
                && (int) $request['page'] === 2
                && $request['q'] === 'moon';
        });
    }

    public function test_search_reports_missing_configuration(): void
    {
        $this->withoutMiddleware();
        config(['pixabay.api_key' => null]);

        $this->getJson(route('api.pixabay.query', ['query' => 'moon']))
            ->assertStatus(503)
            ->assertJsonPath('message', 'Pixabay ist nicht konfiguriert. Bitte PIXABAY_API_KEY setzen.');
    }
}
