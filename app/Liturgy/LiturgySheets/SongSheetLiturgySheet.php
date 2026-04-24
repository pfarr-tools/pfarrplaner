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
 *
 * Sponsored by: Evangelischer Kirchenbezirk Balingen, https://www.kirchenbezirk-balingen.de
 *
 * Pfarrplaner is based on the Laravel framework (https://laravel.com).
 * This file may contain code created by Laravel's scaffolding functions.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Liturgy\LiturgySheets;


use App\Documents\Word\DefaultWordDocument;
use App\Liturgy\ItemHelpers\PsalmItemHelper;
use App\Liturgy\ItemHelpers\ReadingItemHelper;
use App\Liturgy\ItemHelpers\SongItemHelper;
use App\Liturgy\Music\ABCMusic;
use App\Liturgy\Replacement\Replacement;
use App\Models\Liturgy\Item;
use App\Models\Liturgy\Song;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Shared\Converter;

class SongSheetLiturgySheet extends AbstractLiturgySheet
{
    protected $title = 'Liedblatt';
    protected $icon = 'fa fa-file-word';
    protected $service = null;
    protected $extension = 'docx';

    protected $configurationPage = 'Liturgy/LiturgySheets/SongSheetConfiguration';
    protected $configurationComponent = 'SongSheetLiturgySheetConfiguration';

    protected $defaultConfig = [
        'renderMusic' => false,
        'mergeVerses' => false,
    ];


    public function __construct()
    {
        parent::__construct();
    }

    public function render(Service $service)
    {
        $this->service = $service;

        $doc = new DefaultWordDocument();
        $this->setProperties($doc);

        $doc->renderServiceTitleHeading($service);

        foreach ($service->liturgyBlocks as $block) {
            foreach ($block->items as $item) {
                if (method_exists($this, ($method = 'render' . ucfirst($item->data_type . 'Item')))) {
                    $this->$method($doc, $item);
                }
            }
        }
        return $doc->sendToBrowser($this->getFileName($service));
    }

    protected function setProperties (DefaultWordDocument $doc) {
        $properties = $doc->getPhpWord()->getDocInfo();
        $properties->setCreator(Auth::user()->fullName());
        $properties->setCompany(Auth::user()->office ?? '');
        $properties->setTitle($this->getFileTitle());
        $properties->setDescription($this->getFileTitle());
        $properties->setCategory('Gottesdienste');
        $properties->setLastModifiedBy(Auth::user()->fullName());
        $properties->setSubject('Liedblatt');
    }



    public function getFileTitle(): string
    {
        return 'Liedblatt';
    }

    protected function renderFreeTextItem(DefaultWordDocument $doc, Item $item)
    {
        if (!($item->data['handoutText'] ?? false)) {
            return;
        }
        $helper = $item->getHelper();
        $helper->setField('handoutText');
        if (!($item->data['handoutSuppressTitle'] ?? false)) {
            $doc->getSection()->addTitle($item->title,3);
        }
        $doc->renderNormalText(Replacement::replaceAll($helper->getText(), $this->service));
    }

    protected function renderReadingItem(DefaultWordDocument $doc, Item $item)
    {
        if(!isset($item->data['reference'])) return;
        if(!($item->data['showInHandouts'] ?? false)) return;
        $helper = new ReadingItemHelper($item);
        $helper->renderToWordDocument($doc, true);
    }

    protected function renderPsalmItem(DefaultWordDocument $doc, Item $item)
    {
        if (!isset($item->data['psalm'])) return;
        if (!$item->data['psalm']['text']) return;
        /** @var PsalmItemHelper $helper */
        $helper = $item->getHelper();
        $doc->getSection()->addTitle($helper->getTitleText(),3);
        $doc->renderNormalText($item->data['psalm']['text']);
    }

    protected function renderSongItem(DefaultWordDocument $doc, Item $item)
    {
        if (!isset($item->data['song'])) return;
        if (null === $item->data['song']) return;
        if ($item->data['song']['id'] == -1) return;
        /** @var SongItemHelper $helper */
        $helper = $item->getHelper();
        $doc->getSection()->addTitle($helper->getTitleText(),3);
        if ($item->data['song']['song']['copyrights'] ?? '') {
            $doc->renderNormalText($item->data['song']['song']['copyrights'], ['size' => 8]);
        }

        if ($this->config['renderMusic'] && isset($item->data['song']['song']['notation'])) {
            $song = Song::find($item->data['song']['song_id']);
            $images = ABCMusic::images($song, $item->data['verses'], ABCMusic::COLORS_NORMAL, $this->config['mergeVerses']);
            foreach ($images as $image) {
                $doc->getSection()->addImage($image, ['width' => Converter::cmToPoint(17.5)]);
            }
        } else {
            foreach ($helper->getActiveVerses() as $verse) {
                if ($verse['refrain_before']) {
                    $doc->renderNormalText($item->data['song']['song']['refrain'], ['italic' => true]);
                }
                $doc->renderNormalText($verse['number'].'. '.$verse['text']);
                if ($verse['refrain_after']) {
                    $doc->renderNormalText($item->data['song']['song']['refrain'], ['italic' => true]);
                }
            }
        }

    }

}
