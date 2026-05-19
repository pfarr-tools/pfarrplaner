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

namespace Tests\Browser\Stage2;

use App\Liturgy\LiturgySheets\LiturgySheets;
use App\Models\Liturgy\Block;
use App\Models\Liturgy\Item;
use App\Models\Location;
use App\Models\Places\City;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Laravel\Dusk\Browser;
use Tests\AbstractPageLoadTest;
use Tests\Browser\Pages\LiturgyEditorPage;

class LiturgyEditorFeatureTest extends AbstractPageLoadTest
{
    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $city = City::factory()->create([
            'name' => 'Musterstadt Mitte',
        ]);
        $location = Location::factory()->create([
            'city_id' => $city->id,
            'name' => 'Stadtkirche',
            'default_time' => '10:30',
        ]);

        $this->service = Service::factory()->create([
            'date' => Carbon::parse('2026-05-17 08:30:00'),
            'time' => '10:30',
            'title' => 'Predigtgottesdienst',
            'description' => 'Predigtgottesdienst mit Beispiel-Liturgie',
            'location_id' => $location->id,
            'city_id' => $city->id,
            'eucharist' => 0,
            'baptism' => 0,
            'cc' => 0,
        ]);

        $this->createPredigtgottesdienstLiturgy($this->service);
    }

    public function testLiturgyEditorRendersWithoutError(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(new LiturgyEditorPage($this->service->slug))
                    ->assertDontSee('500')
                    ->assertDontSee('Whoops');
        });
    }

    public function testLiturgyEditorLinksBackToServiceEditor(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(new LiturgyEditorPage($this->service->slug))
                    ->waitFor('#app', 10)
                    ->assertPresent('a.btn-light');
        });
    }

    public function testPdfLinkIsRendered(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(new LiturgyEditorPage($this->service->slug))
                    ->waitFor('#app', 10)
                    ->assertPresent('button[title="Dokumente herunterladen"]');
        });
    }

    public function testSettingsRoundedTimesTogglePresent(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(new LiturgyEditorPage($this->service->slug))
                    ->waitFor('#app', 10)
                    ->assertSee('Zeitangaben runden');
        });
    }

    public function testEveryDownloadableLiturgySheetCanBeDownloadedFromTheDropdown(): void
    {
        $downloadableSheets = collect(LiturgySheets::all())
            ->filter(fn (array $sheet) => !($sheet['isNotAFile'] ?? false))
            ->values()
            ->all();

        $this->browse(function (Browser $browser) use ($downloadableSheets) {
            $browser->loginAs($this->superAdminUser, 'web')
                    ->visit(new LiturgyEditorPage($this->service->slug))
                    ->waitFor('#app', 10);

            foreach ($downloadableSheets as $sheet) {
                $this->downloadSheetAndAssertFile($browser, $sheet);
            }
        });
    }

    protected function downloadSheetAndAssertFile(Browser $browser, array $sheet): void
    {
        $before = $this->downloadedFiles();

        $browser->press('Herunterladen')
                ->waitFor('.dropdown-menu.show', 5)
                ->clickLink($sheet['title']);

        if ($sheet['configurationComponent']) {
            $browser->waitFor('.modal.show', 5)
                    ->assertSee($sheet['title'] . ' herunterladen')
                    ->press('Herunterladen');
        }

        $this->waitForNewDownload($browser, $before, $sheet['extension']);

        if ($sheet['configurationComponent']) {
            $browser->waitUntilMissing('.modal.show', 5);
        }

        $browser->visit(route('liturgy.editor', $this->service->slug))
                ->waitFor('#app', 10);
    }

    protected function waitForNewDownload(Browser $browser, array $before, string $extension): void
    {
        $browser->waitUsing(20, 250, function () use ($before, $extension) {
            $after = $this->downloadedFiles();
            $newFiles = array_values(array_diff($after, $before));

            if (empty($newFiles)) {
                return false;
            }

            foreach ($newFiles as $file) {
                if (str_ends_with($file, '.crdownload')) {
                    return false;
                }
            }

            return collect($newFiles)->contains(function (string $file) use ($extension) {
                return str_ends_with(strtolower($file), '.' . strtolower($extension));
            });
        }, 'Expected a new .' . $extension . ' download, but none was completed.');
    }

    protected function downloadedFiles(): array
    {
        if (!File::exists($this->downloadDirectory)) {
            return [];
        }

        return collect(File::files($this->downloadDirectory))
            ->map(fn ($file) => $file->getFilename())
            ->sort()
            ->values()
            ->all();
    }

    protected function createPredigtgottesdienstLiturgy(Service $service): void
    {
        $blocks = [
            'Eröffnung und Anrufung' => [
                ['freetext', 'Glockengeläut', ['description' => '<p>Die Glocken laden zum Gottesdienst ein.</p>', 'duration' => '02:00', 'responsible' => ['ministry:sacristans']]],
                ['song', 'Eingangslied', [
                    'song' => [
                        'id' => 101,
                        'code' => 'EG',
                        'reference' => '170',
                        'song' => [
                            'title' => 'Komm, Herr, segne uns',
                            'verses' => [
                                ['number' => '1', 'text' => 'Komm, Herr, segne uns'],
                                ['number' => '2', 'text' => 'Keiner kann allein Segen sich bewahren'],
                            ],
                        ],
                    ],
                    'verses' => '1-2',
                    'responsible' => ['ministry:organists'],
                ]],
                ['freetext', 'Votum', ['description' => '<p>Im Namen Gottes des Vaters und des Sohnes und des Heiligen Geistes.</p>', 'text' => '<p>Im Namen Gottes des Vaters und des Sohnes und des Heiligen Geistes.</p>', 'slides' => 'Im Namen Gottes des Vaters und des Sohnes und des Heiligen Geistes.', 'handoutText' => '<p>Im Namen Gottes des Vaters und des Sohnes und des Heiligen Geistes.</p>', 'responsible' => ['ministry:pastors']]],
                ['psalm', 'Psalmgebet', ['psalm' => ['title' => 'Psalm 36', 'reference' => 'Psalm 36', 'songbook_abbreviation' => 'EG', 'text' => 'Herr, deine Güte reicht, so weit der Himmel ist.'], 'responsible' => ['ministry:pastors']]],
            ],
            'Verkündigung und Bekenntnis' => [
                ['reading', 'Schriftlesung', ['intro' => 'Die Lesung steht im Evangelium nach Matthäus.', 'reference' => 'Eigener Text', 'customText' => 'Ihr seid das Licht der Welt. Es kann die Stadt, die auf einem Berge liegt, nicht verborgen sein.', 'customSource' => 'Mt 5,14', 'showInHandouts' => 1, 'responsible' => ['ministry:pastors']]],
                ['song', 'Wochenlied', [
                    'song' => [
                        'id' => 102,
                        'code' => 'EG',
                        'reference' => '295',
                        'song' => [
                            'title' => 'Wohl denen, die da wandeln',
                            'verses' => [
                                ['number' => '1', 'text' => 'Wohl denen, die da wandeln'],
                                ['number' => '3', 'text' => 'Mein Herz hängt treu und feste'],
                            ],
                        ],
                    ],
                    'verses' => '1+3',
                    'responsible' => ['ministry:organists'],
                ]],
                ['freetext', 'Fürbitten und Vaterunser', ['description' => '<p>Fürbitten für Gemeinde, Kirche und Welt. Gemeinsam beten wir das Vaterunser.</p>', 'text' => '<p>Gemeinsam beten wir das Vaterunser.</p>', 'slides' => "Vater unser im Himmel\n---\nDenn dein ist das Reich", 'handoutText' => '<p>Gemeinsam beten wir das Vaterunser.</p>', 'responsible' => ['ministry:pastors']]],
            ],
        ];

        $blockIndex = 0;
        foreach ($blocks as $title => $items) {
            $block = Block::create([
                'service_id' => $service->id,
                'title' => $title,
                'instructions' => '',
                'sortable' => $blockIndex++,
            ]);

            foreach ($items as $itemIndex => [$type, $itemTitle, $data]) {
                $item = new Item([
                    'liturgy_block_id' => $block->id,
                    'title' => $itemTitle,
                    'instructions' => '',
                    'data_type' => $type,
                    'sortable' => $itemIndex,
                ]);
                $item->data = array_merge([
                    'responsible' => [],
                    'duration' => '',
                    'speed' => '',
                ], $data);
                $item->save();
            }
        }
    }
}
