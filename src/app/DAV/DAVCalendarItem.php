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

namespace App\DAV;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;

class DAVCalendarItem
{
    public const AUTO_WARNING = '<p><small>Bitte beachte: Dies ist ein automatisch vom Pfarrplaner erzeugter Eintrag. Änderungen können jederzeit ohne Vorwarnungen vom Pfarrplaner überschrieben werden.</small></p>';

    /** @var Model&object{created_at: Carbon, updated_at: Carbon, id: int|string} */
    protected $object;

    /** @var VCalendar */
    protected $vObject;

    /** @var string */
    protected $key = '';

    /** @var array<string, bool|string> Kalenderkonfiguration des Eintrags */
    protected $config = [];

    /**
     * Erzeugt einen Kalendereintrag aus einem Modell und den übergebenen Terminangaben.
     *
     * @param  Model  $object  Modell, aus dem der Eintrag erzeugt wird.
     * @param  string  $title  Titel des Kalendereintrags.
     * @param  Carbon  $start  Beginn des Termins.
     * @param  Carbon  $end  Ende des Termins.
     * @param  string  $location  Ort des Termins.
     * @param  string  $body  Beschreibung des Termins.
     * @param  string  $key  Optionaler Schlüssel zur Unterscheidung mehrerer Einträge.
     * @param  array<string, bool|string>  $config  Schalter für Ganztagstermine und BUSY-Status.
     * @param  string[]  $categories  Zusätzliche Kategorien des Kalendereintrags.
     */
    public function __construct(
        Model $object,
        string $title,
        Carbon $start,
        Carbon $end,
        string $location,
        string $body,
        string $key = '',
        array $config = [],
        array $categories = [],
    ) {
        /** @var Model&object{created_at: Carbon, updated_at: Carbon, id: int|string} $object */
        $this->object = $object;
        $this->key = $key;
        $this->config = $config;

        $categories = array_merge(['Pfarrplaner'], $categories);

        $this->vObject = new VCalendar;
        /** @var VEvent $vEvent */
        $vEvent = $this->vObject->create('VEVENT');
        /**
         * Setzt eine Kalendereigenschaft und ersetzt dabei einen vorhandenen Wert.
         *
         * @param  VEvent  $event  Kalendereintrag.
         * @param  string  $name  Name der Eigenschaft.
         * @param  mixed  $value  Wert der Eigenschaft.
         */
        $setProperty = static function (VEvent $event, string $name, mixed $value): void {
            $event->remove($name);
            $event->add($name, $value);
        };
        $setProperty($vEvent, 'UID', $this->objectId(true));
        if ($config['allDay'] ?? false) {
            $dtStart = $this->vObject->createProperty('DTSTART', $start->format('Ymd'));
            $dtStart['VALUE'] = 'DATE';
            $vEvent->add($dtStart);
            $dtEnd = $this->vObject->createProperty('DTEND', $end->format('Ymd'));
            $dtEnd['VALUE'] = 'DATE';
            $vEvent->add($dtEnd);
        } else {
            $setProperty($vEvent, 'DTSTART', $start);
            $setProperty($vEvent, 'DTEND', $end);
        }
        $setProperty($vEvent, 'DTSTAMP', $this->object->created_at);
        $setProperty($vEvent, 'CREATED', $this->object->created_at);
        $setProperty($vEvent, 'SUMMARY', $title);
        $setProperty($vEvent, 'LOCATION', $location);
        $setProperty($vEvent, 'DESCRIPTION', $body);
        $setProperty($vEvent, 'TRANSP', ($config['busy'] ?? 'true') ? 'OPAQUE' : 'TRANSPARENT');
        $setProperty($vEvent, 'X-MICROSOFT-CDO-BUSYSTATUS', $config['busyStatus'] ?? 'BUSY');
        $setProperty($vEvent, 'CATEGORIES', implode(',', $categories));
        $setProperty($vEvent, 'SEQUENCE', ($this->object->created_at == $this->object->updated_at) ? 1 : $this->object->updated_at->timestamp);
        $this->vObject->add($vEvent);
    }

    /**
     * @return string|false
     */
    protected function getDomain()
    {
        return parse_url(url('/'), PHP_URL_HOST) ?? false;
    }

    /**
     * Get a calendarItem via it's URI
     *
     * @throws \ErrorException
     */
    /**
     * @param  string  $uri  Calendar item URI.
     */
    public static function fromUri($uri): ?DAVCalendarItem
    {
        $uri = Str::replace('.ics', '', (string) $uri);
        $tmp = explode('--', $uri);
        $class = Str::replace('-', '\\', $tmp[0]);
        if (! is_subclass_of($class, Model::class)) {
            throw new \ErrorException($class.' is not a model class');
        }
        $object = $class::find($tmp[1]);
        if (! $object) {
            return null;
        }
        if (! $object instanceof HasDAVCalendarItems) {
            throw new \ErrorException($class.' does not implement the HasDAVCalendarItems interface');
        }

        return $object->getCalendarItem($tmp[2] ?? '');
    }

    public function getVObject(): VCalendar
    {
        return $this->vObject;
    }

    /**
     * @param  bool  $withDomain  Whether the domain is appended.
     * @return string
     */
    protected function objectId($withDomain = false)
    {
        return Str::replace('\\', '-', get_class($this->object)).'--'.$this->object->id
            .(empty($this->key) ? '' : '--'.$this->key)
            .($withDomain ? '@'.$this->getDomain() : '');
    }

    /**
     * @return array<string, mixed>
     */
    public function toCalendarObject(): array
    {
        $calData = $this->getVObject()->serialize();

        return [
            'id' => $this->objectId(),
            'uri' => $this->objectId().'.ics',
            'calendardata' => $calData,
            'etag' => '"'.sha1($calData).'"',
            'lastmodified' => $this->object->updated_at->timestamp,
        ];
    }
}
