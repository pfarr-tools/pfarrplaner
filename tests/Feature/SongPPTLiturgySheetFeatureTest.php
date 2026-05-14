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

namespace Tests\Feature;

use App\Liturgy\LiturgySheets\SongPPTLiturgySheet;
use Illuminate\Support\Collection;
use Tests\TestCase;

class SongPPTLiturgySheetFeatureTest extends TestCase
{
    /**
     * @return void
     */
    public function testLongEventListsAreSplitAcrossMultipleSlides(): void
    {
        $sheet = new class extends SongPPTLiturgySheet {
            /**
             * @param Collection $events
             * @return array<int, Collection>
             */
            public function paginateSlidesForTest(Collection $events): array
            {
                return $this->paginateEventListSlides($events);
            }
        };

        $sheet->setConfiguration([
            'fontSize' => 40,
        ]);

        $events = collect(range(1, 8))->map(function (int $index) {
            $service = new class {
                public string $locationTextWithCity = 'Musterkirche, Musterstadt';

                /**
                 * @return string
                 */
                public function timeText(): string
                {
                    return '10:00';
                }

                /**
                 * @param bool $short
                 * @return string
                 */
                public function titleText($short = true): string
                {
                    return 'Fallback-Titel';
                }
            };

            $eventMeta = new class {
                public bool $is_allday = false;
            };

            return new class($index, $service, $eventMeta) {
                public object $service;
                public object $event;
                private int $index;

                public function __construct(int $index, object $service, object $event)
                {
                    $this->index = $index;
                    $this->service = $service;
                    $this->event = $event;
                }

                /**
                 * @param string $adChannelKey
                 * @param string $defaultTo
                 * @return string
                 */
                public function getAdText(string $adChannelKey, string $defaultTo = ''): string
                {
                    return 'Sehr lange Veranstaltungsbeschreibung Nummer ' . $this->index
                        . ' mit vielen Zusatzworten fuer den Umbruch auf mehreren Zeilen'
                        . ' und noch mehr Text fuer die Hoehenberechnung im Folienlayout';
                }
            };
        });

        $pages = $sheet->paginateSlidesForTest($events);

        $this->assertGreaterThan(1, count($pages));
        $this->assertGreaterThan(0, $pages[0]->count());
        $this->assertSame(8, collect($pages)->sum(function (Collection $page) {
            return $page->count();
        }));
    }
}
