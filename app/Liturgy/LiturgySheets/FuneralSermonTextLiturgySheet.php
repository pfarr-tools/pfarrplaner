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


use App\Documents\Word\DefaultA5WordDocument;
use App\Documents\Word\DefaultWordDocument;
use App\Models\Liturgy\Item;
use App\Models\Service;
use App\Services\NameService;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpWord\Shared\Html;

class FuneralSermonTextLiturgySheet extends AbstractLiturgySheet
{
    protected $title = 'Erinnerung an die Trauerfeier';
    protected $icon = 'fa fa-file-word';

    /** @var Service $service */
    protected $service = null;
    protected $extension = 'docx';
    protected $privileged = true;

    public function __construct()
    {
        parent::__construct();
    }

    public function render(Service $service)
    {
        $this->service = $service;
        if (!count($service->funerals)) return;

        $doc = new DefaultA5WordDocument();
        $doc->getPhpWord()->getSettings()->setBookFoldPrinting(true);
        $this->setProperties($doc);
        $doc->setInstructionsFontStyle(['size' => 8, 'italic' => true]);

        $names = collect();

        // title page
        $doc->getSection()->addTextBreak(5);
        $doc->getSection()->addText('Zur Erinnerung an', ['size' => 16], ['align' => 'center']);
        $doc->getSection()->addTextBreak(1);
        foreach ($service->funerals as $funeral) {
            $name = NameService::fromName($funeral->buried_name)->format(NameService::FIRST_LAST);
            $names->push($name);
            $doc->getSection()->addText($name, ['size' => 24], ['align' => 'center']);
            $doc->getSection()->addText(
                $funeral->dob->format('d.m.Y').' - '.$funeral->dod->format('d.m.Y'),
                ['size' => 12], ['align' => 'center']);
        }
        $doc->getSection()->addTextBreak(4);
        $doc->getSection()->addText(
            'Trauerfeier am '.$service->date->format('d.m.Y'),
            ['size' => 12], ['align' => 'center']);
        $doc->getSection()->addText(
            $service->locationText(),
            ['size' => 12], ['align' => 'center']);

        $doc->getSection()->addPageBreak();

        // heading
        if (($this->service) && ($this->service->sermon)) {
            $doc->getSection()->addTitle($this->service->sermon->title, 1);
            if ($this->service->sermon->subtitle) $doc->getSection()->addTitle($this->service->sermon->subtitle, 2);
            $doc->getSection()->addTextBreak();
        }

        foreach ($service->liturgyBlocks as $block) {
            foreach ($block->items as $item) {
                if ($item->data_type == 'sermon') {
                    $this->renderSermonItem($doc, $item);
                }
            }
        }

        $doc->sendToBrowser($this->getFileName($service, (($this->service) && ($this->service->sermon) ? ' - Trauerfeier für ' .$names->join(', ', ' und ')  : '')));
    }

    protected function setProperties(DefaultWordDocument $doc)
    {
        $properties = $doc->getPhpWord()->getDocInfo();
        $properties->setCreator(Auth::user()->name);
        $properties->setCompany(Auth::user()->office ?? '');
        $properties->setTitle($this->service->date->setTimeZone('Europe/Berlin')->format('Ymd-Hi') . ' ' . $this->getFileTitle());
        $properties->setDescription($this->getFileTitle() . ' (' . $this->title . ')');
        $properties->setCategory('Gottesdienste');
        $properties->setLastModifiedBy(Auth::user()->name);
        $properties->setSubject('Komplette Liturgie');
    }


    protected function renderSermonItem(DefaultWordDocument $doc, Item $item)
    {
        if (null === $this->service->sermon) {
            $doc->renderNormalText('Für diesen Gottesdienst wurde noch keine Predigt angelegt.', ['italic' => true]);
        } else {
            if (!$this->service->sermon->text) {
                return;
            }
            $text = utf8_decode(
                strtr($this->service->sermon->text, [
                    '<h1>' => '<h3>',
                    '</h1>' => '</h3>',
                    '<h2>' => '<h4>',
                    '</h2>' => '</h4>',
                ])
            );
            $dom = new \DOMDocument();
            $dom->loadHTML($text, LIBXML_NOWARNING);
            $nodes = [];
            /** @var \DOMNode $node */
            foreach ($dom->documentElement->firstChild->childNodes as $node) {
                if ($node->nodeName == 'blockquote') {
                    $doc->renderText($node->nodeValue, $doc::BLOCKQUOTE, ['size' => 10]);
                } else {
                    Html::addHtml(
                        $doc->getSection(),
                        '<body>' . str_replace('<br>', '<br />', $dom->saveHTML($node)) . '</body>'
                    );
                }
                $nodes[] = $node->nodeName . ' -> ' . $node->nodeValue;
            }
        }
    }


}
