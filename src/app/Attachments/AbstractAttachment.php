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

namespace App\Attachments;

use Illuminate\Support\Str;

abstract class AbstractAttachment
{
    /** @var string Anzeigetitel des Attachments. */
    protected static $title = '';

    /** @var string Beschreibung des Attachments. */
    protected static $description = '';

    /** @var string CSS-Klasse des Symbols. */
    protected static $icon = '';

    /** @var string Dateiendung ohne führenden Punkt. */
    protected static $extension = '';

    /** @var string MIME-Typ der erzeugten Datei. */
    protected static $mimeType = '';

    /** @var string Kurzzeichen für den Dateinamen. */
    protected static $callSign = '';

    /** @var string Titel für den Dateinamen. */
    protected static $fileTitle = '';

    /** @var bool Gibt an, ob das Attachment eine Einrichtungsseite besitzt. */
    protected static $hasSetup = false;

    /**
     * Ermittelt den Schlüssel des Attachments aus dem Klassennamen.
     *
     * @return string Schlüssel des Attachments.
     */
    public static function getKey()
    {
        return Str::lcfirst(Str::replace('Attachment', '', Str::afterLast(get_called_class(), '\\')));
    }

    /**
     * Liefert die Metadaten des Attachments für die Auswahlliste.
     *
     * @return array{
     *     key: string,
     *     class: class-string,
     *     title: string,
     *     description: string,
     *     icon: string,
     *     extension: string,
     *     mimeType: string,
     *     hasSetup: bool
     * } Metadaten des Attachments.
     */
    public static function getInfo()
    {
        return [
            'key' => static::getKey(),
            'class' => static::class,
            'title' => static::$title,
            'description' => static::$description,
            'icon' => static::$icon,
            'extension' => static::$extension,
            'mimeType' => static::$mimeType,
            'hasSetup' => static::$hasSetup,
        ];
    }

    /**
     * Erzeugt den Dateinamen aus Kurzzeichen, Titel und Endung.
     *
     * @return string Dateiname des Attachments.
     */
    public function getFileName()
    {
        return trim((static::$callSign ? static::$callSign.'_' : '').static::$fileTitle).'.'.static::$extension;
    }

    /**
     * Erzeugt die Datei und gibt sie zum Download aus.
     *
     * @return \Symfony\Component\HttpFoundation\Response Dateiantwort.
     */
    abstract public function download();

    /**
     * Erzeugt ein Attachment für den Eintrag mit der angegebenen ID.
     *
     * @param  int|string  $id  ID des zugehörigen Eintrags.
     * @return static Attachment für den angegebenen Eintrag.
     */
    abstract public static function fromId($id);
}
