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

namespace App\StudyHelpers;

use App\StudyHelpers\AbstractStudyHelper;
use Illuminate\Support\Str;

class VELKDStudyHelper extends AbstractStudyHelper
{

    public $title = 'VELKD Lesepredigt';
    protected $records = [];

    function read(): void
    {
        if ('' == ($content = $this->getContent('https://www.velkd.de/schwerpunkte/liturgie/lesepredigt'))) return;
        $content = Str::replace("\n", '', $content);
        preg_match('/<li><a href="(.*?)"(?:.*?)Lesepredigt für(?:.*)(\d\d\.\d\d\.\d\d\d\d)/', $content, $matches);

        $this->records[$matches[2]] = ['[VELKD] Lesepredigt für den '.$matches[2] => 'https://www.velkd.de'.$matches[1]];
    }

    function getLinks(array $data): array
    {
        $data['links'] = array_merge($data['links'] ?? [], $this->records[$data['date']] ?? []);
        return $data;
    }
}
