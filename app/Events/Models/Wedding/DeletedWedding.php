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

namespace App\Events\Models\Wedding;

use App\Models\People\User;
use App\Models\Rites\Wedding;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeletedWedding
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected User $user;
    protected Wedding $wedding;

    /**
     * @param User $user
     * @param Wedding $wedding
     */
    public function __construct(User $user, Wedding $wedding)
    {
        $this->user = $user;
        $this->wedding = $wedding;
    }
}
