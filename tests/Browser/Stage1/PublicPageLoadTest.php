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

use App\Models\Places\City;
use Illuminate\Support\Facades\URL;
use Laravel\Dusk\Browser;
use Tests\AbstractPageLoadTest;

class PublicPageLoadTest extends AbstractPageLoadTest
{
    protected City $city;

    protected function setUp(): void
    {
        parent::setUp();
        $this->city = City::factory()->create();
    }

    /**
     * The QR page is public (no auth middleware) — visit without loginAs.
     */
    public function testCityQrLoads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit(route('qr', $this->city->id))
                    ->waitFor('#app', 10)
                    ->assertDontSee('500')
                    ->assertDontSee('Whoops');
        });
    }

    /**
     * Streaming troubleshooter requires a signed URL — generate one for the test.
     */
    public function testStreamingTroubleshooterLoads(): void
    {
        $signedUrl = URL::signedRoute('streaming.troubleshooter', ['city' => $this->city->name]);

        $this->browse(function (Browser $browser) use ($signedUrl) {
            $this->assertPageLoads($browser, $signedUrl);
        });
    }
}
