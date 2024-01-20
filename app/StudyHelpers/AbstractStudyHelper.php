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

use GuzzleHttp\Client;
use Illuminate\Console\Command;

abstract class AbstractStudyHelper
{

    public $title = '';

    /** @var Client|null $client */
    protected $client = null;

    /** @var Command $command */
    protected $command = null;


    public function __construct(Command $command)
    {
        $this->client = new Client();
        $this->command = $command;
    }

    protected function getContent($url)
    {
        try {
            $result = $this->client->get($url);
        } catch (\Exception $exception) {
            return '';
        }
        if ($result->getStatusCode() != 200) {
            return '';
        }
        return $result->getBody()->getContents();
    }


    abstract function read(): void;

    abstract function getLinks(array $data): array;
}
