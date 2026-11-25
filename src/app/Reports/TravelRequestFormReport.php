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

namespace App\Reports;

use App\Documents\PDF;
use App\Models\Leave\Absence;
use App\Services\FileNameService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** PDF report for travel request forms. */
class TravelRequestFormReport extends AbstractPDFDocumentReport
{
    public const FILE_SIGNATURE = '21.3';

    public const FILE_TITLE = 'Dienstreiseantrag';

    /**
     * @var string
     */
    public $title = 'Dienstreiseantrag';

    /**
     * @var string
     */
    public $group = 'Formulare';

    /**
     * @var string
     */
    public $description = 'Dienstreiseantrag für das Dekanatamt';

    protected $inertia = true;

    /**
     * Returns true if the report is active for the current user
     */
    public function isActive(): bool
    {
        return $this->authenticatedUser()->hasRole('Pfarrer:in');
    }

    /**
     * Show upcoming absences of the authenticated user for report setup.
     *
     * @return Response Inertia response with the report setup page.
     */
    public function setup(): Response
    {
        $absences = Absence::where('user_id', $this->authenticatedUser()->id)
            ->where('from', '>', Carbon::now())
            ->orderBy('from')
            ->get();

        return Inertia::render('Report/TravelRequest/Setup', compact('absences'));
    }

    /**
     * Render the travel request as a downloadable PDF document.
     *
     * @param  Request  $request  Report request containing the absence id.
     * @return mixed PDF download response.
     */
    public function render(Request $request)
    {
        $data = $request->validate(['absence' => 'int|exists:absences,id']);

        $data['absence'] = Absence::findOrFail((int) $data['absence']);

        $data['duration'] = $data['absence']->to->diff($data['absence']->from)->days + 1;

        $packageContents = file_get_contents(base_path('package.json'));
        if ($packageContents === false) {
            throw new \RuntimeException('Die Anwendungsmetadaten konnten nicht gelesen werden.');
        }
        $packageConfig = json_decode($packageContents, true);
        $data['version'] = $packageConfig['version'];

        $pdf = PDF::fromView($this->getRenderViewName(), $data, $this->header, $this->footer);

        $filename = FileNameService::make(
            self::FILE_TITLE,
            'pdf',
            self::FILE_SIGNATURE,
            [$data['absence']->from, $data['absence']->to],
            false,
            $this->authenticatedUser(),
        );
        $tempFile = tempnam(sys_get_temp_dir(), $filename);
        $pdf->save($tempFile);

        return response()->download($tempFile, $filename, ['Content-Type', 'application/pdf']);

    }
}
