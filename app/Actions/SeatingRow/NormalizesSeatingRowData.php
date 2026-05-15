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
 */

namespace App\Actions\SeatingRow;

class NormalizesSeatingRowData
{
    public static function normalize(array $data): array
    {
        if (is_numeric($data['title'])) {
            $data['title'] = str_pad((string) $data['title'], 2, '0', STR_PAD_LEFT);
        }

        $data['divides_into'] = $data['divides_into'] ?? 1;
        $data['seats'] = $data['seats'] ?? 1;
        $data['spacing'] = $data['spacing'] ?? 0;
        $data['split'] = str_replace(' ', '', $data['split'] ?? '');

        return $data;
    }
}
