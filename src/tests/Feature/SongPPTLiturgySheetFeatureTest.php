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

use App\Liturgy\LiturgySheets\SongPPTLiturgySheet;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class SongPPTLiturgySheetFeatureTest extends TestCase
{
    public function test_long_event_lists_are_split_across_multiple_slides(): void
    {
        $sheet = new class extends SongPPTLiturgySheet
        {
            /**
             * @return array<int, Collection>
             */
            public function paginateSlidesForTest(Collection $events): array
            {
                return $this->paginateEventListSlides($events);
            }

            /**
             * @param  array<string, array<int, Collection>>  $pagesByDate
             * @return array<string, array<int, Collection>>
             */
            public function suppressHighlightedOnlySlidesForTest(array $pagesByDate, iterable $highlightedEventsByDate): array
            {
                return $this->suppressHighlightedOnlyEventListSlides($pagesByDate, $highlightedEventsByDate);
            }
        };

        $sheet->setConfiguration([
            'fontSize' => 40,
        ]);

        $events = collect(range(1, 8))->map(function (int $index) {
            $service = new class
            {
                public string $locationTextWithCity = 'Musterkirche, Musterstadt';

                public function timeText(): string
                {
                    return '10:00';
                }

                /**
                 * @param  bool  $short
                 */
                public function titleText($short = true): string
                {
                    return 'Fallback-Titel';
                }
            };

            $eventMeta = new class
            {
                public bool $is_allday = false;

                public function descriptionText(): string
                {
                    return 'Eine ausführliche Veranstaltungsbeschreibung für den Folienumbruch';
                }
            };

            return new class($index, $service, $eventMeta)
            {
                public object $service;

                public object $event;

                private int $index;

                public function __construct(int $index, object $service, object $event)
                {
                    $this->index = $index;
                    $this->service = $service;
                    $this->event = $event;
                }

                public function getAdText(string $adChannelKey, string $defaultTo = ''): string
                {
                    return 'Sehr lange Veranstaltungsbeschreibung Nummer '.$this->index
                        .' mit vielen Zusatzworten fuer den Umbruch auf mehreren Zeilen'
                        .' und noch mehr Text fuer die Hoehenberechnung im Folienlayout';
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

    public function test_event_list_slides_containing_only_highlighted_events_are_suppressed(): void
    {
        $sheet = new class extends SongPPTLiturgySheet
        {
            /**
             * @param  array<string, array<int, Collection>>  $pagesByDate
             * @return array<string, array<int, Collection>>
             */
            public function suppressHighlightedOnlySlidesForTest(array $pagesByDate, iterable $highlightedEventsByDate): array
            {
                return $this->suppressHighlightedOnlyEventListSlides($pagesByDate, $highlightedEventsByDate);
            }
        };

        $highlightedEvent = $this->fakeAdEvent(1);
        $plainEvent = $this->fakeAdEvent(2);

        $pagesByDate = [
            '2026-05-24' => [
                collect([$highlightedEvent]),
                collect([$highlightedEvent, $plainEvent]),
            ],
        ];

        $filteredPages = $sheet->suppressHighlightedOnlySlidesForTest($pagesByDate, [
            '2026-05-24' => collect([$highlightedEvent]),
        ]);

        $this->assertCount(1, $filteredPages['2026-05-24']);
        $this->assertSame([1, 2], $filteredPages['2026-05-24'][0]->pluck('id')->all());
    }

    public function test_title_slide_contains_service_details(): void
    {
        $sheet = new class extends SongPPTLiturgySheet
        {
            /**
             * @return array<int, string>
             */
            public function titleSlideLinesForTest(object $service): array
            {
                return $this->titleSlideLines($service);
            }
        };

        $service = $this->mock(Service::class);
        $service->shouldReceive('titleText')->with(false)->andReturn('Gottesdienst zum Sonntag');
        $service->shouldReceive('dateText')->andReturn('24.05.2026');
        $service->shouldReceive('timeText')->andReturn('10:00 Uhr');
        $service->shouldReceive('locationText')->andReturn('Stadtkirche');

        $this->assertSame([
            'Gottesdienst zum Sonntag',
            '24.05.2026, 10:00 Uhr, Stadtkirche',
        ], $sheet->titleSlideLinesForTest($service));
    }

    protected function fakeAdEvent(int $id): object
    {
        $service = new class($id)
        {
            public int $id;

            public string $locationTextWithCity = 'Musterkirche, Musterstadt';

            public function __construct(int $id)
            {
                $this->id = $id;
            }

            public function timeText(): string
            {
                return '10:00';
            }

            /**
             * @param  bool  $short
             */
            public function titleText($short = true): string
            {
                return 'Fallback-Titel';
            }
        };

        $eventMeta = new class
        {
            public bool $is_allday = false;
        };

        return new class($id, $service, $eventMeta)
        {
            public int $id;

            public object $service;

            public object $event;

            public Carbon $start;

            public function __construct(int $id, object $service, object $event)
            {
                $this->id = $id;
                $this->service = $service;
                $this->event = $event;
                $this->start = Carbon::parse('2026-05-24 10:00:00');
            }

            public function getAdText(string $adChannelKey, string $defaultTo = ''): string
            {
                return 'Veranstaltung '.$this->id;
            }
        };
    }
}
