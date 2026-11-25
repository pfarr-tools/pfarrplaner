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

use App\Services\PackageService;
use Illuminate\Support\Str;

abstract class AbstractSngFile extends AbstractFile
{
    /** @var array<string, string|int> SNG header key/value pairs */
    protected $headers = [];

    /** @var string Name of the generated SNG file */
    protected $fileName = '';

    /** @var string Song or slide title */
    protected $title = '';

    /**
     * @param  string  $title  Song title.
     * @return void
     */
    public function __construct(string $title)
    {
        $this->setTitle($title);
        $this->fileName = Str::slug($title).'.sng';
        $this->setPathInZip('Songs/'.$this->fileName);
    }

    /**
     * Wrap song text after 40 characters
     *
     * @param  string  $line  Line to wrap
     * @param  int  $indent  Indentation depth
     */
    protected function wordWrap(string $line, int $indent = 0): string
    {
        $lineLength = 40 - $indent;
        $pad = str_repeat(' ', $indent);
        if (str_contains($line, "\n")) {
            $line = str_replace("\n", "\r\n", str_replace("\r\n", "\n", $line));
            $line = $pad.trim($line);
            if (! Str::endsWith($line, "\r\n")) {
                $line .= "\r\n";
            }

            return $line;
        } else {
            return $pad.implode("\r\n".$pad, explode("\r\n", wordwrap($line, $lineLength, "\r\n")))."\r\n";
        }
    }

    /**
     * Get the data for the schedule file
     *
     * @return array<string, mixed> Schedule item data
     */
    public function toScheduleItem(): array
    {
        $scheduleItem = [
            'Caption' => utf8_decode($this->getCaption()),
            'Color' => SongBeamer::LOGO_COLOR,
            'FileName' => $this->getFileName(),
            'Props' => [],
        ];

        return $scheduleItem;
    }

    /**
     * Get the data for the song profile
     *
     * @return array<string, mixed> Song profile data
     */
    public function toProfile(): array
    {
        return [
            'SngRelFileName' => $this->fileName,
        ];
    }

    /**
     * Get the SNG headers
     *
     * @return array<string, string|int> Header key/value pairs
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Set the SNG headers
     *
     * @param  array<string, string|int>  $headers  Header key/value pairs
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    /**
     * Add a single header value
     */
    public function addHeader(string $key, string $value): void
    {
        $this->headers[$key] = $value;
    }

    /**
     * Output headers to text as key/value pairs
     */
    public function headersToText(): string
    {
        if (! isset($this->headers['Editor'])) {
            $package = PackageService::info();
            $appName = config('app.name');
            $versionString = $package['versionString'] ?? null;
            $this->addHeader('Editor', (is_string($appName) ? $appName : '').' '.(is_string($versionString) ? $versionString : ''));
        }
        if (! isset($this->headers['Version'])) {
            $this->addHeader('Version', '3');
        }

        $text = '';
        foreach ($this->headers as $key => $value) {
            $text .= $this->line('#'.$key.'='.$value);
        }

        return $text;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * @param  string  $fileName  File name.
     */
    public function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param  string  $title  Song title.
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * Get the caption used in the schedule file
     *
     * @return string
     */
    public function getCaption()
    {
        return $this->getTitle();
    }
}
