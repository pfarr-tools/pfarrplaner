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

namespace Tests\Unit;

use App\Models\Seating\Booking;
use Tests\AbstractSimpleModelUnitTest;

class BookingUnitTest extends AbstractSimpleModelUnitTest
{
    protected string $modelClass = Booking::class;
    protected bool $hasPolicy = false;
    protected bool $hasFactory = false;
}
