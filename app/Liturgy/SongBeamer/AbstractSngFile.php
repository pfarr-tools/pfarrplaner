<?php
/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://codeberg.org/pfarrplaner/pfarrplaner
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

use App\Services\PackageService;
use Illuminate\Support\Str;

abstract class AbstractSngFile extends AbstractFile
{

    protected $headers = [];


    protected $fileName = '';
    protected $title = '';


    public function __construct(string $title)
    {
        $this->setTitle($title);
        $this->fileName = Str::slug($title) . '.sng';
        $this->setPathInZip('Songs/' . $this->fileName);
    }

    /**
     * Wrap song text after 40 characters
     * @param $line
     * @return string
     */
    protected function wordWrap($line, $indent = 0) {
        $lineLength = 40-$indent;
        $pad = str_repeat(' ', $indent);
        if (str_contains($line, "\n")) {
            $line = str_replace("\n", "\r\n", str_replace("\r\n", "\n", $line));
            $line = $pad.trim($line);
            if (!Str::endsWith($line, "\r\n")) $line .= "\r\n";
            return $line;
        } else {
            return $pad.join("\r\n".$pad, explode("\r\n", wordwrap($line, $lineLength, "\r\n")))."\r\n";
        }
    }

    /**
     * Get the data for the schedule file
     * @return array
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
     * @return array
     */
    public function toProfile(): array
    {
        return [
            'SngRelFileName' => $this->fileName,
        ];
    }


    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }


    /**
     * Add a single header value
     * @param string $key
     * @param string $value
     * @return void
     */
    public function addHeader(string $key, string $value): void
    {
        $this->headers[$key] = $value;
    }

    /**
     * Output headers to text as key/value pairs
     * @return string
     */
    public function headersToText(): string
    {
        if (!isset($this->headers['Editor'])) {
            $package = PackageService::info();
            $this->addHeader('Editor', config('app.name') . ' ' . $package['versionString']);
        }
        if (!isset($this->headers['Version'])) $this->addHeader('Version', 3);

        $text = '';
        foreach ($this->headers as $key => $value) {
            $text .= $this->line('#'.$key.'='.$value);
        }
        return $text;
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     */
    public function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }


    public function getCaption()
    {
        return $this->getTitle();
    }

}
