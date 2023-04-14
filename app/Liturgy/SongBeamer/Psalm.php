<?php
/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://codeberg.org/pfarrplaner/pfarrplaner
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

namespace App\Liturgy\SongBeamer;

use App\Liturgy\Item;
use App\Liturgy\ItemHelpers\PsalmItemHelper;
use App\Liturgy\ItemHelpers\SongItemHelper;
use App\Services\PackageService;
use Illuminate\Support\Str;

class Psalm extends AbstractSngFile
{

    /** @var Item */
    protected $item;

    protected $fileName = '';

    /** @var PsalmItemHelper $helper */
    protected $helper;


    /**
     * @param Item $item
     */
    public function __construct(Item $item)
    {
        $this->item = $item;
        $this->helper = $item->getHelper();
        parent::__construct($this->helper->getTitleText());
    }

    public function toText()
    {
        $this->setHeaders([
            'LangCount' => 1,
            'Title' => $this->item->data['psalm']['title'],
            'ChurchSongID' => $this->helper->getCodeText(),
            'Songbook' => $this->helper->getCodeText(),
            'CCLI' => '-',
            'Rights' => $this->helper->getTitleText(),
                          ]);


        $text = $this->headersToText();

        foreach ($this->helper->getVerses() as $verse) {
            $text .= $this->line('---');
            foreach ($verse as $line) {
                $indent = Str::startsWith($line, "\t") ? 4 : 0;
                $text .= utf8_decode($this->wordWrap(str_replace("\t",'', $line), $indent));
            }
        }
        return $text;

    }

    /**
     * Get the data for the song profile
     * @return array
     */
    public function toProfile(): array
    {
        $profile = parent::toProfile();
        $profile['Lang1Style'] = '?L';
        $profile['UseSongProfile'] = '@@True';
        return $profile;
    }

    /**
     * @return Item
     */
    public function getItem(): Item
    {
        return $this->item;
    }

    /**
     * @param Item $item
     */
    public function setItem(Item $item): void
    {
        $this->item = $item;
    }

    public function getCaption()
    {
        return $this->item->title.': '.$this->getTitle();
    }


}
