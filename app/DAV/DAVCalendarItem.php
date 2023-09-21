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

namespace App\DAV;

use App\Service;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Sabre\VObject\Component\VCalendar;

class DAVCalendarItem
{

    public const AUTO_WARNING = '<p><small>Bitte beachte: Dies ist ein automatisch vom Pfarrplaner erzeugter Eintrag. Änderungen können jederzeit ohne Vorwarnungen vom Pfarrplaner überschrieben werden.</small></p>';

    /** @var Model */
    protected $object;

    /** @var VCalendar */
    protected $vObject;

    /** @var string $key */
    protected $key = '';

    /** @var array $config Config */
    protected $config = [];

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
        $this->object = $object;
        $this->key = $key;
        $this->config = $config;

        $categories = array_merge(['Pfarrplaner'], $categories);

        $this->vObject = new VCalendar();
        $vEvent = $this->vObject->create('VEVENT');
        $vEvent->UID = $this->objectId(true);
        if ($config['allDay'] ?? false) {
            $vEvent->DTSTART = $start->format('Ymd');
            $vEvent->DTSTART['VALUE'] = 'DATE';
            $vEvent->DTEND = $end->format('Ymd');
            $vEvent->DTEND['VALUE'] = 'DATE';
        } else {
            $vEvent->DTSTART = $start;
            $vEvent->DTEND = $end;
        }
        $vEvent->DTSTAMP = $object->created_at;
        $vEvent->CREATED = $object->created_at;
        $vEvent->SUMMARY = $title;
        $vEvent->LOCATION = $location;
        $vEvent->DESCRIPTION = $body;
        $vEvent->TRANSP = ($config['busy'] ?? 'true') ? 'OPAQUE' : 'TRANSPARENT';
        $vEvent->{'X-MICROSOFT-CDO-BUSYSTATUS'} = $config['busyStatus'] ?? 'BUSY';
        $vEvent->CATEGORIES = join(',', $categories);
        $vEvent->SEQUENCE = ($object->created_at == $object->updated_at) ? 1 : $object->updated_at->timestamp;
        $this->vObject->add($vEvent);
    }

    protected function getDomain()
    {
        return parse_url(url('/'), PHP_URL_HOST);
    }

    /**
     * Get a calendarItem via it's URI
     * @param $uri
     * @return DAVCalendarItem|null
     * @throws \ErrorException
     */
    public static function fromUri($uri): DAVCalendarItem|null
    {
        $uri = Str::replace('.ics', '', $uri);
        $tmp = explode('--', $uri);
        $class = Str::replace('-', '\\', $tmp[0]);
        if (!is_subclass_of($class, Model::class)) {
            throw new \ErrorException($class . ' is not a model class');
        }
        $object = $class::find($tmp[1]);
        if (!$object) {
            return null;
        }
        if (!$object instanceof HasDAVCalendarItems) {
            throw new \ErrorException($class . ' does not implement the HasDAVCalendarItems interface');
        }
        return $object->getCalendarItem($tmp[2] ?? '');
    }

    /**
     * @return mixed
     */
    public function getVObject(): VCalendar
    {
        return $this->vObject;
    }

    protected function objectId($withDomain = false)
    {
        return Str::replace('\\', '-', get_class($this->object)) . '--' . $this->object->id
            . (empty($this->key) ? '' : '--' . $this->key)
            . ($withDomain ? '@' . $this->getDomain() : '');
    }

    public function toCalendarObject(): array
    {
        $calData = $this->getVObject()->serialize();
        return [
            'id' => $this->objectId(),
            'uri' => $this->objectId() . '.ics',
            'calendardata' => $calData,
            'etag' => '"' . sha1($calData) . '"',
            'lastmodified' => $this->object->updated_at->timestamp,
        ];
    }


}
