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

use Illuminate\Support\Str;

class SimpleText extends AbstractSngFile
{

    protected $title;
    protected $text;
    protected $scheduleOptions = [];
    protected $profileOptions = [];
    protected $headerOptions = [];
    protected $showTitle = true;

    public function __construct(string $title, string $text, array $scheduleOptions = [], array $profileOptions = [], array $headerOptions = [], $showTitle = true)
    {
        parent::__construct($title);
        $this->text = $text;
        $this->scheduleOptions = $scheduleOptions;
        $this->profileOptions = $profileOptions;
        $this->headerOptions = $headerOptions;
        $this->showTitle = $showTitle;
    }

    /**
     * @inheritDoc
     */
    public function toText()
    {
        $headers = $this->headerOptions;
        if ($this->showTitle) $headers['Title'] = $this->title;
        $this->setHeaders($headers);

        $text = $this->headersToText();
        $ctr = 0;
        foreach (explode("\r\n", trim($this->wordWrap($this->text))) as $line) {
            if ($ctr % 10 == 0) {
                $text .= $this->line('---');
            }
            $text .= $this->line($line);
            $ctr++;
        }
        return $text;
    }

    /**
     * @inheritDoc
     */
    public function toScheduleItem(): array
    {
        $scheduleItem =  parent::toScheduleItem();
        if (count($this->scheduleOptions)) $scheduleItem = array_merge($this->scheduleOptions, $scheduleItem);
        return $scheduleItem;
    }

    /**
     * @inheritDoc
     */
    public function toProfile(): array
    {
        $profile = parent::toProfile();
        if (count($this->profileOptions)) $profile = array_merge($this->profileOptions, $profile);
        return $profile;
    }


}
