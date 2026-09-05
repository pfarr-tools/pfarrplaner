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

namespace App\Events\Models\Funeral;

use App\Models\People\User;
use App\Models\Rites\Funeral;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeletedFuneral
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected User $user;
    protected Funeral $funeral;

    /**
     * @param User $user
     * @param Funeral $funeral
     */
    public function __construct(User $user, Funeral $funeral)
    {
        $this->user = $user;
        $this->funeral = $funeral;
    }
}
