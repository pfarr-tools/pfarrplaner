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
use App\Liturgy\Bible\BibleText;
use App\Liturgy\Bible\ReferenceParser;
use App\Liturgy\ItemHelpers\PsalmItemHelper;
use App\Liturgy\ItemHelpers\ReadingItemHelper;
use App\Liturgy\ItemHelpers\SongItemHelper;
use App\Liturgy\Replacement\Replacement;
use App\Models\Liturgy\Item;
use App\Services\MinistryService;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\JcTable;

class ReadingAndAnnouncementsLiturgySheet extends AbstractLiturgySheet
{
    protected $title = 'Schriftlesung und Abkündigungen';
    protected $icon = 'fa fa-file-word';

    /** @var Service $service */
    protected $service = null;
    protected $extension = 'docx';

    protected $defaultConfig = [
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
        $doc->getPhpWord()->addTableStyle(
            'Ablauf',
            [
                'borderSize' => 6,
                'borderColor' => '444444',
                'cellMargin' => 80,
                'alignment' => JcTable::START
            ]
        );

        $doc->renderServiceTitleHeading($this->service);

        foreach (['P', 'O', 'M', 'Schriftlesung'] as $category) {
            if (count($this->service->participantsByCategory($category))) {
                $doc->getSection()->addText(MinistryService::title($category).":\t"
                                            .$this->renderParticipantRow($category)."\n");
            }
        }


        $this->renderLiturgyTable($doc);
        // collect items for this user
        foreach ($this->service->liturgyBlocks as $block) {
            foreach ($block->items as $item) {
                if (in_array($item->data_type, ['reading', 'freeText'])) {
                    if (method_exists($this, ($method = 'render' . ucfirst($item->data_type . 'Item')))) {
                        $this->$method($doc, $item);
                    }
                }
            }
        }

        return $doc->sendToBrowser($this->getFileName($service));
    }

    public function renderParticipantRow($category)
    {
        $p = [];
        foreach ($this->service->participantsByCategory($category) as $person) $p[] = $person->name;
        return join(', ', $p);
    }


    protected function renderLiturgyTable(DefaultWordDocument $doc, $recipient = '')
    {
        foreach ($this->service->liturgyBlocks as $block) {
            $doc->getSection()->addTitle($block->title, 1);

            $table = $doc->getSection()->addTable('Ablauf');
            foreach ($block->items as $item) {
                if (method_exists($this, ($method = 'render' . ucfirst($item->data_type . 'ItemRow')))) {
                    $table->addRow();
                    $this->$method($doc, $table, $item);
                }
            }
        }
    }

    protected function setProperties(DefaultWordDocument $doc)
    {
        $properties = $doc->getPhpWord()->getDocInfo();
        $properties->setCreator(Auth::user()->name);
        $properties->setCompany(Auth::user()->office ?? '');
        $properties->setTitle($this->getFileTitle());
        $properties->setDescription($this->getFileTitle() . ' (' . $this->title . ')');
        $properties->setCategory('Gottesdienste');
        $properties->setLastModifiedBy(Auth::user()->name);
        $properties->setSubject('Ablauf und Texte');
    }

    public function getFileTitle(): string
    {
        return 'Gottesdienst' . (($this->service) && ($this->service->sermon) ? ' - ' . $this->service->sermon->title : '');
    }

    protected function renderRow(Table $table, Item $item, $text)
    {
        $table->addCell(Converter::cmToTwip(4.3))->addText($item->title, ['bold' => true]);
        $table->addCell(Converter::cmToTwip(8.5))->addText($text);
        $this->renderRecipients($table, $item);
    }

    protected function renderRecipients(Table $table, Item $item)
    {
        $cell = $table->addCell(Converter::cmToTwip(4.3));
        $run = $cell->addTextRun();
        $first = true;
        foreach ($item->recipients() as $recipient) {
            if (!$first) {
                $run->addText(', ');
            }
            $first = false;
        }
    }

    /**
     * @param DefaultWordDocument $doc
     * @param Table $table
     * @param Item $item
     */
    protected function renderFreetextItemRow(DefaultWordDocument $doc, Table $table, Item $item)
    {
        $text = '';
        if ($item->data['description']) {
            $text = Replacement::replaceAll($item->getHelper()->getText(), $this->service);
            $text = (false !== strpos($text, "\n")) ? explode("\n", $text)[0] : substr($text, 0, 80) . '...';
        }
        $this->renderRow($table, $item, $text);
    }

    protected function renderSermonItemRow(DefaultWordDocument $doc, Table $table, Item $item)
    {
        $this->renderRow($table, $item, $this->service->sermon_id ? $this->service->sermon->title : '');
    }

    protected function renderPsalmItemRow(DefaultWordDocument $doc, Table $table, Item $item)
    {
        /** @var PsalmItemHelper $helper */
        $helper = $item->getHelper();
        $this->renderRow($table, $item, $helper->getTitleText());
    }

    protected function renderSongItemRow(DefaultWordDocument $doc, Table $table, Item $item)
    {
        if (!isset($item->data['song'])) {
            return;
        }
        /** @var SongItemHelper $helper */
        $helper = $item->getHelper();
        $this->renderRow($table, $item, $helper->getTitleText());
    }

    protected function renderReadingItemRow(DefaultWordDocument $doc, Table $table, Item $item)
    {
        $this->renderRow($table, $item, $item->data['reference'] ?? '');
    }

    protected function renderFreetextItem(DefaultWordDocument $doc, Item $item)
    {
        if (!$item->data['description']) {
            return;
        }
        if ((!str_contains(strtolower($item->title), 'bekanntgabe')) && (!str_contains(strtolower($item->title), 'abkündigung'))) return;
        $doc->getSection()->addTitle($item->title, 2);
        $doc->renderNormalText(Replacement::replaceAll($item->getHelper()->getText(), $this->service));
    }

    protected function renderSermonItem(DefaultWordDocument $doc, Item $item)
    {
    }

    protected function renderPsalmItem(DefaultWordDocument $doc, Item $item)
    {
    }

    protected function renderSongItem(DefaultWordDocument $doc, Item $item)
    {
    }

    protected function renderReadingItem(DefaultWordDocument $doc, Item $item)
    {
        if (!$item->data['reference']) {
            return;
        }

        $helper = new ReadingItemHelper($item);
        $helper->renderToWordDocument($doc, true);
    }


}
