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

use App\Http\Middleware\ForceDomain;
use App\Models\People\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SecurityPublicAssetAccessFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ForceDomain::class);
        Storage::fake(config('filesystems.default'));
    }

    public function testGuestCannotAccessImageWithoutSignature(): void
    {
        $path = 'security-test-image-'.uniqid().'.png';
        Storage::put('attachments/'.$path, 'test');

        $this->get(route('image', ['path' => $path]))
            ->assertUnauthorized();
    }

    public function testSignedImageUrlRemainsAccessibleWithoutLogin(): void
    {
        $path = 'security-test-signed-image-'.uniqid().'.png';
        Storage::put('attachments/'.$path, 'test');

        $this->get(URL::signedRoute('image', ['path' => $path]))
            ->assertOk();
    }

    public function testGuestCannotAccessStorageDownloadWithoutSignature(): void
    {
        $path = 'security-test-file-'.uniqid().'.pdf';
        Storage::put('attachments/'.$path, 'test');

        $this->get(route('storage', ['path' => pathinfo($path, PATHINFO_FILENAME), 'prettyName' => basename($path)]))
            ->assertUnauthorized();
    }

    public function testGuestCannotOpenVacationEmbed(): void
    {
        $user = User::factory()->create();

        $this->get(route('embed.user.vacations', ['user' => $user->id]))
            ->assertRedirect(route('login'));
    }
}
