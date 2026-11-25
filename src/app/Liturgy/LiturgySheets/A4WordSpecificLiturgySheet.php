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
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\Shared\Html;
use PhpOffice\PhpWord\SimpleType\JcTable;

/** A4 liturgy sheet with service order and texts. */
class A4WordSpecificLiturgySheet extends AbstractLiturgySheet
{
    protected $title = 'Ablaufplan und Texte (DIN A4)';

    protected $icon = 'fa fa-file-word';

    /** @var Service */
    protected $service = null;

    protected $extension = 'docx';

    protected $configurationPage = 'Liturgy/LiturgySheets/A4WordSpecificLiturgySheetConfiguration';

    protected $configurationComponent = 'A4WordSpecificLiturgySheetConfiguration';

    /** @var string */
    protected $currentRecipient = '';

    protected $defaultConfig = [
        'includeTexts' => 1,
        'includeTable' => 1,
        'includeSongTexts' => 1,
        'includeFullReadings' => 1,
        'includeAdditionalHeaders' => 1,
        'includeAllTexts' => 1,
        'recipients' => [],
    ];

    /**
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Render the configured Word document for a service and its recipients.
     *
     * @param  Service  $service  Service whose liturgy is exported.
     * @return mixed Download response produced by the Word document.
     */
    public function render(Service $service)
    {
        if (! $this->config['includeTexts']) {
            $this->config['includeAllTexts']
                = $this->config['includeAdditionalHeaders']
                = $this->config['includeSongTexts']
                = $this->config['includeFullReadings']
                = 0;
        }

        if ($this->config['includeAllTexts']) {
            $this->config['includeAdditionalHeaders'] = 1;
        }

        $this->service = $service;

        $doc = new DefaultWordDocument;
        $this->setProperties($doc);
        $doc->getPhpWord()->addTableStyle('Ablauf', ['borderSize' => 6, 'borderColor' => '444444', 'cellMargin' => 80, 'alignment' => JcTable::START]);

        $recipientsConfig = $this->config['recipients'] ?? [];
        $recipients = $this->normalizeRecipients(array_values(is_array($recipientsConfig) ? $recipientsConfig : []));

        if (count($recipients)) {
            $first = true;
            foreach ($recipients as $recipient) {
                $this->currentRecipient = $recipient;
                $doc->setRecipient($recipient);
                if (! $first) {
                    $doc->getSection()->addPageBreak();
                }
                $first = false;
                $this->renderLiturgyTable($doc, $recipient);

                if ($this->config['includeTexts']) {
                    // collect items for this user
                    $items = [];
                    $hasHeader = false;
                    foreach ($this->service->liturgyBlocks as $block) {
                        foreach ($block->items as $item) {
                            if ($this->config['includeAllTexts'] || in_array(
                                $recipient,
                                $item->recipients()
                            ) || $this->config['includeAdditionalHeaders']) {
                                if (! $hasHeader) {
                                    $doc->getSection()->addTitle('Texte für '.$recipient, 1);
                                    $hasHeader = true;
                                }
                                $doc->getSection()->addTitle($item->title, 2);
                                if ($this->config['includeAllTexts'] && in_array($recipient, $item->recipients())) {
                                    $doc->getSection()->addText($recipient, ['italic' => true, 'fgColor' => 'yellow']);
                                }
                            }
                            if ($this->config['includeAllTexts']
                                || in_array($recipient, $item->recipients())
                                || ($this->config['includeSongTexts'] && (in_array($item->data_type, ['song', 'psalm']
                                )))) {
                                if (method_exists($this, ($method = 'render'.ucfirst($item->data_type.'Item')))) {
                                    $this->$method($doc, $item);
                                }
                            }
                        }
                    }
                }

            }
        } else {
            $this->renderLiturgyTable($doc);
        }

        return $doc->sendToBrowser($this->getFileName($service));
    }

    /**
     * Convert nested or duplicated form values into unique scalar recipient names.
     *
     * @param  array<int, mixed>  $recipients  Values submitted by the configuration form.
     * @return string[] Unique, non-empty recipient names.
     */
    protected function normalizeRecipients(array $recipients): array
    {
        return collect($recipients)
            ->flatten()
            ->filter(static fn ($recipient): bool => is_string($recipient) && trim($recipient) !== '')
            ->map(static fn (string $recipient): string => trim($recipient))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Render the liturgy items as a Word table.
     *
     * @param  DefaultWordDocument  $doc  Target document.
     * @param  string  $recipient  Optional recipient label.
     * @return void
     */
    protected function renderLiturgyTable(DefaultWordDocument $doc, $recipient = '')
    {
        /** @var array{styles: array{paragraphs: array{titles: array<int, array<string, mixed>>}, fonts: array{titles: array<int, array<string, mixed>>}}} $docConfig */
        $docConfig = $doc->getConfig();
        $run = new TextRun($docConfig['styles']['paragraphs']['titles'][1] ?? null);
        $run->addText($this->service->titleText(false), $docConfig['styles']['fonts']['titles'][1] ?? []);
        $run->addTextBreak();
        $run->addText($this->service->trueDate()->setTimeZone('Europe/Berlin')->isoFormat('DD.MM.YYYY, HH:mm').' Uhr'.', '
                      .$this->service->locationText(), $docConfig['styles']['fonts']['titles'][1] ?? []);
        $doc->getSection()->addTitle($run, 0);

        if (! $this->config['includeTable']) {
            return;
        }

        if ($recipient) {
            $doc->renderNormalText('Ablaufplan für '.$recipient, ['italic' => true], true);
        }

        foreach ($this->service->liturgyBlocks as $block) {
            $doc->getSection()->addTitle($block->title, 1);

            $table = $doc->getSection()->addTable('Ablauf');
            if ($table === null) {
                continue;
            }
            foreach ($block->items as $item) {
                if (method_exists($this, ($method = 'render'.ucfirst($item->data_type.'ItemRow')))) {
                    $table->addRow();
                    $this->$method($doc, $table, $item);
                }
            }
        }

    }

    /**
     * Set the metadata of the generated Word document.
     *
     * @param  DefaultWordDocument  $doc  Document whose properties are set.
     */
    protected function setProperties(DefaultWordDocument $doc): void
    {
        $properties = $doc->getPhpWord()->getDocInfo();
        if ($properties === null) {
            return;
        }
        $user = Auth::user();
        $properties->setCreator($user?->fullName() ?? '');
        $properties->setCompany($user->office ?? '');
        $properties->setTitle($this->getFileTitle());
        $properties->setDescription($this->getFileTitle().' ('.$this->title.')');
        $properties->setCategory('Gottesdienste');
        $properties->setLastModifiedBy($user?->fullName() ?? '');
        $properties->setSubject('Ablauf und Texte');
    }

    /**
     * Return the configured title used for the generated file.
     *
     * @return string File title.
     */
    public function getFileTitle(): string
    {
        return 'Gottesdienst'.(($this->service) && ($this->service->sermon) ? ' - '.$this->service->sermon->title : '');
    }

    /**
     * Render one generic liturgy item row.
     *
     * @param  Table  $table  Target table.
     * @param  Item  $item  Liturgy item.
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
     * Render recipient information for a liturgy item.
     *
     * @param  Table  $table  Target table.
     * @param  Item  $item  Liturgy item.
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
            $run->addText($recipient, $recipient == $this->currentRecipient ? ['fgColor' => 'yellow'] : []);
            $first = false;
        }
    }

    /**
     * Render a free-text item as a table row.
     *
     * @param  DefaultWordDocument  $doc  Target document.
     * @param  Table  $table  Target table.
     * @param  Item  $item  Liturgy item.
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
     * Render a sermon item as a table row.
     *
     * @param  DefaultWordDocument  $doc  Target document.
     * @param  Table  $table  Target table.
     * @param  Item  $item  Liturgy item.
     * @return void
     */
    protected function renderSermonItemRow(DefaultWordDocument $doc, Table $table, Item $item)
    {
        $sermon = $this->service->sermon;
        $this->renderRow($table, $item, $sermon->title ?? '');
    }

    /**
     * Render a psalm item as a table row.
     *
     * @param  DefaultWordDocument  $doc  Target document.
     * @param  Table  $table  Target table.
     * @param  Item  $item  Liturgy item.
     * @return void
     */
    protected function renderPsalmItemRow(DefaultWordDocument $doc, Table $table, Item $item)
    {
        /** @var PsalmItemHelper $helper */
        $helper = $item->getHelper();
        $this->renderRow($table, $item, $helper->getTitleText());
    }

    /**
     * Render a song item as a table row.
     *
     * @param  DefaultWordDocument  $doc  Target document.
     * @param  Table  $table  Target table.
     * @param  Item  $item  Liturgy item.
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
     * Render a reading item as a table row.
     *
     * @param  DefaultWordDocument  $doc  Target document.
     * @param  Table  $table  Target table.
     * @param  Item  $item  Liturgy item.
     * @return void
     */
    protected function renderReadingItemRow(DefaultWordDocument $doc, Table $table, Item $item)
    {
        $reference = $item->data['reference'] ?? '';
        $this->renderRow($table, $item, is_scalar($reference) ? (string) $reference : '');
    }

    /**
     * Render the content of a free-text item.
     *
     * @param  DefaultWordDocument  $doc  Target document.
     * @param  Item  $item  Liturgy item.
     * @return void
     */
    protected function renderFreetextItem(DefaultWordDocument $doc, Item $item)
    {
        if (! $item->data['description']) {
            return;
        }
        /** @var FreetextItemHelper $helper */
        $helper = $item->getHelper();
        $doc->renderNormalText(Replacement::replaceAll($helper->getText(), $this->service));
    }

    /**
     * Render the content of a sermon item.
     *
     * @param  DefaultWordDocument  $doc  Target document.
     * @param  Item  $item  Liturgy item.
     * @return void
     */
    protected function renderSermonItem(DefaultWordDocument $doc, Item $item)
    {
        if ($this->service->sermon === null) {
            $doc->renderNormalText('Für diesen Gottesdienst wurde noch keine Predigt angelegt.', ['italic' => true]);
        } else {
            if (! $this->service->sermon->text) {
                return;
            }
            $text = strtr($this->service->sermon->text, [
                '<h1>' => '<h3>', '</h1>' => '</h3>',
                '<h2>' => '<h4>', '</h2>' => '</h4>',
            ]);
            $dom = DefaultWordDocument::createDomDocumentFromHtml($text);
            $body = $dom->getElementsByTagName('body')->item(0);
            if (! $body) {
                return;
            }
            $nodes = [];
            /** @var \DOMNode $node */
            foreach ($body->childNodes as $node) {
                if ($node->nodeName == 'blockquote') {
                    foreach (explode("\n\n", DefaultWordDocument::getTextWithBreaksFromDomNode($node)) as $paragraph) {
                        if (trim($paragraph) === '') {
                            continue;
                        }
                        $doc->renderText($paragraph, DefaultWordDocument::BLOCKQUOTE, ['size' => 10]);
                    }
                } else {
                    $section = $doc->getSection();
                    $html = $dom->saveHTML($node);
                    if (is_string($html)) {
                        Html::addHtml($section, '<body>'.str_replace('<br>', '<br />', $html).'</body>');
                    }
                }
                $nodes[] = $node->nodeName.' -> '.$node->nodeValue;
            }
        }
    }

    /**
     * Render the content of a psalm item.
     *
     * @param  DefaultWordDocument  $doc  Target document.
     * @param  Item  $item  Liturgy item.
     * @return void
     */
    protected function renderPsalmItem(DefaultWordDocument $doc, Item $item)
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
     * Render the content of a song item.
     *
     * @param  DefaultWordDocument  $doc  Target document.
     * @param  Item  $item  Liturgy item.
     * @return void
     */
    protected function renderSongItem(DefaultWordDocument $doc, Item $item)
    {
        $song = $item->data['song'] ?? null;
        if (! is_array($song)) {
            return;
        }
        /** @var SongItemHelper $helper */
        $helper = $item->getHelper();
        $doc->getSection()->addTitle($helper->getTitleText(), 3);
        $songDetails = $song['song'] ?? null;
        $songDetails = is_array($songDetails) ? $songDetails : [];
        $copyrights = $songDetails['copyrights'] ?? null;
        if (is_scalar($copyrights) && $copyrights) {
            $doc->renderNormalText((string) $copyrights, ['size' => 8]);
        }
        if (! $this->config['includeSongTexts']) {
            return;
        }

        $refrain = $songDetails['refrain'] ?? '';
        $refrain = is_scalar($refrain) ? (string) $refrain : '';
        foreach ($helper->getActiveVerses() as $verse) {
            if ($verse['refrain_before']) {
                $doc->renderNormalText($refrain, ['italic' => true]);
            }
            $number = $verse['number'] ?? '';
            $verseText = $verse['text'] ?? '';
            $doc->renderNormalText((is_scalar($number) ? (string) $number : '').'. '
                .(is_scalar($verseText) ? (string) $verseText : ''));
            if ($verse['refrain_after']) {
                $doc->renderNormalText($refrain, ['italic' => true]);
            }
        }
    }

    /**
     * Render the content of a reading item.
     *
     * @param  DefaultWordDocument  $doc  Target document.
     * @param  Item  $item  Liturgy item.
     * @return void
     */
    protected function renderReadingItem(DefaultWordDocument $doc, Item $item)
    {
        if (! $item->data['reference']) {
            return;
        }
        $helper = new ReadingItemHelper($item);
        $includeFullReadings = $this->config['includeFullReadings'] ?? false;
        $helper->renderToWordDocument($doc, is_scalar($includeFullReadings) && (bool) $includeFullReadings);
    }
}
