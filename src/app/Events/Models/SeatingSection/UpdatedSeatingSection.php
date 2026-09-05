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

namespace App\Events\Models\SeatingSection;

use App\Models\People\User;
use App\Models\Seating\SeatingSection;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdatedSeatingSection
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected User $user;
    protected SeatingSection $seatingSection;

    public function __construct(User $user, SeatingSection $seatingSection)
    {
        $this->user = $user;
        $this->seatingSection = $seatingSection;
    }
}
