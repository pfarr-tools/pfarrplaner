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
 *
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
use App\Liturgy\ItemHelpers\FreetextItemHelper;
use App\Liturgy\ItemHelpers\PsalmItemHelper;
use App\Liturgy\ItemHelpers\ReadingItemHelper;
use App\Liturgy\ItemHelpers\SongItemHelper;
use App\Liturgy\Music\ABCMusic;
use App\Liturgy\Replacement\Replacement;
use App\Models\Liturgy\Item;
use App\Models\Liturgy\Song;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpWord\Shared\Converter;

/** Liturgy sheet rendering the song sheet. */
class SongSheetLiturgySheet extends AbstractLiturgySheet
{
    protected $title = 'Liedblatt';

    protected $icon = 'fa fa-file-word';

    /** @var Service */
    protected $service = null;

    protected $extension = 'docx';

    protected $configurationPage = 'Liturgy/LiturgySheets/SongSheetConfiguration';

    protected $configurationComponent = 'SongSheetLiturgySheetConfiguration';

    protected $defaultConfig = [
        'renderMusic' => false,
        'mergeVerses' => false,
    ];

    /**
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return mixed
     */
    public function render(Service $service)
    {
        $this->service = $service;

        $doc = new DefaultWordDocument;
        $this->setProperties($doc);

        $doc->renderServiceTitleHeading($service);

        foreach ($service->liturgyBlocks as $block) {
            foreach ($block->items as $item) {
                if (method_exists($this, ($method = 'render'.ucfirst($item->data_type.'Item')))) {
                    $this->$method($doc, $item);
                }
            }
        }

        return $doc->sendToBrowser($this->getFileName($service));
    }

    /**
     * Set the metadata of the generated Word document.
     *
     * @param  DefaultWordDocument  $doc  Document whose properties are set.
     */
    protected function setProperties(DefaultWordDocument $doc): void
    {
        $properties = $doc->getPhpWord()->getDocInfo();
        $user = Auth::user();
        $userName = $user?->fullName() ?? '';
        $properties->setCreator($userName);
        $properties->setCompany($user->office ?? '');
        $properties->setTitle($this->getFileTitle());
        $properties->setDescription($this->getFileTitle());
        $properties->setCategory('Gottesdienste');
        $properties->setLastModifiedBy($userName);
        $properties->setSubject('Liedblatt');
    }

    public function getFileTitle(): string
    {
        return 'Liedblatt';
    }

    /**
     * Render the handout text of a free text item into the document.
     *
     * @param  DefaultWordDocument  $doc  Document receiving the rendered item.
     * @param  Item  $item  Liturgy item to render.
     */
    protected function renderFreeTextItem(DefaultWordDocument $doc, Item $item): void
    {
        if (! ($item->data['handoutText'] ?? false)) {
            return;
        }
        /** @var FreetextItemHelper $helper */
        $helper = $item->getHelper();
        $helper->setField('handoutText');
        if (! ($item->data['handoutSuppressTitle'] ?? false)) {
            $doc->getSection()->addTitle($item->title, 3);
        }
        $doc->renderNormalText(Replacement::replaceAll($helper->getText(), $this->service));
    }

    /**
     * Render a reading item into the document when it is marked for handouts.
     *
     * @param  DefaultWordDocument  $doc  Document receiving the rendered item.
     * @param  Item  $item  Liturgy item to render.
     */
    protected function renderReadingItem(DefaultWordDocument $doc, Item $item): void
    {
        if (! isset($item->data['reference'])) {
            return;
        }
        if (! ($item->data['showInHandouts'] ?? false)) {
            return;
        }
        $helper = new ReadingItemHelper($item);
        $helper->renderToWordDocument($doc, true);
    }

    /**
     * Render a Psalm item into the document when a Psalm text is available.
     *
     * @param  DefaultWordDocument  $doc  Document receiving the rendered item.
     * @param  Item  $item  Liturgy item to render.
     */
    protected function renderPsalmItem(DefaultWordDocument $doc, Item $item): void
    {
        $psalm = $item->data['psalm'] ?? null;
        $text = is_array($psalm) ? ($psalm['text'] ?? null) : null;
        if (! is_scalar($text) || ! $text) {
            return;
        }
        /** @var PsalmItemHelper $helper */
        $helper = $item->getHelper();
        $doc->getSection()->addTitle($helper->getTitleText(), 3);
        $doc->renderNormalText((string) $text);
    }

    /**
     * Render a song item, including its verses or music notation, into the document.
     *
     * @param  DefaultWordDocument  $doc  Document receiving the rendered item.
     * @param  Item  $item  Liturgy item to render.
     */
    protected function renderSongItem(DefaultWordDocument $doc, Item $item): void
    {
        $songData = $item->data['song'] ?? null;
        if (! is_array($songData)) {
            return;
        }
        if (($songData['id'] ?? null) == -1) {
            return;
        }
        /** @var SongItemHelper $helper */
        $helper = $item->getHelper();
        $doc->getSection()->addTitle($helper->getTitleText(), 3);
        $songDetails = $songData['song'] ?? null;
        $songDetails = is_array($songDetails) ? $songDetails : [];
        $copyrights = $songDetails['copyrights'] ?? null;
        if (is_scalar($copyrights) && $copyrights) {
            $doc->renderNormalText((string) $copyrights, ['size' => 8]);
        }

        if ($this->config['renderMusic'] && isset($songDetails['notation'])) {
            $songId = $songData['song_id'] ?? null;
            $song = Song::findOrFail(is_scalar($songId) ? (int) $songId : 0);
            /** @var array<int, string>|string $verses */
            $verses = $item->data['verses'] ?? '';
            $mergeVerses = $this->config['mergeVerses'] ?? false;
            $images = ABCMusic::images(
                $song,
                $verses,
                ABCMusic::COLORS_NORMAL,
                is_scalar($mergeVerses) && (bool) $mergeVerses
            );
            foreach ($images as $image) {
                $doc->getSection()->addImage($image, ['width' => Converter::cmToPoint(17.5)]);
            }
        } else {
            $refrain = $songDetails['refrain'] ?? '';
            $refrain = is_scalar($refrain) ? (string) $refrain : '';
            foreach ($helper->getActiveVerses() as $verse) {
                if ($verse['refrain_before']) {
                    $doc->renderNormalText($refrain, ['italic' => true]);
                }
                $number = $verse['number'] ?? '';
                $verseText = $verse['text'] ?? '';
                $doc->renderNormalText(
                    (is_scalar($number) ? (string) $number : '').'. '
                    .(is_scalar($verseText) ? (string) $verseText : '')
                );
                if ($verse['refrain_after']) {
                    $doc->renderNormalText($refrain, ['italic' => true]);
                }
            }
        }

    }
}
