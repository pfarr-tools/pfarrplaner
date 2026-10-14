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

namespace App\Http\Controllers;

use App\Services\SentryFeedbackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ErrorFeedbackController extends Controller
{
    /**
     * Store feedback for a Sentry event.
     *
     * @param Request $request
     * @param SentryFeedbackService $feedback
     * @return RedirectResponse
     */
    public function store(Request $request, SentryFeedbackService $feedback): RedirectResponse
    {
        $data = $request->validate([
            'event_id' => ['required', 'regex:/^[0-9a-f]{32}$/i'],
            'name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'comments' => ['required', 'string', 'min:3', 'max:5000'],
        ]);

        $sent = $feedback->send(
            $data['event_id'],
            $data['name'] ?? null,
            $data['email'] ?? null,
            $data['comments'],
        );

        return back()->with($sent ? 'feedback_success' : 'feedback_error', true);
    }
}
