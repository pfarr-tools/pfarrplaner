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

namespace App\Actions\SeatingSection;

use App\Seating\SeatingModels;

class NormalizesSeatingSectionData
{
    public static function normalize(array $data): array
    {
        if (isset($data['seating_model']) && ($model = SeatingModels::byTitle($data['seating_model']))) {
            $data['seating_model'] = get_class($model);
        }

        if (($data['color'] ?? '') === 'rgb(0,0,0)') {
            $data['color'] = '';
        }

        $data['seating_model'] = $data['seating_model'] ?? \App\Seating\RowBasedSeatingModel::class;

        return $data;
    }
}
