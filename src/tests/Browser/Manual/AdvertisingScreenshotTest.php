<?php

namespace Tests\Browser\Manual;

use App\Models\Ads\AdConfig;
use App\Models\Attachment;
use App\Models\Places\City;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Laravel\Dusk\Browser;

class AdvertisingScreenshotTest extends ManualScreenshotTestCase
{
    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $city = City::factory()->create(['name' => 'Mustergemeinde']);
        $this->superAdminUser->cities()->attach($city->id, ['permission' => 'w']);
        $this->superAdminUser->homeCities()->attach($city->id);

        $this->service = Service::factory()->create([
            'city_id' => $city->id,
            'title' => 'Sommerabend in der Stadtkirche',
            'description' => 'Ein musikalischer Gottesdienst mit anschließendem Beisammensein.',
            'date' => Carbon::now()->addDays(12)->setTime(18, 0)->utc(),
            'time' => '18:00',
            'ad_text' => 'Musik, Begegnung und ein sommerlicher Abend in der Stadtkirche.',
        ]);

        $this->createExampleImages();
        AdConfig::create([
            'service_id' => $this->service->id,
            'slug' => 'story',
            'offset' => 5,
            'ad_text' => 'Jetzt vormerken und weitersagen!',
        ]);
        AdConfig::create([
            'service_id' => $this->service->id,
            'slug' => 'newsletter',
            'offset' => 10,
            'ad_text' => 'Ein besonderer Abend für die ganze Gemeinde.',
        ]);
    }

    /** Create deterministic local SVGs so the screenshots contain real previews. */
    protected function createExampleImages(): void
    {
        $cuts = [
            null,
            'bildschirm-4x3',
            'bildschirm-16x9',
            'communiapp',
            'quadratisch',
            'story',
        ];

        foreach ($cuts as $index => $cut) {
            $file = 'attachments/manual-ad-'.$index.'.svg';
            Storage::put($file, $this->exampleSvg($index));
            Attachment::create([
                'title' => $cut ? 'Sommerabend – '.$cut : 'Sommerabend – Bildquelle',
                'file' => $file,
                'attachable_id' => $this->service->id,
                'attachable_type' => Service::class,
                'cut' => $cut,
            ]);
        }
    }

    /** Return a lightweight, browser-renderable example image. */
    protected function exampleSvg(int $variant): string
    {
        $colors = ['#57205f', '#29477f', '#2f6f68', '#b85b2a', '#7a314f', '#2f526e'];
        $color = $colors[$variant % count($colors)];

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="800" viewBox="0 0 1200 800">
  <rect width="1200" height="800" fill="{$color}"/>
  <circle cx="980" cy="140" r="220" fill="#ffffff" opacity=".12"/>
  <circle cx="180" cy="690" r="300" fill="#ffffff" opacity=".08"/>
  <path d="M170 570h860v38H170zM250 530h700v28H250z" fill="#f4d7a1"/>
  <path d="M365 530V300l230-130 230 130v230h-90V350H455v180z" fill="#f6f1e8"/>
  <path d="M570 170h50v-55h-50zM475 350h290v180H475z" fill="#d9c49a"/>
  <text x="600" y="690" fill="#ffffff" font-family="sans-serif" font-size="48" text-anchor="middle">Sommerabend in der Stadtkirche</text>
</svg>
SVG;
    }

    public function test_capture_ad_editor_tabs(): void
    {
        $this->browse(function (Browser $browser) {
            $this->captureManualScreenshot(
                $browser,
                route('service.ads', $this->service->slug),
                'werbung-editor-werbebilder',
                800
            );

            $this->captureTab($browser, 'publications', 'werbung-editor-veroeffentlichungen-allgemein', 300);
            $browser->click('.publication-tab:nth-child(2)')->pause(300);
            $this->captureCurrentManualScreenshot($browser, 'werbung-editor-veroeffentlichungen', 0);
        });
    }
}
