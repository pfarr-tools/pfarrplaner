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

namespace Tests\Unit\Services;

use App\Services\RoleService;
use Tests\TestCase;

class RoleServiceUnitTest extends TestCase
{
    public function testRoleConstantsAreDefined(): void
    {
        $this->assertEquals('Super-Administrator:in', RoleService::ROLE_SUPER_ADMIN);
        $this->assertEquals('Administrator:in', RoleService::ROLE_ADMIN);
        $this->assertEquals('Pfarrer:in', RoleService::ROLE_PASTOR);
    }

    public function testRoleConstantsAreStrings(): void
    {
        $this->assertIsString(RoleService::ROLE_SUPER_ADMIN);
        $this->assertIsString(RoleService::ROLE_ADMIN);
        $this->assertIsString(RoleService::ROLE_PASTOR);
    }

    public function testRoleConstantsAreDistinct(): void
    {
        $roles = [RoleService::ROLE_SUPER_ADMIN, RoleService::ROLE_ADMIN, RoleService::ROLE_PASTOR];
        $this->assertCount(3, array_unique($roles));
    }
}
