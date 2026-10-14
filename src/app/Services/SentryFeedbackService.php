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

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SentryFeedbackService
{
    /**
     * Send user feedback for an existing Sentry event.
     *
     * @param string $eventId
     * @param string|null $name
     * @param string|null $email
     * @param string $comments
     * @return bool
     */
    public function send(string $eventId, ?string $name, ?string $email, string $comments): bool
    {
        $feedback = config('sentry.feedback');

        if (blank($feedback['token'] ?? null) || blank($feedback['organization'] ?? null) || blank($feedback['project'] ?? null)) {
            Log::warning('Sentry user feedback is not configured.', [
                'event_id' => $eventId,
            ]);

            return false;
        }

        $url = rtrim($feedback['endpoint'], '/')
            .'/projects/'.rawurlencode($feedback['organization'])
            .'/'.rawurlencode($feedback['project']).'/user-feedback/';

        /** @var PendingRequest $request */
        $request = Http::withToken($feedback['token'])
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
}
