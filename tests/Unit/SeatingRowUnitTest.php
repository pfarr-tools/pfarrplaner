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

use App\Models\Seating\SeatingRow;
use App\Services\RoleService;
use Tests\AbstractModelUnitTest;

class SeatingRowUnitTest extends AbstractModelUnitTest
{
    protected $modelClass = SeatingRow::class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testUser->assignRole(RoleService::ROLE_SUPER_ADMIN);
    }
}
