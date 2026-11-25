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

namespace App\Documents\Word;

use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpWord\Element\Cell;
use PhpOffice\PhpWord\Shared\Converter;

class DefaultAddressLabelSheetDocument extends DefaultLabelSheetDocument
{
    /** @var string Formatted sender address shown on each label. */
    protected string $fromAddress = '';

    public const FONT6 = ['name' => 'Arial', 'size' => 6];

    public const FONT8 = ['name' => 'Arial', 'size' => 8];

    public const FONT10 = ['name' => 'Arial', 'size' => 10];

    /**
     * Convert a free-form configuration or address value into printable text.
     *
     * @param  mixed  $value  Value read from the document configuration or an address record.
     * @return string Textual representation, or an empty string for non-scalar values.
     */
    private function asString(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * @param  array<string, mixed>  $config  Document configuration.
     * @return void
     */
    public function __construct($config = [])
    {
        if (! isset($config['label'])) {
            $config['label'] = [];
        }
        parent::__construct($config);
    }

    /**
     * Render the address records as labels on the sheet.
     *
     * @param  iterable<array<string, mixed>>  $addresses  Address records to render.
     * @param  array<string, mixed>|string  $fromAddress  Sender address override or an empty string to use the current user's data.
     */
    public function renderAddresses(iterable $addresses, array|string $fromAddress): void
    {

        $user = Auth::user();
        $fromAddressData = is_array($fromAddress) ? $fromAddress : [];
        $fromParts = collect();
        if ($x = $fromAddressData['office'] ?? $user?->office) {
            $fromParts->push($x);
        }
        if (isset($fromAddressData['address'])) {
            $fromParts->push($fromAddressData['address']);
            $zip = $this->asString($fromAddressData['zip'] ?? '');
            $city = $this->asString($fromAddressData['city'] ?? '');
            if ($x = trim($zip.' '.$city)) {
                $fromParts->push($x);
            }
        } else {
            if ($user?->address) {
                foreach (explode("\n", $user->address) as $x) {
                    $fromParts->push(trim($x));
                }
            }
        }

        $this->fromAddress = $this->asString($fromParts->join(' · '));

        $labelConfig = $this->config['label'] ?? [];
        $labelHeight = is_array($labelConfig) ? ($labelConfig['height'] ?? 3.7) : 3.7;
        $skipLabels = $this->config['skipLabels'] ?? 0;

        $this->renderLabels(
            $addresses,
            3,
            Converter::cmToTwip(7),
            Converter::cmToTwip(is_numeric($labelHeight) ? (float) $labelHeight : 3.7),
            [

            ],
            [$this, 'renderAddress'],
            is_numeric($skipLabels) ? (int) $skipLabels : 0,
        );
    }

    /**
     * Render a single address into one label cell.
     *
     * @param  Cell  $cell  Table cell receiving the address.
     * @param  array<string, mixed>  $address  Address record with name, address, zip and city.
     * @param  int  $index  Zero-based index of the label.
     */
    public function renderAddress(Cell $cell, array $address, int $index): void
    {

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
            $cell->addText($this->asString($address['info']), static::FONT6, array_merge($pStyle, [
                'align' => 'right',
                'spaceBefore' => $this->fromAddress ? 0 : Converter::cmToTwip(0.7),
            ]));
        }

        $run = $cell->addTextRun(array_merge($pStyle, [
            'spaceBefore' => $this->fromAddress || isset($address['info']) ? 0 : Converter::cmToTwip(0.7),
        ]));
        $run->addText($this->asString($address['name']), static::FONT10);
        $run->addTextBreak();
        $run->addText($this->asString($address['address']), static::FONT10);
        $run->addTextBreak();
        $run->addText(trim($this->asString($address['zip']).' '.$this->asString($address['city'])), static::FONT10);
    }
}
