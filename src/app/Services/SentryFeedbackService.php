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

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SentryFeedbackService
{
    /**
     * Send user feedback for an existing Sentry event.
     */
    public function send(string $eventId, ?string $name, ?string $email, string $comments): bool
    {
        /** @var array{token?: mixed, organization?: mixed, project?: mixed, endpoint?: mixed} $feedback */
        $feedback = config('sentry.feedback');

        $token = $feedback['token'] ?? null;
        $organization = $feedback['organization'] ?? null;
        $project = $feedback['project'] ?? null;
        $configuredEndpoint = $feedback['endpoint'] ?? null;

        if (! is_string($token) || ! is_string($organization) || ! is_string($project)
            || blank($token) || blank($organization) || blank($project)) {
            Log::warning('Sentry user feedback is not configured.', [
                'event_id' => $eventId,
            ]);

            return false;
        }

        $url = rtrim($this->endpoint(is_string($configuredEndpoint) ? $configuredEndpoint : null), '/')
            .'/projects/'.rawurlencode($organization)
            .'/'.rawurlencode($project).'/user-feedback/';

        /** @var PendingRequest $request */
        $request = Http::withToken($token)
            ->acceptJson()
            ->asJson()
            ->timeout(10);

        try {
            $response = $request->post($url, array_filter([
                'event_id' => $eventId,
                'name' => $name,
                'email' => $email,
                'comments' => $comments,
            ], static fn ($value) => $value !== null && $value !== ''));
        } catch (Throwable $exception) {
            Log::warning('Sentry user feedback request failed.', [
                'event_id' => $eventId,
                'exception' => get_class($exception),
            ]);

            return false;
        }

        if ($response->successful()) {
            return true;
        }

        Log::warning('Sentry user feedback could not be sent.', [
            'event_id' => $eventId,
            'status' => $response->status(),
        ]);

        return false;
    }

    /**
     * Resolve the feedback API endpoint from an explicit override or the Sentry DSN.
     */
    private function endpoint(?string $configuredEndpoint): string
    {
        if (filled($configuredEndpoint)) {
            return $configuredEndpoint;
        }

        $dsn = config('sentry.dsn');
        $parsedDsn = is_string($dsn) ? parse_url($dsn) : false;

        if (is_array($parsedDsn) && filled($parsedDsn['host'] ?? null)) {
            $scheme = $parsedDsn['scheme'] ?? 'https';
            $port = isset($parsedDsn['port']) ? ':'.$parsedDsn['port'] : '';

            return $scheme.'://'.$parsedDsn['host'].$port.'/api/0';
        }

        return 'https://sentry.io/api/0';
    }
}
