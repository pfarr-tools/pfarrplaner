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

use App\Liturgy\Item;
use ErrorException;
use ZipArchive;

class ImportableSchedule
{

    /** @var Schedule */
    protected $schedule;

    protected $files = [];
    protected $imports = [];

    protected $profiles;

    /**
     *
     */
    public function __construct()
    {
        $this->schedule = new Schedule();

        $this->profiles = new ObjectFile('SngProfilesCollection', 'TSngProfilesCollection', []);
        $this->profiles->setPathInZip('BGSngProfiles.cfg');

        $this->files[] = $this->schedule;
        $this->files[] = $this->profiles;
    }

    /**
     * Add a song
     * @param Item $item
     * @return void
     */
    public function addSongItem(Item $item)
    {
        $song = new Song($item);
        $this->schedule->addItem($song->toScheduleItem());
        $this->files[] = $song;
        $this->profiles->addItem($song->toProfile());
    }

    public function addSimpleTextItem(string $title, string $text, array $scheduleOptions = [], array $profileOptions = [], array $headerOptions = [], bool $showTitle = true)
    {
        $simpleText = new SimpleText($title, $text, $scheduleOptions, $profileOptions, $headerOptions, $showTitle);
        $this->schedule->addItem($simpleText->toScheduleItem());
        $this->files[] = $simpleText;
        $this->profiles->addItem($simpleText->toProfile());
    }

    public function addImport(string $title, string $file, bool $importFile = true)
    {
        if (!file_exists($file)) return;
        if ($importFile) $this->imports[] = $file;
        $this->schedule->addItem([
            'Caption' => utf8_decode($title),
            'Color' => SongBeamer::LOGO_COLOR,
            'FileName' => 'imports://'.basename($file),
            'Props' => [],
                                 ]);
    }

    /**
     * Add a psalm
     * @param Item $item
     * @return void
     */
    public function addPsalmItem(Item $item)
    {
        $psalm = new Psalm($item);
        $this->schedule->addItem($psalm->toScheduleItem());
        $this->files[] = $psalm;
        $this->profiles->addItem($psalm->toProfile());
    }

    /**
     * Add a colored section title
     * @param $title
     * @return void
     */
    public function addSectionTitle($title)
    {
        $this->schedule->addItem([
                                     'Caption' => utf8_decode($title),
                                     'Color' => '@@clWhite',
                                     'BGColor' => SongBeamer::LOGO_COLOR,
                                     'Props' => [],
                                 ]);
    }

    /**
     * Add an item simply as a greyed-out note
     * @param Item|string $item
     * @return void
     */
    public function addGeneralItem($item)
    {
        $title = is_string($item) ? $item : $item->title;
        $this->schedule->addItem([
                                     'Caption' => utf8_decode($title),
                                     'Color' => '@@clGray',
                                     'Props' => [],
                                 ]);
    }

    /**
     * Add a scripture reading
     * @param Item $item
     * @return void
     */
    public function addReadingItem(Item $item)
    {
        $this->schedule->addItem([
                                     'Caption' => utf8_decode(
                                         $item['title']
                                         . (isset($item->data['reference']) ? ': '.str_replace('–', '-', $item->data['reference']) : '')
                                     ),
                                     'Color' => '@@clGray',
                                     'Props' => [],
                                 ]);
    }


    /**
     * Create temporary ZIP file and dump contents to browser before deleting it
     * @return void
     * @throws ErrorException
     */
    public function toZip()
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'pp_sb_');
        $zip = new ZipArchive();
        $res = $zip->open($zipPath, ZipArchive::CREATE);
        if (!$res) {
            throw new ErrorException('Could not create ZIP file at ' . $zipPath);
        }

        // add static files
        $zip->addFile(base_path('assets/songbeamer/BGProfiles.cfg'), 'BGProfiles.cfg');

        // add song files
        /** @var AbstractFile $file */
        foreach ($this->files as $file) {
            $file->addToZipFile($zip);
        }

        // add imports
        foreach ($this->imports as $file) {
            $zip->addFile($file, 'Files/'.basename($file));
        }

        $zip->close();
        if (file_exists($zipPath)) {
            readfile($zipPath);
            unlink($zipPath);
        }
    }

    /**
     * @return Schedule
     */
    public function getSchedule(): Schedule
    {
        return $this->schedule;
    }

    /**
     * @param Schedule $schedule
     */
    public function setSchedule(Schedule $schedule): void
    {
        $this->schedule = $schedule;
    }

    /**
     * @return array
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * @param array $files
     */
    public function setFiles(array $files): void
    {
        $this->files = $files;
    }

    /**
     * @return array
     */
    public function getImports(): array
    {
        return $this->imports;
    }

    /**
     * @param array $imports
     */
    public function setImports(array $imports): void
    {
        $this->imports = $imports;
    }


}
