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

namespace App\Documents\Word;

use App\Services\NameService;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpWord\Element\Cell;
use PhpOffice\PhpWord\Shared\Converter;

class DefaultAddressLabelSheetDocument extends DefaultLabelSheetDocument
{

    protected $fromAddress = '';

    public const FONT6 = ['name' => 'Arial', 'size' => 6];
    public const FONT8 = ['name' => 'Arial', 'size' => 8];
    public const FONT10 = ['name' => 'Arial', 'size' => 10];

    public function __construct($config = [])
    {
        if (!isset($config['label'])) $config['label'] = [];
        parent::__construct($config);
    }

    public function renderAddresses($addresses, $fromAddress)
    {

        $user = Auth::user();
        $fromParts = collect();
        if ($x = $fromAddress['office'] ?? $user->office) $fromParts->push($x);
        if (isset($fromAddress['address'])) {
            $fromParts->push($fromAddress['address']);
            if ($x = trim(($fromAddress['zip'] ?? '').' '.($fromAddress['city'] ?? ''))) $fromParts->push($x);
        } else {
            if ($user->address) {
                foreach (explode("\n", $user->address) as $x) $fromParts->push(trim($x));
            }
        }

        $this->fromAddress = $fromParts->join(' · ');

        $this->renderLabels(
            $addresses,
            3,
            Converter::cmToTwip(7),
            Converter::cmToTwip($this->config['label']['height'] ?? 3.7),
            [

            ],
            [$this, 'renderAddress'],
            $this->config['skipLabels'] ?? 0,
        );
    }

    public function renderAddress(Cell $cell, $address, $index) {

        $pStyle = [
            'indentation' => [
                'left' => Converter::cmToTwip(0.7),
                'right' => Converter::cmToTwip(0.7),
            ],
            'spaceAfter' => 60,
        ];

        if ($this->fromAddress) {
            $cell->addText($this->fromAddress, static::FONT6, array_merge($pStyle, [
                'spaceBefore' => Converter::cmToTwip(0.7),
                'borderBottomSize' => Converter::pointToTwip(.25),
                'borderBottomColor' => '#000000',
            ]));
        }

        if (isset($address['info'])) {
            $cell->addText($address['info'], static::FONT6, array_merge($pStyle, [
                'align' => 'right',
                'spaceBefore' => $this->fromAddress ? 0: Converter::cmToTwip(0.7),
            ]));
        }

        $run = $cell->addTextRun(array_merge($pStyle, [
            'spaceBefore' => $this->fromAddress || isset($address['info']) ? 0: Converter::cmToTwip(0.7),
        ]));
        $run->addText($address['name'], static::FONT10);
        $run->addTextBreak();
        $run->addText($address['address'], static::FONT10);
        $run->addTextBreak();
        $run->addText(trim($address['zip'].' '.$address['city']), static::FONT10);
    }


}
