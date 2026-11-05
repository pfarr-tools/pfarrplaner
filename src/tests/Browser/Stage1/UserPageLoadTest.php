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

namespace Tests\Browser\Stage1;

use Laravel\Dusk\Browser;
use Tests\AbstractPageLoadTest;

class UserPageLoadTest extends AbstractPageLoadTest
{
    public function test_tokens_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('tokens.index'));
        });
    }

    public function test_i_cal_connect_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('ical.connect'));
        });
    }

    public function test_profile_editor_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->assertPageLoads($browser, route('user.profile'));
        });
    }
}
