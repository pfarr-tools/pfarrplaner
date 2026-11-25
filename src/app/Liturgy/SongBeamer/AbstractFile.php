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
 *
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

abstract class AbstractFile
{
    /** @var string Path of the file inside the ZIP archive */
    protected $pathInZip = '';

    /**
     * @param  string  $pathInZip  Path of the file inside the ZIP archive
     * @return void
     */
    public function __construct(string $pathInZip)
    {
        $this->pathInZip = $pathInZip;
    }

    /**
     * Formats a single line with the given indentation depth.
     *
     * @param  string  $text  Line content to format
     * @param  int  $indent  Indentation depth (two spaces per level)
     * @return string Formatted line including the line break
     */
    protected function line(string $text, int $indent = 0): string
    {
        return str_repeat(' ', $indent * 2).$text."\r\n";
    }

    /**
     * Renders the file content as text.
     *
     * @return string Rendered file content
     */
    abstract public function toText();

    /**
     * Adds the rendered file to the given ZIP archive.
     *
     * @param  ZipArchive  $zipArchive  Archive to add the file to
     */
    public function addToZipFile(ZipArchive &$zipArchive): void
    {
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
     * @param  string  $pathInZip  Path inside the archive.
     * @return void
     */
    public function setPathInZip(string $pathInZip): void
    {
        $this->pathInZip = $pathInZip;
    }
}
