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
 *
 * Sponsored by: Evangelischer Kirchenbezirk Balingen, https://www.kirchenbezirk-balingen.de
 *
 * Pfarrplaner is based on the Laravel framework (https://laravel.com).
 * This file may contain code created by Laravel's scaffolding functions.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tests\Traits;

use PHPUnit\Framework\Assert;

trait TestsClasses
{

    /**
     * @param $class
     * @return void
     */
    public function assertClassExists($class): void
    {
        Assert::assertTrue(class_exists($class), 'Class '.$class.' does not exist');
    }

    /**
     * @param $interface
     * @return void
     */
    public function assertInterfaceExists($interface): void
    {
        Assert::assertTrue(interface_exists($interface), 'Interface '.$interface.' does not exist');
    }

    /**
     * @param $expectedClass
     * @param $actualClass
     * @return void
     */
    public function assertIsSubClassOf($expectedClass, $actualClass): void
    {
        Assert::assertTrue(is_subclass_of($actualClass, $expectedClass));
    }

    /**
     * @param $expectedClass
     * @param $actualClass
     * @return void
     */
    public function assertIsA($expectedClass, $actualClass): void
    {
        Assert::assertTrue(is_a($actualClass, $expectedClass), (is_string($expectedClass) ? $expectedClass : get_class($actualClass)).' is not a '.$expectedClass);
    }

}
