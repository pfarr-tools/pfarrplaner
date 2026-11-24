<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 * @version git: $Id: 927153449133482a7f827b7ea09deed1e4b83ffa $
 */

namespace Tests\Feature;

use App\Helpers\StoragePath;
use App\Liturgy\LiturgySheets\SongPPTLiturgySheet;
use App\Models\Attachment;
use App\Models\People\User;
use App\Models\Sermon;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpPresentation\PhpPresentation;
use Tests\TestCase;
use ZipArchive;

/** Regression tests for configurable PowerPoint/ODP separator slides. */
class SongPPTLiturgySheetFeatureTest extends TestCase
{
    /**
     * Exported PowerPoint packages use the image format detected from a source file without an extension.
     */
    public function test_ppt_export_normalizes_image_parts_without_source_extensions(): void
    {
        $sourcePath = tempnam(sys_get_temp_dir(), 'ppt-image-source-');
        $outputPath = tempnam(sys_get_temp_dir(), 'ppt-image-export-');
        file_put_contents(
            $sourcePath,
            base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAb/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABBQJ//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPwF//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPwF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQAGPwJ//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPyF//9oADAMBAAIAAwAAABAf/8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPxB//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPxB//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxB//9oACAEBAAE/EB//2Q==', true)
        );
        $sheet = new class extends SongPPTLiturgySheet
        {
            /**
             * Create a PowerPoint containing one image with the supplied source path.
             *
             * @param  string  $sourcePath  Path to the source image.
             * @param  string  $outputPath  Destination path for the presentation archive.
             */
            public function exportImageForTest(string $sourcePath, string $outputPath): void
            {
                $this->ppt = new PhpPresentation;
                $this->ppt->getActiveSlide()->createDrawingShape()
                    ->setPath($sourcePath)
                    ->setWidth(1)
                    ->setHeight(1);
                $this->createPPTFile($outputPath);
            }
        };

        $sheet->exportImageForTest($sourcePath, $outputPath);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($outputPath));
        $mediaFiles = [];
        $relationshipFiles = [];
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $path = $zip->getNameIndex($index);
            if (is_string($path) && str_starts_with($path, 'ppt/media/')) {
                $mediaFiles[] = $path;
            }
            if (is_string($path) && str_ends_with($path, '.rels')) {
                $relationshipFiles[] = $zip->getFromName($path);
            }
        }
        $contentTypes = $zip->getFromName('[Content_Types].xml');
        $zip->close();
        unlink($sourcePath);
        unlink($outputPath);

        $this->assertCount(1, $mediaFiles);
        $this->assertMatchesRegularExpression('#^ppt/media/[^/]+\.jpg$#', $mediaFiles[0]);
        $this->assertStringNotContainsString('Extension=""', $contentTypes);
        $this->assertStringContainsString(basename($mediaFiles[0]), implode('', $relationshipFiles));
    }

    /** ODP content keeps indentation from Psalm verse tabs. */
    public function test_odp_file_preserves_indented_verse_lines(): void
    {
        $sheet = new class extends SongPPTLiturgySheet
        {
            /**
             * @param  array<int, string>  $lines
             */
            public function exportOdpSlideForTest(array $lines, string $outputPath): void
            {
                $this->ppt = new PhpPresentation;
                $this->ppt->removeSlideByIndex(0);
                $this->setConfiguration(['outputFormat' => 'odp']);
                $this->slide($lines, 40);
                $this->createODPFile($outputPath);
            }
        };
        $tempFile = tempnam(sys_get_temp_dir(), 'psalm-odp-test-');

        $sheet->exportOdpSlideForTest(['First line', "\tIndented line"], $tempFile);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($tempFile));
        $contentXml = $zip->getFromName('content.xml');
        $zip->close();
        unlink($tempFile);

        $this->assertIsString($contentXml);
        $this->assertStringContainsString("\u{00A0}\u{00A0}Indented line", $contentXml);
    }

    /** PPT and ODP event lists omit the automatic possible-baptism description. */
    public function test_ad_list_description_excludes_possible_baptism_label(): void
    {
        $sheet = new class extends SongPPTLiturgySheet
        {
            /**
             * @param  array<string, int|string>  $layout
             * @return array<string, mixed>
             */
            public function eventListEntryLayoutForTest(object $event, array $layout): array
            {
                return $this->getEventListEntryLayout($event, $layout);
            }
        };
        $service = new class
        {
            public string $locationTextWithCity = 'Musterkirche, Musterstadt';

            public function titleText(bool $short = true): string
            {
                return 'Gottesdienst';
            }
        };
        $eventDetails = new class
        {
            /**
             * @param  array<int, string>  $exclude
             */
            public function descriptionText(array $exclude = []): string
            {
                return in_array('baptism', $exclude, true)
                    ? 'mit Abendmahl'
                    : 'möglicher Taufgottesdienst; mit Abendmahl';
            }
        };
        $event = new class($service, $eventDetails)
        {
            public object $service;

            public object $event;

            public function __construct(object $service, object $event)
            {
                $this->service = $service;
                $this->event = $event;
            }

            public function getAdText(string $channel, string $default = ''): string
            {
                return $default;
            }
        };

        $entry = $sheet->eventListEntryLayoutForTest($event, [
            'titleFontTtfPath' => resource_path('fonts/Sarabun-SemiBold.ttf'),
            'listFontSize' => 20,
            'maxTitleWidthPx' => 1000,
            'descriptionFontTtfPath' => resource_path('fonts/Sarabun-Regular.ttf'),
            'descriptionFontSize' => 18,
        ]);

        $this->assertSame('mit Abendmahl', implode(' ', $entry['descriptionLines']));
    }

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

    public function test_separator_slide_configuration_defaults_to_empty_slides(): void
    {
        $sheet = new SongPPTLiturgySheet;

        $this->assertSame('empty', $sheet->getConfiguration()['slideBetweenElements']);
    }

    public function test_separator_slide_configuration_accepts_all_modes(): void
    {
        $sheet = new class extends SongPPTLiturgySheet
        {
            public function separatorModeForTest(): string
            {
                return $this->getInterElementSlideMode();
            }
        };

        foreach (['none', 'empty', 'title'] as $mode) {
            $sheet->setConfiguration(['slideBetweenElements' => $mode]);

            $this->assertSame($mode, $sheet->separatorModeForTest());
        }
    }

    public function test_title_slide_data_uses_configured_text_and_service_attachment(): void
    {
        $sheet = new class extends SongPPTLiturgySheet
        {
            /**
             * @return array<string, mixed>
             */
            public function titleSlideDataForTest(Service $service): array
            {
                return $this->getTitleSlideData($service);
            }
        };

        $service = Service::factory()->make();
        $attachment = new Attachment([
            'file' => 'attachments/title-slide.jpg',
            'cut' => 'ppt-title-slide',
        ]);
        $attachment->id = 42;
        $service->setRelation('attachments', collect([$attachment]));
        $sheet->setConfiguration([
            'titleSlideTitle' => 'Gottesdienst am Sonntag',
            'titleSlideSubtitle' => 'Mit Prädikant Heinrich Lang',
            'titleSlideAttachmentId' => 42,
        ]);

        $this->assertSame([
            'title' => 'Gottesdienst am Sonntag',
            'subtitle' => 'Mit Prädikant Heinrich Lang',
            'image' => StoragePath::for('attachments/title-slide.jpg'),
        ], $sheet->titleSlideDataForTest($service));
    }

    /**
     * The sermon title and all pastors are used in the title slide defaults.
     */
    public function test_title_slide_data_uses_sermon_defaults_and_lists_all_pastors(): void
    {
        $sheet = new class extends SongPPTLiturgySheet
        {
            /**
             * @return array<string, mixed>
             */
            public function titleSlideDataForTest(Service $service): array
            {
                return $this->getTitleSlideData($service);
            }
        };

        $service = Service::factory()->make(['date' => '2026-11-08 10:30:00']);
        $service->setRelation('sermon', new Sermon([
            'title' => 'Hoffnung für alle',
            'reference' => 'Römer 15,13',
        ]));
        $service->setRelation('pastors', collect([
            new User(['first_name' => 'Anna', 'last_name' => 'Muster']),
            new User(['first_name' => 'Bernd', 'last_name' => 'Beispiel']),
            new User(['first_name' => 'Clara', 'last_name' => 'Test']),
        ]));
        $sheet->setConfiguration([
            'titleSlideTitle' => '',
            'titleSlideSubtitle' => '',
        ]);

        $this->assertSame([
            'title' => 'Hoffnung für alle',
            'subtitle' => 'mit Anna Muster, Bernd Beispiel und Clara Test zu Römer 15,13',
            'image' => null,
        ], $sheet->titleSlideDataForTest($service));
    }

    /**
     * The service weekday and date are used when no sermon title exists.
     */
    public function test_title_slide_data_uses_service_date_when_sermon_title_is_missing(): void
    {
        $sheet = new class extends SongPPTLiturgySheet
        {
            /**
             * @return array<string, mixed>
             */
            public function titleSlideDataForTest(Service $service): array
            {
                return $this->getTitleSlideData($service);
            }
        };

        $service = Service::factory()->make(['date' => '2026-11-08 10:30:00']);
        $service->setRelation('sermon', new Sermon([
            'title' => '',
            'reference' => 'Römer 15,13',
        ]));
        $service->setRelation('pastors', collect([
            new User(['first_name' => 'Anna', 'last_name' => 'Muster']),
        ]));
        $sheet->setConfiguration([
            'titleSlideTitle' => '',
            'titleSlideSubtitle' => '',
        ]);

        $this->assertSame([
            'title' => 'Gottesdienst am Sonntag, 08.11.2026',
            'subtitle' => 'mit Anna Muster',
            'image' => null,
        ], $sheet->titleSlideDataForTest($service));
    }

    public function test_title_slide_data_without_attachment_keeps_configured_text(): void
    {
        $sheet = new class extends SongPPTLiturgySheet
        {
            /**
             * @return array<string, mixed>
             */
            public function titleSlideDataForTest(Service $service): array
            {
                return $this->getTitleSlideData($service);
            }
        };

        $sheet->setConfiguration([
            'titleSlideTitle' => 'Gottesdienst',
            'titleSlideSubtitle' => 'Ohne Hintergrundbild',
        ]);

        $this->assertSame([
            'title' => 'Gottesdienst',
            'subtitle' => 'Ohne Hintergrundbild',
            'image' => null,
        ], $sheet->titleSlideDataForTest(Service::factory()->make()));
    }

    public function test_materialized_storage_path_uses_shared_framework_cache(): void
    {
        $path = 'attachments/title-slide.jpg';

        $this->assertSame(
            storage_path('framework/cache/'.sha1($path)),
            StoragePath::cachePath($path)
        );
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
