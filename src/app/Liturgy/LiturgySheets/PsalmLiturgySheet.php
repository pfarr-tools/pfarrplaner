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

use App\Documents\Word\DefaultA5WordDocument;
use App\Documents\Word\DefaultWordDocument;
use App\Liturgy\ItemHelpers\PsalmItemHelper;
use App\Models\Liturgy\Item;
use App\Models\Service;

class PsalmLiturgySheet extends AbstractLiturgySheet
{
    protected $title = 'Psalm';

    protected $fileTitle = 'Psalm';

    protected $icon = 'fa fa-file-word';

    /** @var Service Service currently rendered for the export. */
    protected $service = null;

    protected $extension = 'docx';

    /** @var string Title of the rendered Psalm used as file name. */
    protected $fileName = '';

    /**
     * @param  Service  $service  Service to render.\n     * @return mixed
     */
    public function render(Service $service)
    {
        $this->service = $service;

        $doc = new DefaultA5WordDocument;

        foreach ($service->liturgyBlocks as $block) {
            foreach ($block->items as $item) {
                if (method_exists($this, ($method = 'render'.ucfirst($item->data_type.'Item')))) {
                    $this->$method($doc, $item);
                    $doc->getSection()->addPageBreak();
                    $this->$method($doc, $item);
                }
            }
        }

        return $doc->sendToBrowser($this->getFileName($service));
    }

    /**
     * Render Psalm text when the liturgy item contains usable Psalm data.
     *
     * @param  DefaultWordDocument  $doc  Document receiving the Psalm content.
     * @param  Item  $item  Liturgy item whose Psalm data should be rendered.
     */
    protected function renderPsalmItem(DefaultWordDocument $doc, Item $item): void
    {
        $psalmData = $item->data['psalm'] ?? null;
        if (! is_array($psalmData)) {
            return;
        }
        $text = $psalmData['text'] ?? null;
        if (! is_string($text) || empty($text)) {
            return;
        }
        /** @var PsalmItemHelper $helper */
        $helper = $item->getHelper();
        $title = $psalmData['title'] ?? 'Psalm';
        $title = is_string($title) ? $title : 'Psalm';
        $doc->getSection()->addTitle($title, 1);
        $doc->renderNormalText($text);
        $this->fileName = $title;
    }
}
