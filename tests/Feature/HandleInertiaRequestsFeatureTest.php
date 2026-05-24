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

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\People\User;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class HandleInertiaRequestsFeatureTest extends TestCase
{
    /**
     * Resolve shared props directly from the middleware, bypassing HTTP stack.
     *
     * @return array
     */
    protected function sharedProps(): array
    {
        $middleware = $this->app->make(HandleInertiaRequests::class);
        $request = request();
        $request->setLaravelSession($this->app->make('session')->driver());
        return $middleware->share($request);
    }

    /**
     * Shared props always include flash and errors keys, even for guest requests.
     */
    public function testSharedPropsContainFlashAndErrors()
    {
        $props = $this->sharedProps();

        $this->assertArrayHasKey('flash', $props);
        $this->assertArrayHasKey('errors', $props);
    }

    /**
     * Flash messages stored in the session appear in the shared flash prop.
     */
    public function testFlashMessagesAreShared()
    {
        Session::put('success', 'Gespeichert');

        $props = $this->sharedProps();

        $flash = value($props['flash']);
        $this->assertSame('Gespeichert', value($flash['success']));
    }

    /**
     * Validation errors stored in the session appear in the shared errors prop.
     */
    public function testValidationErrorsAreShared()
    {
        $errors = new \Illuminate\Support\MessageBag(['name' => ['Das Feld ist erforderlich.']]);
        Session::put('errors', new \Illuminate\Support\ViewErrorBag()->put('default', $errors));

        $props = $this->sharedProps();

        $shared = ($props['errors'])();
        $this->assertObjectHasProperty('name', $shared);
    }

    public function testAuthenticatedSharedPropsContainInterstitials()
    {
        config()->set('interstitials.items', [
            'breaking-change' => [
                'enabled' => true,
                'audience' => 'all',
                'title' => 'Wichtige Änderung',
            ],
        ]);

        $user = User::factory()->create();
        $this->actingAs($user);

        $props = $this->sharedProps();

        $this->assertArrayHasKey('interstitials', $props);
        $this->assertSame('breaking-change', $props['interstitials']()[0]['key']);
    }
}
