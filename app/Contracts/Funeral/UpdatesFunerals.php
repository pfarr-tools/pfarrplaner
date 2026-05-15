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

namespace App\Contracts\Funeral;

use App\Models\People\User;
use App\Models\Rites\Funeral;

interface UpdatesFunerals
{
    /**
     * @param User $user
     * @param Funeral $funeral
     * @param array $input
     * @return Funeral
     */
    public function update(User $user, Funeral $funeral, array $input): Funeral;
}
