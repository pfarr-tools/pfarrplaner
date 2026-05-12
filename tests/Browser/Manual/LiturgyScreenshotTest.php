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

use App\Models\Liturgy\Block;
use App\Models\Liturgy\Item;
use App\Models\Location;
use App\Models\Places\City;
use App\Models\Service;
use Carbon\Carbon;
use Laravel\Dusk\Browser;

class LiturgyScreenshotTest extends ManualScreenshotTestCase
{
    protected Service $service;

    protected Service $template;

    protected function setUp(): void
    {
        parent::setUp();

        $city = City::factory()->create([
            'name' => 'Musterstadt Mitte',
            'konfiapp_apikey' => 'konfiapp-beispielschluessel',
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
        $this->template = Service::factory()->create([
            'date' => Carbon::parse('1978-03-05 09:30:00'),
            'time' => '10:30',
            'title' => 'Württembergischer Predigtgottesdienst',
            'description' => 'Vorlage für einen Predigtgottesdienst',
            'location_id' => $location->id,
            'city_id' => $city->id,
        ]);

        $this->createPredigtgottesdienstLiturgy($this->service);
        $this->createPredigtgottesdienstLiturgy($this->template);
    }

    protected function createPredigtgottesdienstLiturgy(Service $service): void
    {
        $blocks = [
            'Eröffnung und Anrufung' => [
                ['freetext', 'Glockengeläut', ['description' => '<p>Die Glocken laden zum Gottesdienst ein.</p>', 'duration' => '02:00', 'responsible' => ['ministry:sacristans']]],
                ['freetext', 'Musik zum Eingang', ['description' => '<p>Orgelvorspiel und stilles Gebet.</p>', 'responsible' => ['ministry:organists']]],
                ['song', 'Eingangslied', ['song' => ['code' => 'EG', 'reference' => '170', 'song' => ['title' => 'Komm, Herr, segne uns', 'verses' => [['number' => '1', 'text' => 'Komm, Herr, segne uns'], ['number' => '2', 'text' => 'Keiner kann allein Segen sich bewahren']]]], 'verses' => '1-2', 'responsible' => ['ministry:organists']]],
                ['freetext', 'Eingangswort', ['description' => '<p>Begrüßung der Gemeinde und kurze Einführung in den Sonntag.</p>', 'responsible' => ['ministry:pastors']]],
                ['freetext', 'Votum', ['description' => '<p>Im Namen Gottes des Vaters und des Sohnes und des Heiligen Geistes.</p>', 'text' => '<p>Im Namen Gottes des Vaters und des Sohnes und des Heiligen Geistes.</p>', 'slides' => 'Im Namen Gottes des Vaters und des Sohnes und des Heiligen Geistes.', 'responsible' => ['ministry:pastors']]],
                ['psalm', 'Psalmgebet', ['intro' => 'Wir beten mit Worten aus Psalm 36.', 'text' => 'Herr, deine Güte reicht, so weit der Himmel ist.', 'responsible' => ['ministry:pastors']]],
                ['freetext', 'Ehr sei dem Vater', ['description' => '<p>Ehr sei dem Vater und dem Sohn und dem Heiligen Geist.</p>', 'text' => '<p>Ehr sei dem Vater und dem Sohn und dem Heiligen Geist.</p>', 'responsible' => ['ministry:pastors']]],
                ['freetext', 'Eingangsgebet', ['description' => '<p>Gebet zum Eingang.</p>', 'text' => '<p>Gott, du bist mitten unter uns. Öffne unsere Herzen für dein Wort.</p>', 'responsible' => ['ministry:pastors']]],
                ['freetext', 'Stilles Gebet', ['description' => '<p>Die Gemeinde betet in der Stille.</p>', 'duration' => '01:00', 'responsible' => ['ministry:pastors']]],
            ],
            'Verkündigung und Bekenntnis' => [
                ['reading', 'Schriftlesung', ['intro' => 'Die Lesung steht im Evangelium nach Matthäus.', 'reference' => 'Eigener Text', 'customText' => 'Ihr seid das Licht der Welt. Es kann die Stadt, die auf einem Berge liegt, nicht verborgen sein.', 'customSource' => 'Mt 5,14', 'responsible' => ['ministry:pastors']]],
                ['song', 'Wochenlied', ['song' => ['code' => 'EG', 'reference' => '295', 'song' => ['title' => 'Wohl denen, die da wandeln', 'verses' => [['number' => '1', 'text' => 'Wohl denen, die da wandeln'], ['number' => '3', 'text' => 'Mein Herz hängt treu und feste']]]], 'verses' => '1+3', 'responsible' => ['ministry:organists']]],
                ['sermon', 'Predigttext und Predigt', ['responsible' => ['ministry:pastors']]],
                ['song', 'Lied nach der Predigt', ['song' => ['code' => 'EG', 'reference' => '365', 'song' => ['title' => 'Von Gott will ich nicht lassen', 'verses' => [['number' => '1', 'text' => 'Von Gott will ich nicht lassen'], ['number' => '5', 'text' => 'Drum bleib ich stets an ihm']]]], 'verses' => '1+5', 'responsible' => ['ministry:organists']]],
                ['freetext', 'Fürbitten und Vaterunser', ['description' => '<p>Fürbitten für Gemeinde, Kirche und Welt. Gemeinsam beten wir das Vaterunser.</p>', 'text' => '<p>Gemeinsam beten wir das Vaterunser.</p>', 'slides' => "Vater unser im Himmel\n---\nDenn dein ist das Reich", 'responsible' => ['ministry:pastors']]],
            ],
            'Sendung und Segen' => [
                ['song', 'Schlusslied', ['song' => ['code' => 'EG', 'reference' => '171', 'song' => ['title' => 'Bewahre uns, Gott', 'verses' => [['number' => '1', 'text' => 'Bewahre uns, Gott'], ['number' => '4', 'text' => 'Bewahre uns, Gott, behüte uns']]]], 'verses' => '1+4', 'responsible' => ['ministry:organists']]],
                ['freetext', 'Segen', ['description' => '<p>Der Herr segne dich und behüte dich.</p>', 'text' => '<p>Der Herr segne dich und behüte dich.</p>', 'slides' => 'Der Herr segne dich und behüte dich.', 'responsible' => ['ministry:pastors']]],
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

    public function testCaptureLiturgyEditor(): void
    {
        $this->browse(function (Browser $browser) {
            $this->captureManualScreenshot(
                $browser,
                route('liturgy.editor', $this->service->slug),
                'liturgie-editor',
                1000
            );
        });
    }

    public function testCaptureLiturgyTemplateList(): void
    {
        $this->browse(function (Browser $browser) {
            $this->captureManualScreenshot(
                $browser,
                route('template.index'),
                'liturgie-vorlagen',
                1000
            );
        });
    }

    public function testCaptureLiturgySheetConfigurations(): void
    {
        $this->browse(function (Browser $browser) {
            $this->captureManualScreenshot(
                $browser,
                route('liturgy.configure', ['service' => $this->service->slug, 'key' => 'FullText']),
                'liturgie-konfiguration-volltext',
                1000
            );
            $this->captureManualScreenshot(
                $browser,
                route('liturgy.configure', ['service' => $this->service->slug, 'key' => 'SongSheet']),
                'liturgie-konfiguration-liedblatt',
                1000
            );
            $this->captureManualScreenshot(
                $browser,
                route('liturgy.configure', ['service' => $this->service->slug, 'key' => 'SongBeamer']),
                'liturgie-konfiguration-songbeamer',
                1000
            );
        });
    }

    public function testCapturePowerPointConfigurationTabs(): void
    {
        $this->browse(function (Browser $browser) {
            $this->captureManualScreenshot(
                $browser,
                route('liturgy.configure', ['service' => $this->service->slug, 'key' => 'SongPPT']),
                'liturgie-powerpoint-farbschema',
                1000
            );
            $this->captureTab($browser, 'layout', 'liturgie-powerpoint-layout');
            $this->captureTab($browser, 'content', 'liturgie-powerpoint-inhalte');
            $this->captureTab($browser, 'ads', 'liturgie-powerpoint-werbung', 1000);
            $this->captureTab($browser, 'format', 'liturgie-powerpoint-ausgabeformat');
        });
    }
}
