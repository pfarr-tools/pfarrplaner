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

class AuthScreenshotTest extends ManualScreenshotTestCase
{
    public function testCaptureLoginPage(): void
    {
        $this->browse(function (Browser $browser) {
            // Visit as unauthenticated user
            $browser->visit(route('login'))
                    ->waitFor('#app', 10)
                    ->pause(500);

            $duskName = 'manual-login';
            $browser->screenshot($duskName);
            $src = base_path('tests/Browser/screenshots/' . $duskName . '.png');
            if (file_exists($src)) {
                copy($src, $this->screenshotDir . '/login.png');
            }
        });
    }

    public function testCapturePasswordChangePage(): void
    {
        $this->browse(function (Browser $browser) {
            $this->captureManualScreenshot(
                $browser,
                route('password.edit'),
                'change-password'
            );
        });
    }
}
