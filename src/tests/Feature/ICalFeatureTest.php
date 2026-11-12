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

use App\CalendarLinks\AbstractCalendarLink;
use App\Models\People\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Tests\TestCase;

class ICalFeatureTest extends TestCase
{
    /**
     * @return void
     */
    public function test_ical_export_keeps_whitespace_for_folded_lines_starting_with_comma()
    {
        $calendarLink = new class extends AbstractCalendarLink
        {
            protected $viewName = 'ical';

            /**
             * @return array
             */
            public function getRenderData(Request $request, User $user)
            {
                $service = new class
                {
                    public $id = 9947;

                    public $description = 'Sommerkonzert Orgel Plus Gitarre: Silvia Elvers, Orgel / Christian Gruber, Gitarre';

                    public $date;

                    public $updated_at;

                    public function __construct()
                    {
                        $this->date = Carbon::create(2025, 8, 3, 0, 0, 0, 'Europe/Berlin');
                        $this->updated_at = Carbon::create(2025, 7, 15, 8, 19, 9, 'Europe/Berlin');
                    }

                    /**
                     * @return string
                     */
                    public function locationText()
                    {
                        return 'Stadtkirche';
                    }

                    /**
                     * @return string
                     */
                    public function titleText()
                    {
                        return 'GD';
                    }

                    /**
                     * @param  string  $role
                     * @return string
                     */
                    public function participantsText($role)
                    {
                        return match ($role) {
                            'P' => '',
                            'O' => '',
                            'M' => 'Breiser',
                            default => '',
                        };
                    }

                    /**
                     * @return string
                     */
                    public function descriptionText()
                    {
                        return $this->description;
                    }

                    /**
                     * @param  bool  $withSeparator
                     * @return string
                     */
                    public function timeText($withSeparator = true)
                    {
                        return '18:00';
                    }
                };

                return [$service];
            }
        };

        $user = User::factory()->create();
        $ical = $calendarLink->export(Request::create('/ical/export', 'GET'), $user);

        $this->assertStringContainsString("\r\n , Gitarre)", $ical);
        $this->assertStringNotContainsString("\r\n, Gitarre)", $ical);
    }

    /**
     * Ensure iCalendar content lines are not indented by Blade formatting.
     *
     * @return void
     */
    public function test_ical_templates_keep_content_lines_at_column_zero()
    {
        $templates = [
            'resources/views/ical/ical.blade.php',
            'resources/views/ical/absences.blade.php',
            'resources/views/ical/export/absences.blade.php',
            'resources/views/ical/export/events.blade.php',
            'resources/views/ical/export/ical.blade.php',
            'resources/views/ical/export/occurences.blade.php',
            'resources/views/baptisms/appointment/ical.blade.php',
            'resources/views/funerals/appointment/ical.blade.php',
        ];

        foreach ($templates as $template) {
            $contents = file_get_contents(base_path($template));

            $this->assertIsString($contents);
            $this->assertStringNotContainsString('fochrrmat', $contents);
            $this->assertDoesNotMatchRegularExpression(
                '/^[ \t]+(?:BEGIN|END):(?:VCALENDAR|VEVENT)|^[ \t]+(?:UID|LOCATION|SUMMARY|DESCRIPTION|CLASS|DTSTART|DTEND|DTSTAMP)(?:;[^:]*)?:/m',
                $contents,
                $template.' contains indented iCalendar content lines.'
            );
        }
    }
}
