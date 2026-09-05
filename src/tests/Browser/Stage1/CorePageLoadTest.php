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

namespace Tests\Browser\Stage1;

use Laravel\Dusk\Browser;
use Tests\AbstractPageLoadTest;

class CorePageLoadTest extends AbstractPageLoadTest
{
    public function testHomePageLoads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('home'));
        });
    }

    public function testAboutPageLoads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('about'));
        });
    }

    public function testDashPageLoads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, '/home');
        });
    }
}
