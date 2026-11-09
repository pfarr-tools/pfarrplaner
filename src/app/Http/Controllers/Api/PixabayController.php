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
 *
 *
 * Pfarrplaner is based on the Laravel framework (https://laravel.com).
 * This file may contain code created by Laravel's scaffolding functions.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Class PixabayController
 */
class PixabayController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Search Pixabay images for the uploader.
     *
     * @param  string  $query  Search phrase.
     * @param  string|int  $page  Requested result page.
     */
    public function query(string $query, string|int $page = 1): JsonResponse
    {
        $page = max(1, (int) $page);
        $apiKey = config('pixabay.api_key');

        abort_if(! is_string($apiKey) || trim($apiKey) === '', 503, 'Pixabay ist nicht konfiguriert. Bitte PIXABAY_API_KEY setzen.');

        $response = Http::timeout(20)->get('https://pixabay.com/api/', [
            'key' => $apiKey,
            'image_type' => 'photo',
            'page' => $page,
            'q' => $query,
        ]);

        if ($response->status() === 401 || $response->status() === 403) {
            abort(503, 'Der Pixabay-API-Key ist ungültig oder abgelaufen.');
        }

        if ($response->status() === 429) {
            abort(503, 'Das Pixabay-Limit ist momentan erreicht. Bitte später erneut versuchen.');
        }

        abort_unless($response->successful(), 502, 'Pixabay ist derzeit nicht erreichbar.');

        return response()->json($response->json());
    }

    /** Proxy an approved Pixabay image URL for local cropper processing. */
    public function image(Request $request)
    {
        $url = (string) $request->validate(['url' => 'required|url'])['url'];
        $host = parse_url($url, PHP_URL_HOST);
        abort_unless(in_array($host, ['pixabay.com', 'cdn.pixabay.com', 'pixabayusercontent.com'], true), 422, 'Ungültige Pixabay-Bildadresse.');

        $response = Http::timeout(20)->get($url);
        abort_unless($response->successful(), 502, 'Das Pixabay-Bild konnte nicht geladen werden.');

        return response($response->body(), 200, [
            'Content-Type' => $response->header('Content-Type') ?: 'application/octet-stream',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
