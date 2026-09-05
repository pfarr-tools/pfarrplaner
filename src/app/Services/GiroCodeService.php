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

namespace App\Services;

class GiroCodeService
{


    /**
     * Generates the text payload for an EPC GiroCode (SEPA Credit Transfer).
     *
     * The returned string can be passed directly to a QR code generator
     * (e.g. `qrencode`) to create a banking-compatible EPC QR code.
     *
     * @param string      $name    Name of the payment recipient.
     * @param string      $iban    IBAN of the payment recipient.
     * @param float|null  $amount  Payment amount in EUR (optional).
     * @param string      $purpose Payment reference / purpose (optional).
     * @param string|null $bic     BIC of the recipient bank (optional, may be empty).
     *
     * @return string EPC-compliant text payload for a GiroCode QR code.
     */
    public static function codeValue(
        string $name,
        string $iban,
        ?float $amount = null,
        string $purpose = '',
        ?string $bic = null
    ): string {
        // Grundvalidierungen
        $iban = strtoupper(str_replace(' ', '', $iban));
        $bic  = $bic ? strtoupper(trim($bic)) : '';

        // Betrag formatieren (falls vorhanden)
        $amountLine = '';
        if ($amount !== null) {
            $amountLine = 'EUR' . number_format($amount, 2, '.', '');
        }

        // EPC-Zeilen laut Standard
        $lines = [
            'BCD',          // Service Tag
            '001',          // Version
            '1',            // Zeichensatz: UTF-8
            'SCT',          // SEPA Credit Transfer
            $bic,           // BIC (optional)
            trim($name),    // Name Zahlungsempfänger
            $iban,          // IBAN
            $amountLine,    // Betrag (optional)
            '',
            trim($purpose) // Verwendungszweck (optional)
        ];

        // Leere Zeilen am Ende entfernen (EPC-konform)
        while (!empty($lines) && trim(end($lines)) === '') {
            array_pop($lines);
        }

        return implode("\n", $lines);
    }

}
