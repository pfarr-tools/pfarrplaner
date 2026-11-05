<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 * @version git: $Id$
 */

namespace Tests\Unit\Services;

use App\Services\RoleService;
use Tests\TestCase;

class RoleServiceUnitTest extends TestCase
{
    public function test_role_constants_are_defined(): void
    {
        $this->assertEquals('Super-Administrator:in', RoleService::ROLE_SUPER_ADMIN);
        $this->assertEquals('Administrator:in', RoleService::ROLE_ADMIN);
        $this->assertEquals('Pfarrer:in', RoleService::ROLE_PASTOR);
        $this->assertEquals('Prädikant:in', RoleService::ROLE_PREDICANT);
    }

    public function test_role_constants_are_strings(): void
    {
        $this->assertIsString(RoleService::ROLE_SUPER_ADMIN);
        $this->assertIsString(RoleService::ROLE_ADMIN);
        $this->assertIsString(RoleService::ROLE_PASTOR);
        $this->assertIsString(RoleService::ROLE_PREDICANT);
    }

    public function test_role_constants_are_distinct(): void
    {
        $roles = [
            RoleService::ROLE_SUPER_ADMIN,
            RoleService::ROLE_ADMIN,
            RoleService::ROLE_PASTOR,
            RoleService::ROLE_PREDICANT,
        ];
        $this->assertCount(4, array_unique($roles));
    }
}
