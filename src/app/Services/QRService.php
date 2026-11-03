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

class QRService
{
    /**
     * Build the qrencode command for a payload file.
     *
     * @param string $file Output PNG path
     * @param string $payloadFile Input payload path
     * @return string qrencode command
     */
    public static function buildCommand(string $file, string $payloadFile): string
    {
        return 'qrencode -8 -l M -v 2 -m 0 -M 4 -o '
            . escapeshellarg($file)
            . ' -r '
            . escapeshellarg($payloadFile);
    }

    /**
     * Generate a QR code PNG for a value.
     *
     * @param string $value QR code payload
     * @return string Temporary PNG path
     */
    public static function generate($value) {
        $file = tempnam('/tmp', 'pfp-qr').'.png';

        // Wichtig: kein Leerraum am Ende, keine extra Leerzeile
        $value = rtrim($value, "\r\n");

        // temp file für payload
        $tmp = tempnam(sys_get_temp_dir(), 'epc_');
        file_put_contents($tmp, $value);

        $command = self::buildCommand($file, $tmp);

        exec($command, $out, $code);
        @unlink($tmp);

        if ($code !== 0) {
            throw new RuntimeException("qrencode failed with exit code $code");
        }

        return $file;
    }
}
