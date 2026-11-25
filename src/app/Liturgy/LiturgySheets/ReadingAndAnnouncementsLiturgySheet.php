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
use App\Liturgy\Replacement\Replacement;
use App\Models\Liturgy\Item;
use App\Models\Service;
use App\Services\MinistryService;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\JcTable;

class ReadingAndAnnouncementsLiturgySheet extends AbstractLiturgySheet
{
    protected $title = 'Schriftlesung und Abkündigungen';

    protected $icon = 'fa fa-file-word';

    /** @var Service */
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

        $doc = new DefaultWordDocument;
        $this->setProperties($doc);
        $doc->getPhpWord()->addTableStyle(
            'Ablauf',
            [
                'borderSize' => 6,
                'borderColor' => '444444',
                'cellMargin' => 80,
                'alignment' => JcTable::START,
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
                    if (method_exists($this, ($method = 'render'.ucfirst($item->data_type.'Item')))) {
                        $this->$method($doc, $item);
                    }
                }
            }
        }

        return $doc->sendToBrowser($this->getFileName($service));
    }

    /**
     * @param  string  $category  Participant category.
     * @return string
     */
    public function renderParticipantRow($category)
    {
        $p = [];
        foreach ($this->service->participantsByCategory($category) as $person) {
            $p[] = $person->name;
        }

        return implode(', ', $p);
    }

    /**
     * @param  string  $recipient  Recipient filter.
     * @return void
     */
    protected function renderLiturgyTable(DefaultWordDocument $doc, $recipient = '')
    {
        foreach ($this->service->liturgyBlocks as $block) {
            $doc->getSection()->addTitle($block->title, 1);

            $table = $doc->getSection()->addTable('Ablauf');
            foreach ($block->items as $item) {
                if (method_exists($this, ($method = 'render'.ucfirst($item->data_type.'ItemRow')))) {
                    $table->addRow();
                    $this->$method($doc, $table, $item);
                }
            }
        }
    }

    protected function setProperties(DefaultWordDocument $doc): void
    {
        $properties = $doc->getPhpWord()->getDocInfo();
        $user = Auth::user();
        $userName = $user?->fullName() ?? '';
        $properties->setCreator($userName);
        $properties->setCompany($user->office ?? '');
        $properties->setTitle($this->getFileTitle());
        $properties->setDescription($this->getFileTitle().' ('.$this->title.')');
        $properties->setCategory('Gottesdienste');
        $properties->setLastModifiedBy($userName);
        $properties->setSubject('Ablauf und Texte');
    }

    public function getFileTitle(): string
    {
        return 'Gottesdienst'.(($this->service) && ($this->service->sermon) ? ' - '.$this->service->sermon->title : '');
    }

    /**
     * @param  string  $text  Row text.
     * @return void
     */
    protected function renderRow(Table $table, Item $item, $text)
    {
        $table->addCell((int) Converter::cmToTwip(4.3))->addText($item->title, ['bold' => true]);
        $table->addCell((int) Converter::cmToTwip(8.5))->addText($text);
        $this->renderRecipients($table, $item);
    }

    /**
     * @return void
     */
    protected function renderRecipients(Table $table, Item $item)
    {
        $cell = $table->addCell((int) Converter::cmToTwip(4.3));
        $run = $cell->addTextRun();
        $first = true;
        foreach ($item->recipients() as $recipient) {
            if (! $first) {
                $run->addText(', ');
            }
            $first = false;
        }
    }

    /**
     * @return void
     */
    protected function renderFreetextItemRow(DefaultWordDocument $doc, Table $table, Item $item)
    {
        $text = '';
        if ($item->data['description']) {
            /** @var FreetextItemHelper $helper */
            $helper = $item->getHelper();
            $text = Replacement::replaceAll($helper->getText(), $this->service);
            $text = (strpos($text, "\n") !== false) ? explode("\n", $text)[0] : substr($text, 0, 80).'...';
        }
        $this->renderRow($table, $item, $text);
    }

    /**
     * @return void
     */
    protected function renderSermonItemRow(DefaultWordDocument $doc, Table $table, Item $item)
    {
        $this->renderRow($table, $item, $this->service->sermon->title ?? '');
    }

    /**
     * @return void
     */
    protected function renderPsalmItemRow(DefaultWordDocument $doc, Table $table, Item $item)
    {
        /** @var PsalmItemHelper $helper */
        $helper = $item->getHelper();
        $this->renderRow($table, $item, $helper->getTitleText());
    }

    /**
     * @return void
     */
    protected function renderSongItemRow(DefaultWordDocument $doc, Table $table, Item $item)
    {
        if (! isset($item->data['song'])) {
            return;
        }
        /** @var SongItemHelper $helper */
        $helper = $item->getHelper();
        $this->renderRow($table, $item, $helper->getTitleText());
    }

    /**
     * @return void
     */
    protected function renderReadingItemRow(DefaultWordDocument $doc, Table $table, Item $item)
    {
        $this->renderRow($table, $item, $item->data['reference'] ?? '');
    }

    /**
     * @return void
     */
    protected function renderFreetextItem(DefaultWordDocument $doc, Item $item)
    {
        if (! $item->data['description']) {
            return;
        }
        if ((! str_contains(strtolower($item->title), 'bekanntgabe')) && (! str_contains(strtolower($item->title), 'abkündigung'))) {
            return;
        }
        $doc->getSection()->addTitle($item->title, 2);
        /** @var FreetextItemHelper $helper */
        $helper = $item->getHelper();
        $doc->renderNormalText(Replacement::replaceAll($helper->getText(), $this->service));
    }

    /**
     * @return void
     */
    protected function renderSermonItem(DefaultWordDocument $doc, Item $item) {}

    /**
     * @return void
     */
    protected function renderPsalmItem(DefaultWordDocument $doc, Item $item) {}

    /**
     * @return void
     */
    protected function renderSongItem(DefaultWordDocument $doc, Item $item) {}

    /**
     * @return void
     */
    protected function renderReadingItem(DefaultWordDocument $doc, Item $item)
    {
        if (! $item->data['reference']) {
            return;
        }

        $helper = new ReadingItemHelper($item);
        $helper->renderToWordDocument($doc, true);
    }
}
