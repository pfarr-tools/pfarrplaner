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

use Illuminate\Http\Request;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\SentryFeedbackService;
use Mockery;
use Sentry\SentrySdk;
use Sentry\State\HubInterface;
use RuntimeException;
use Tests\TestCase;

class SentryErrorFeedbackFeatureTest extends TestCase
{
    /**
     * Unexpected exceptions are reported through Laravel's exception handler.
     *
     * @return void
     */
    public function testUnexpectedExceptionsAreReportedToSentry(): void
    {
        $exception = new RuntimeException('Sentry regression test');
        $originalHub = SentrySdk::getCurrentHub();
        $hub = Mockery::mock(HubInterface::class);
        $hub->shouldReceive('captureException')
            ->once()
            ->with($exception, Mockery::type('Sentry\\EventHint'))
            ->andReturnNull();

        SentrySdk::setCurrentHub($hub);

        try {
            app(\Illuminate\Contracts\Debug\ExceptionHandler::class)->report($exception);
        } finally {
            SentrySdk::setCurrentHub($originalHub);
        }
    }

    /**
     * Rendered exceptions are logged together with their displayed reference.
     *
     * @return void
     */
    public function testRenderedExceptionsAreLoggedWithTheirErrorReference(): void
    {
        $exception = new RuntimeException('Logged exception regression test');
        $request = Request::create('/broken', 'GET');
        $request->attributes->set('error_reference', '8334b37a4aa8479d98181455f812c63c');
        $logged = null;

        Log::listen(function (MessageLogged $event) use (&$logged): void {
            if ($event->level === 'error') {
                $logged = $event;
            }
        });

        app(\Illuminate\Contracts\Debug\ExceptionHandler::class)->render($request, $exception);

        $this->assertNotNull($logged);
        $this->assertSame('Unhandled exception rendered', $logged->message);
        $this->assertSame('8334b37a4aa8479d98181455f812c63c', $logged->context['error_reference']);
        $this->assertSame($exception, $logged->context['exception']);
    }

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
     * The GlitchTip debug route deliberately reports an exception in local environments.
     *
     * @return void
     */
    public function testGlitchTipDebugRouteThrowsAnException(): void
    {
        $this->get('/debug-glitchtip')
            ->assertStatus(500);
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
     * User feedback uses the DSN host when no API endpoint override is configured.
     *
     * @return void
     */
    public function testUserFeedbackUsesTheDsnHostWithoutAnEndpointOverride(): void
    {
        config()->set('sentry.dsn', 'https://public@example.invalid/42');
        config()->set('sentry.feedback', [
            'token' => 'feedback-token',
            'organization' => 'pfarr-tools',
            'project' => 'pfarrplaner',
            'endpoint' => null,
        ]);
        Http::fake(['https://example.invalid/*' => Http::response([], 200)]);

        $this->assertTrue(app(SentryFeedbackService::class)->send(
            str_repeat('b', 32),
            'Testperson',
            'test@example.org',
            'Der Kalender konnte nicht geöffnet werden.',
        ));

        Http::assertSent(fn ($request) => $request->url()
            === 'https://example.invalid/api/0/projects/pfarr-tools/pfarrplaner/user-feedback/');
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

    /**
     * The GlitchTip debug route is unavailable in production.
     *
     * @return void
     */
    public function testGlitchTipDebugRouteIsUnavailableInProduction(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->assertNotSame(200, $this->get('/debug-glitchtip')->status());
    }
}
