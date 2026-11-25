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

namespace App\Http\Controllers;

use App\Models\Liturgy\Text;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Controller for liturgical text administration. */
class LiturgicalTextsController extends Controller
{
    /**
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->authorizeResource(Text::class, 'text');
    }

    /**
     * Render the administrative list of liturgical texts.
     *
     * @return Response Inertia response with all texts ordered by title.
     */
    public function index(): Response
    {
        $texts = Text::orderBy('title')->get();

        return Inertia::render('Admin/LiturgicalTexts/Index', compact('texts'));
    }

    /**
     * Render the editor for an existing liturgical text.
     *
     * @param  Text  $text  Text to edit.
     * @return Response Inertia response with the text and its available agenda codes.
     */
    public function edit(Text $text): Response
    {
        $agendaCodes = Text::select('agenda_code')->orderBy('agenda_code')->pluck('agenda_code')->unique();
        $codes = [];
        foreach ($agendaCodes as $agendaCode) {
            $codes[] = ['id' => $agendaCode, 'name' => $agendaCode];
        }

        return Inertia::render('Admin/LiturgicalTexts/LiturgicalTextEditor', compact('text', 'codes'));
    }

    /**
     * Render the editor for a new liturgical text.
     *
     * @return Response Inertia response with an empty text editor.
     */
    public function create(): Response
    {
        return $this->edit((new Text)->fill(['title' => '', 'text' => '', 'agenda_code' => '', 'source' => '', 'needs_replacement' => '', 'notice' => '']));
    }

    /**
     * Store a new liturgical text from the validated request.
     *
     * @param  Request  $request  Request containing the text data.
     * @return RedirectResponse Redirect to the liturgical text list.
     */
    public function store(Request $request): RedirectResponse
    {
        Text::create($this->validateRequest($request));

        return redirect()->route('admin.text.index')->with('success', 'Der Text wurde gespeichert.');
    }

    /**
     * Update an existing liturgical text from the validated request.
     *
     * @param  Request  $request  Request containing the updated text data.
     * @param  Text  $text  Text to update.
     * @return RedirectResponse Redirect to the liturgical text list.
     */
    public function update(Request $request, Text $text): RedirectResponse
    {
        $text->update($this->validateRequest($request));

        return redirect()->route('admin.text.index')->with('success', 'Der Text wurde gespeichert.');
    }

    /**
     * Import liturgical texts from a pasted text representation.
     *
     * @param  Request  $request  Request containing the data to import.
     * @return RedirectResponse Redirect to the liturgical text overview.
     */
    public function import(Request $request): RedirectResponse
    {
        $data = $request->validate(['data' => 'required|string'])['data'];
        $records = [];
        $record = $newRecord = [
            'agenda_code' => '',
            'title' => '',
            'text' => [],
            'notice' => [],
            'source' => [],
            'needs_replacement' => '',
        ];
        $code = '';
        foreach (explode("\n", $data) as $line) {
            if (substr($line, 0, 4) == '    ') {
                $line = "\t".trim($line);
            } else {
                $line = trim($line);
            }
            if ($line == '---') {
                // insert line breaks
                foreach (['text', 'notice', 'source'] as $key) {
                    $lines = $record[$key];
                    $record[$key] = implode("\n", is_array($lines) ? $lines : [$lines]);
                }
                // check if replacement is needed
                foreach (['funeral' => 'bestattung', 'baptism' => 'taufe', 'wedding' => 'trauung'] as $keyCode => $key) {
                    if (strpos($record['text'], '['.$key) !== false) {
                        $record['needs_replacement'] = $keyCode;
                    }
                }
                if (trim($record['text'])) {
                    $records[] = Text::create($record);
                }
                $record = $newRecord;
                $record['agenda_code'] = $code;
            } elseif (substr($line, 0, 1) == '#') {
                $record['agenda_code'] = $code = trim(substr($line, 1));
            } elseif (substr($line, 0, 2) == 'T:') {
                $record['title'] = trim(substr($line, 2));
            } elseif (substr($line, 0, 2) == 'Q:') {
                $record['source'][] = trim(substr($line, 2));
            } elseif (substr($line, 0, 2) == '//') {
                $record['notice'][] = trim(substr($line, 2));
            } else {
                $record['text'][] = $line;
            }
        }

        return redirect()->route('liturgy.text.index');
    }

    /**
     * @return mixed
     */
    protected function validateRequest(Request $request)
    {
        return $request->validate(
            [
                'title' => 'required|string',
                'text' => 'required|string',
                'agenda_code' => 'nullable|string',
                'needs_replacement' => 'nullable|string',
                'source' => 'nullable|string',
                'notice' => 'nullable|string',
            ]
        );
    }

    /**
     * Delete a liturgical text.
     *
     * @param  Text  $text  Text to delete.
     * @return RedirectResponse Redirect to the liturgical text list.
     */
    public function destroy(Text $text): RedirectResponse
    {
        $text->delete();

        return redirect()->route('admin.text.index')->with('success', 'Der Text wurde gelöscht.');
    }
}
