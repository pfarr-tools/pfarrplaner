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

namespace App\Attachments\Baptism;

use App\Attachments\AbstractAttachment;
use App\Models\Rites\Baptism;
use App\Services\FileNameService;

abstract class AbstractBaptismAttachment extends AbstractAttachment
{
    public const FILE_SIGNATURE = '51.1';

    /** @var Baptism $baptism */
    protected $baptism;

    public function __construct(Baptism $baptism)
    {
        $this->baptism = $baptism;
    }


    public static function fromId($id)
    {
        return new (get_called_class())(Baptism::findOrFail($id));
    }

    public function getFileName()
    {
        return FileNameService::make(
            static::$fileTitle ?: static::$title,
            static::$extension,
            self::FILE_SIGNATURE,
            $this->baptism->service->date,
            false,
            $this->baptism->candidate_name
        );
    }

}
