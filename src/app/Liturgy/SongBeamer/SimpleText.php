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

/**
 * Represents a simple text slide as a SongBeamer SNG file.
 */
class SimpleText extends AbstractSngFile
{
    /** @var string Slide title used as the file name and optional header */
    protected $title;

    /** @var string Slide text rendered into the SNG file */
    protected $text;

    /** @var array<string, string|int> Additional schedule item options */
    protected $scheduleOptions = [];

    /** @var array<string, string|int> Additional song profile options */
    protected $profileOptions = [];

    /** @var array<string, string|int> Additional SNG header options */
    protected $headerOptions = [];

    /** @var bool Whether the title is rendered as an SNG header */
    protected $showTitle = true;

    /**
     * Create a simple text slide.
     *
     * @param  string  $title  Slide title
     * @param  string  $text  Slide text
     * @param  array<string, string|int>  $scheduleOptions  Additional schedule item options
     * @param  array<string, string|int>  $profileOptions  Additional song profile options
     * @param  array<string, string|int>  $headerOptions  Additional SNG header options
     * @param  bool  $showTitle  Whether the title is rendered as an SNG header
     */
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
     * Render the simple text slide as SNG file text.
     *
     * @return string Rendered SNG file text
     */
    public function toText()
    {
        $headers = $this->headerOptions;
        if ($this->showTitle) {
            $headers['Title'] = $this->title;
        }
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
     * Get the schedule item data with the additional options merged in.
     *
     * @return array<string, mixed> Schedule item data
     */
    public function toScheduleItem(): array
    {
        /** @var array<string, mixed> $scheduleItem */
        $scheduleItem = parent::toScheduleItem();
        if (count($this->scheduleOptions)) {
            $scheduleItem = array_merge($this->scheduleOptions, $scheduleItem);
        }

        return $scheduleItem;
    }

    /**
     * Get the song profile data with the additional options merged in.
     *
     * @return array<string, mixed> Song profile data
     */
    public function toProfile(): array
    {
        /** @var array<string, mixed> $profile */
        $profile = parent::toProfile();
        if (count($this->profileOptions)) {
            $profile = array_merge($this->profileOptions, $profile);
        }

        return $profile;
    }
}
