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

namespace Tests\Browser\Manual;

use Laravel\Dusk\Browser;

class InputsScreenshotTest extends ManualScreenshotTestCase
{
    public function testCaptureInputsIndex(): void
    {
        $this->browse(function (Browser $browser) {
            $this->captureManualScreenshot(
                $browser,
                route('inputs.index'),
                'eingaben-uebersicht',
                800
            );
        });
    }
}
