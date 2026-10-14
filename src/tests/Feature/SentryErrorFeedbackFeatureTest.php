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

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SentryErrorFeedbackFeatureTest extends TestCase
{
    /**
     * The development test route renders the friendly error page.
     *
     * @return void
     */
    public function testErrorTestRouteRendersTheFriendlyErrorPage(): void
    {
        $response = $this->get('/__test/500');

        $response->assertStatus(500)
            ->assertSee('Wir kümmern uns darum.')
            ->assertSee('Fehlerreferenz')
            ->assertSee('Was ist passiert?')
            ->assertSee('img/logo/pfarrplaner.svg');
    }

    /**
     * User feedback is sent to the Sentry feedback endpoint.
     *
     * @return void
     */
    public function testUserFeedbackIsSentToSentry(): void
    {
        config()->set('sentry.feedback', [
            'token' => 'feedback-token',
            'organization' => 'pfarr-tools',
            'project' => 'pfarrplaner',
            'endpoint' => 'https://sentry.example/api/0',
        ]);
        Http::fake(['https://sentry.example/*' => Http::response([], 200)]);

        $response = $this->from('/__test/500')->post('/error-feedback', [
            'event_id' => str_repeat('a', 32),
            'comments' => 'Der Kalender konnte nicht geöffnet werden.',
            'name' => 'Testperson',
            'email' => 'test@example.org',
        ]);

        $response->assertRedirect('/__test/500');
        $response->assertSessionHas('feedback_success');
        Http::assertSent(function ($request) {
            return $request->url() === 'https://sentry.example/api/0/projects/pfarr-tools/pfarrplaner/user-feedback/'
                && $request->hasHeader('Authorization', 'Bearer feedback-token')
                && $request['event_id'] === str_repeat('a', 32)
                && $request['comments'] === 'Der Kalender konnte nicht geöffnet werden.';
        });
    }

    /**
     * The test route is unavailable in production.
     *
     * @return void
     */
    public function testErrorTestRouteIsUnavailableInProduction(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->get('/__test/500')->assertNotFound();
    }
}
