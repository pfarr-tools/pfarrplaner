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

namespace App\Liturgy\SongBeamer;

use ZipArchive;

use function PHPUnit\Framework\isEmpty;

abstract class AbstractFile
{

    protected $pathInZip = '';

    public function __construct($pathInZip)
    {
        $this->pathInZip = $pathInZip;
    }

    protected function line($text, $indent = 0)
    {
        return str_repeat(' ', $indent*2).$text."\r\n";
    }

    abstract function toText();

    public function addToZipFile(ZipArchive &$zipArchive) {
        $zipArchive->addFromString($this->pathInZip, $this->toText(), ZipArchive::FL_OVERWRITE);
    }

    /**
     * @return string
     */
    public function getPathInZip(): string
    {
        return $this->pathInZip;
    }

    /**
     * @param string $pathInZip
     */
    public function setPathInZip(string $pathInZip): void
    {
        $this->pathInZip = $pathInZip;
    }



}
