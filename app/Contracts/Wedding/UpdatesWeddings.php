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

namespace App\Contracts\Wedding;

use App\Models\People\User;
use App\Models\Rites\Wedding;

interface UpdatesWeddings
{
    /**
     * @param User $user
     * @param Wedding $wedding
     * @param array $input
     * @return Wedding
     */
    public function update(User $user, Wedding $wedding, array $input): Wedding;
}
