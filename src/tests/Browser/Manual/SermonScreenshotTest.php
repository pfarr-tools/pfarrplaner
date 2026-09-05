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

use App\Models\Service;
use App\Models\Sermon;
use Laravel\Dusk\Browser;

class SermonScreenshotTest extends ManualScreenshotTestCase
{
    protected Sermon $sermon;

    protected function setUp(): void
    {
        parent::setUp();
        $service      = Service::factory()->create();
        $this->sermon = Sermon::create([
            'title'      => 'Musterpredigt',
            'subtitle'   => 'Gottes Liebe bleibt nicht auf Abstand',
            'reference'  => 'Johannes 3,16',
            'text'       => '<p>Liebe Gemeinde, manchmal reicht ein einziger Satz, um wieder Boden unter die Füße zu bekommen. „Also hat Gott die Welt geliebt“ - nicht nur die heile Welt, nicht nur die fromme Welt, nicht nur die Welt, die gerade alles richtig macht. Gott liebt diese Welt: mit ihren offenen Fragen, ihren müden Menschen, ihren verwundeten Orten und ihren kleinen Hoffnungen.</p><p>Das ist mehr als ein schöner Gedanke für ein Kirchenfenster. Es ist eine Bewegung Gottes auf uns zu. Liebe bleibt bei Gott nicht auf Abstand. Sie sucht den Weg in unsere Häuser, an unsere Krankenbetten, in unsere Sitzungen, an unsere Küchentische und auch dorthin, wo wir selbst kaum noch mit ihr rechnen.</p><p>Darum hören wir diesen Vers heute nicht als schnellen Trost, sondern als Einladung: Wir dürfen uns dieser Liebe anvertrauen und fragen, wie sie durch uns weitergeht - in einem Wort, das aufrichtet, in einem Besuch, der nicht aufgeschoben wird, in einer Entscheidung, die dem Leben dient.</p>',
        ]);
        $service->update(['sermon_id' => $this->sermon->id]);
    }

    public function testCaptureSermonEditor(): void
    {
        $this->browse(function (Browser $browser) {
            $this->captureManualScreenshot(
                $browser,
                route('sermon.editor', $this->sermon->id),
                'predigt-editor',
                800
            );
        });
    }

    public function testCaptureSermonReader(): void
    {
        $this->browse(function (Browser $browser) {
            $this->captureManualScreenshot(
                $browser,
                route('sermon.reader', $this->sermon->id),
                'predigt-leseansicht',
                800
            );
        });
    }
}
