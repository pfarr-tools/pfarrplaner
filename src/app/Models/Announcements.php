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

namespace App\Models;

use App\Models\Calendar\Occurence;
use App\Models\Places\City;
use App\Models\Rites\Baptism;
use App\Models\Rites\Funeral;
use App\Models\Rites\Wedding;
use App\Models\Scopes\ServicesOnlyScope;
use App\Services\NameService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class Announcements
{

    public const DEFAULT_BAPTISM_VOTE = 'Christus hat der Kirche den Auftrag gegeben: Gehet hin und machet zu Jüngern alle Völker und taufet sie auf den Namen des Vaters und des Sohnes und des Heiligen Geistes.';
    public const DEFAULT_FUNERAL_VOTE = 'Unser keiner lebt sich selber, und keiner stirbt sich selber. Leben wir, so leben wir dem Herrn; sterben wir, so sterben wir dem Herrn. Darum: Wir leben oder sterben, so sind wir des Herrn. Denn dazu ist Christus gestorben und wieder lebendig geworden, dass er über Tote und Lebende Herr sei.';
    public const DEFAULT_WEDDING_VOTE = 'Vater im Himmel, wir bitten für dieses Hochzeitspaar. Begleite sie auf ihrem gemeinsamen Weg. Lass sie deine Liebe erfahren und stärke ihre Liebe zueinander in guten und in schweren Tagen.';

    /** @var Service */
    protected Service $service;

    /** @var City */
    protected City $city;

    /** @var Collection */
    protected $events;
    /** @var Collection */
    protected $featuredEvents;

    /** @var Collection */
    protected $texts;

    /** @var Collection */
    protected $baptisms;

    /** @var Collection */
    protected $funerals;

    /** @var Collection */
    protected $weddings;

    /** @var bool Use tabs \t in output? */
    protected bool $useTabs = false;

    protected $eventListKeyFormat = 'dddd, D. MMMM';

    public function __construct(Service $service, $city, $excludeRegularWeekly = false, $eventListKeyFormat = 'dddd, D. MMMM')
    {
        $this->service = $service;
        if (is_a($city, City::class)) $city = [$city->id];
        elseif (!is_array($city)) $city = [$city];
        $city = City::whereIn('id', $city)->get();
        $this->eventListKeyFormat = $eventListKeyFormat;

        $lastWeek = Carbon::createFromTimeString($service->date->format('Y-m-d') . ' 0:00:00 last Sunday');
        $nextWeek = $lastWeek->copy()->addWeeks(2)->setTime(
            23,
            59,
            59
        );

        $this->events = Occurence::with('event')
            ->between($service->date->copy()->addHour(2), $nextWeek)
            ->whereHas('service', function ($query) use ($service, $city, $excludeRegularWeekly) {
                $query->withoutGlobalScope(ServicesOnlyScope::class);
                $query->where('id', '!=', $service->id);
                $query->inCities($city)->displayable($service->date);
                if ($excludeRegularWeekly ?? false) {
                    // do not include events that are (1) not services and (2) repeat every week
                    $query->where(function ($q2) {
                        $q2->where('event_class', 'service')
                            ->orWhere('rrule', 'not like', '%FREQ=WEEKLY;INTERVAL=1%');
                    });
                }
            })
            ->orderBy('start')
            ->get();

        // expand multi-full-day events to each of the days
        $this->events = static::expandMultiDayEvents($this->events)->groupBy(function ($event) {
                return $event->start->setTimezone('Europe/Berlin')->isoFormat($this->eventListKeyFormat);
            }, function ($event) {
                return $event->start->format('Hi');
            });

        $this->featuredEvents = Occurence::with('event')
            ->whereHas('service', function ($query) use ($service, $city) {
                $query->inCities($city)->displayable($service->date);
            })
            ->adRunningAt('bekanntgaben', $service->date)
            ->orderBy('start')
            ->get();

        $this->funerals = Funeral::where('announcement', $service->date->format('Y-m-d'))
            ->whereHas(
                'service',
                function ($query) use ($service, $city) {
                    $query->inCities($city)
                        ->displayable($service->date);
                }
            )
            ->get();

        $this->weddings = Wedding::with('service')
            ->whereHas(
                'service',
                function ($query) use ($service, $nextWeek, $city) {
                    $query->between($service->date, $nextWeek)
                        ->inCities($city)
                        ->displayable($service->date)
                        ->ordered();
                }
            )->get()
            ->groupBy(function ($rite) use ($service) {
                $day = ($rite->service->date->format('Ymd') == $service->date->format(
                        'Ymd'
                    )) ? 'heute' : 'am ' . $rite->service->date->isoFormat('dddd, DD. MMMM,');
                return 'Im Gottesdienst ' . $day . ' um ' . $rite->service->timeText(
                    ) . ' in ' . $rite->service->city->name;
            });

        $this->baptisms = Baptism::with('service')
            ->whereHas(
                'service',
                function ($query) use ($service, $nextWeek, $city) {
                    $query->between($service->date, $nextWeek)
                        ->inCities($city)
                        ->displayable($service->date)
                        ->ordered();
                }
            )->get()
            ->reject(function ($rite) use ($service) {
                return $rite->service->id == $service->id;
            })
            ->groupBy(function ($rite) use ($service) {
                $day = ($rite->service->date->format('Ymd') == $service->date->format(
                        'Ymd'
                    )) ? 'heute' : 'am ' . $rite->service->date->isoFormat('dddd, DD. MMMM,');
                return 'Im Gottesdienst ' . $day . ' um ' . $rite->service->timeText(
                    ) . ' in ' . $rite->service->city->name;
            });
    }

    protected static function defaultCallback(string $key, array $items)
    {
        return count($items) ? join("\n", $items)."\n" : '';
    }

    public function render($lastService = '', $offerings = '', $callback = null, $separator = null, array $remove = [])
    {
        $data = $this->renderArray($lastService, $offerings);

        $result = [];
        foreach ($data as $key => $items) {
            if (!in_array($key, $remove)) {
                $result[$key] = $callback ? $callback($key, $items) : static::defaultCallback($key, $items);
            }
        }
        return $separator ? collect($result)->reject(function ($item) { return $item == ''; })->join($separator) : $result;
    }

    public function renderArray($lastService = '', $offerings = ''): array
    {
        return [
            'thanks' => $this->getThanksArray(),
            'last_offerings' => $this->getLastOfferingsArray($lastService, $offerings),
            'offerings' => $this->getOfferingsArray(),
            'events_intro' => $this->getEventsIntroArray(),
            'events' => $this->getEventsArray(),
            'featured_events_intro' => $this->getFeaturedEventsIntroArray(),
            'featured_events' => $this->getFeaturedEventsArray(),
            'events_extro' => $this->getEventsExtroArray(),
            'rites' => $this->getRitesArray(),
            'custom_announcements' => $this->getCustomAnnouncementsArray(),
            'final_song' => $this->getFinalSongArray(),
        ];
    }

    public function getRitesArray(): array
    {
        $paragraphs = array_merge($this->getBaptismsArray(), $this->getWeddingsArray(), $this->getFuneralsArray());
        array_shift($paragraphs);
        return $paragraphs;
    }

    public function getBaptismsArray(): array
    {
        if ($this->baptisms->count() == 0) return [];
        $paragraphs = [''];
        foreach ($this->baptisms as $day => $baptisms) {
            $paragraphs[] = join(' ', [
                $day,
                Str::choice($baptisms->count(), 'wird', 'werden'),
                $baptisms->map(function ($baptism) {
                    return NameService::fromName($baptism->candidate_name)->format(NameService::FIRST_LAST);
                })->join(', ', 'und'),
                'getauft.'
            ]);
        }
        $paragraphs[] = '';
        $paragraphs[] = static::DEFAULT_BAPTISM_VOTE;
        return $paragraphs;
    }

    public function getFuneralsArray(): array
    {
        if ($this->funerals->count() == 0) return [];
        $paragraphs = collect([
            '',
        ])->merge(
            $this->funerals->map(function ($funeral) {
                if ($funeral->service->location) {
                    $location = ($funeral->service->location->at_text ?: 'auf dem Friedhof').' '
                    .($funeral->service->location->general_location_name ?: 'in '.$funeral->service->location->city->name);
                } else {
                    $location = 'in '.$funeral->service->city->name;
                }

                return 'Am '.$funeral->dod->isoFormat('D. MMMM').' ist '
                    .NameService::fromName($funeral->buried_name)->format(NameService::FIRST_LAST)
                    .($funeral->buried_address ? ', '.$funeral->buried_address.',' : '')
                    .($funeral->age ? ' im Alter von '.$funeral->age.' Jahren' : '')
                    .' verstorben. Die '.$funeral->type
                    .($funeral->service->date < $this->service->date ? ' fand bereits am ' : ' findet am ')
                    .$funeral->service->date->isoFormat('dddd, DD. MMMM').', um '.$funeral->service->timeText()
                    .' '.$location.' statt.';
            })
        );
        $paragraphs[] = '';
        $paragraphs[] = static::DEFAULT_FUNERAL_VOTE;
        return $paragraphs->toArray();
    }

    public function getWeddingsArray(): array
    {
        if ($this->weddings->count() == 0) return [];
        $paragraphs = [''];
        foreach ($this->weddings as $day => $weddings) {
            $paragraphs[] = join(' ', [
                $day,
                'werden',
                $weddings->map(function ($wedding) {
                    return NameService::fromName($wedding->spouse1_name)->format(NameService::FIRST_LAST)
                        . ' und ' . NameService::fromName($wedding->spouse2_name)->format(NameService::FIRST_LAST);
                })->join('; ', 'und'),
                'kirchlich getraut.'
            ]);
        }
        $paragraphs[] = '';
        $paragraphs[] = static::DEFAULT_WEDDING_VOTE;
        return $paragraphs;
    }

    public function getRitesText($separator = ''): string
    {
        return join($separator, $this->getRitesArray());
    }

    public function getCustomAnnouncementsArray(): array
    {
        return [];
    }

    public function getCustomAnnouncementsText($separator = ''): string
    {
        return join($separator, $this->getCustomAnnouncementsArray());
    }

    public function getFinalSongArray(): array
    {
        $liturgy = collect();
        foreach($this->service->liturgyBlocks->pluck('items') as $items) $liturgy->push(...$items);
        $afterAnnouncements = false;
        $finalSong = $liturgy->filter(function ($item) use (&$afterAnnouncements) {
            if (in_array($item->title, ['Abkündigungen', 'Ankündigungen', 'Bekanntgaben', 'Bekanntmachungen'])) $afterAnnouncements = true;
            return $afterAnnouncements && ($item->data_type == 'song');
        })->first();

        if (!$finalSong) return [];

        return ['Wir singen:', $finalSong->getHelper()->getTitleText(). (($item->data['verses'] ?? '') ? ', ' . $item->data['verses'] : '')];
    }

    public function getFinalSongText($separator = ''): string
    {
        return join($separator, $this->getFinalSongArray());
    }

    public function getEventsIntroArray()
    {
        if ($this->events->count() == 0) {
            return [];
        }
        return [
            'Ganz herzlich laden wir Sie '
            . Str::choice($this->events->count(), 'zu folgender Veranstaltung', 'zu den folgenden Veranstaltungen')
            . ' ein:'
        ];
    }

    public function getEventsIntroText($separator = ''): string
    {
        return join($separator, $this->getEventsIntroArray());
    }

    public function getFeaturedEventsIntroArray()
    {
        if ($this->featuredEvents->count() == 0) {
            return [];
        }
        return [
            'Ganz besonders möchten wir Sie auf folgende '
            . Str::choice($this->events->count(), 'Veranstaltung', 'Veranstaltungen')
            . ' hinweisen:'
        ];
    }

    public function getFeaturedEventsIntroText($separator = ''): string
    {
        return join($separator, $this->getFeaturedEventsIntroArray());
    }

    public function getThanksArray(): array
    {
        $music = $this->service->organists;
        foreach (['Musik', 'Band', 'Klavier', 'Schlagzeug', 'Cajon', 'Bass'] as $instrument) {
            if ($people = $this->service->participantsByCategory($instrument)) {
                $music->merge($people);
            }
        }
        $musicians = collect();
        foreach ($music as $musician) {
            $musicians->push(NameService::fromUser($musician)->format(NameService::FIRST_LAST));
        }

        return [
            'Herzlichen Dank an ' . $musicians->join(
                ', ',
                ' und '
            ) . ' für die schöne musikalische Begleitung des Gottesdiensts.',
        ];
    }

    protected function tab()
    {
        return $this->useTabs ? "\t" : ' ';
    }

    public function getEventsArray(): array
    {
        $paragraphs = [];
        foreach ($this->events as $day => $events) {
            $paragraphs[] = '';
            $paragraphs[] = $day;
            foreach ($events as $event) {
                $paragraphs[] = ($event->event->is_allday ? '' : $event->event->timeText() . $this->tab())
                    . Str::replace('&', '&amp;', $event->event->titleText(false, false))
                    . (count($event->event->pastors ?? []) ?
                        ' mit ' . $event->event->pastors->map(function ($pastor) {
                            return NameService::fromUser($pastor)->format(NameService::TITLE_FIRST_LAST);
                        })->join(', ')
                        : '')
                    . ' (' . $event->event->locationTextWithCity . ')'
                    . ($event->event->description ? "\n" . $event->event->description : '');
            }
        }

        array_shift($paragraphs);
        return $paragraphs;
    }


    public function getEventsText($separator = ''): string
    {
        return join($separator, $this->getEventsArray());
    }

    public function getFeaturedEventsArray(): array
    {
        $paragraphs = [];
        foreach ($this->featuredEvents as $event) {
            $paragraphs[] = '';
            $paragraphs[] = $event->start->setTimeZone('Europe/Berlin')->isoFormat('dddd, DD. MMMM')
                . ($event->event->is_allday ? ($event->start->setTimeZone('Europe/Berlin')->format('Ymd') != $event->end->setTimeZone('Europe/Berlin')->format('Ymd') ? ' - '.$event->end->setTimeZone('Europe/Berlin')->isoFormat('dddd, DD. MMMM') : '') : ', ' . $event->service->timeText())
                . ', ' . $event->service->locationTextWithCity;
            $paragraphs[] = $event->service->titleText(false);
            $paragraphs[] = $event->getAdText('newsletter');
        }
        array_shift($paragraphs);
        return $paragraphs;
    }

    public function getFeaturedEventsText($separator = ''): string
    {
        return join($separator, $this->getFeaturedEventsArray());
    }

    public function getEventsExtroArray(): array
    {
        $sources = collect(['in den Aushängen']);
        if ($this->service->city->homepage) {
            $sources->push('auf unserer Homepage');
        }
        if ($this->service->city->communiapp_token) {
            $sources->push('in unserer App');
        }


        return [
            'Alle weiteren Veranstaltungen finden Sie ' . $sources->join(', ', ' oder ') . '.',
        ];
    }

    public function getEventsExtroText($separator = ''): string
    {
        return join($separator, $this->getEventsExtroArray());
    }


    public function getThanksText(string $separator = ''): string
    {
        return join($separator, $this->getThanksArray());
    }


    public function getLastOfferingsArray(string $lastService = '', string $offerings = ''): array
    {
        $paragraphs = [];
        if ($lastService) {
            $lastService = is_object($lastService) ? $lastService : Carbon::parse($lastService);
            if ($offerings == "0,00\u{A0}€") {
                $offerings = '';
            }
            $paragraphs[] = 'Das Opfer vom letzten ' . $lastService->isoFormat('dddd') . ' ergab ' . ($offerings ?: '______________') . '.';
        }
        return $paragraphs;
    }

    public function getLastOfferingsText($separator = ''): string
    {
        return join($separator, $this->getLastOfferingsArray());

    }

    public function getOfferingsArray(string $lastService = '', string $offerings = ''): array
    {
        $paragraphs = [];
        if (!empty($this->service->offeringGoal())) {
            $paragraphs[] = 'Das Opfer heute erbitten wir für: ' . $this->service->offeringGoal();
        } else {
            $paragraphs[] = 'Das Opfer heute erbitten wir für die vielfältigen Aufgaben in unserer Kirchengemeinde.';
        }

        if ($this->service->offering_text) {
            $paragraphs[] = '';
            $paragraphs[] = $this->service->offering_text;
            $paragraphs[] = '';
        }
        $paragraphs[] = 'Herzlichen Dank für alles, was Sie geben.';
        return $paragraphs;
    }

    public function getOfferingsText(string $lastService = '', string $offerings = '', string $separator = ''): string
    {
        return join($separator, $this->renderOfferingsArray($lastService, $offerings));
    }

    public function getService(): Service
    {
        return $this->service;
    }

    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function getFeaturedEvents(): Collection
    {
        return $this->featuredEvents;
    }

    public function getTexts(): Collection
    {
        return $this->texts;
    }

    public function getBaptisms(): Collection
    {
        return $this->baptisms;
    }

    public function getFunerals(): Collection
    {
        return $this->funerals;
    }

    public function getWeddings(): Collection
    {
        return $this->weddings;
    }

    public function getCity(): City
    {
        return $this->city;
    }

    public function isUseTabs(): bool
    {
        return $this->useTabs;
    }

    public function setUseTabs(bool $useTabs): void
    {
        $this->useTabs = $useTabs;
    }

    public function getEventListKeyFormat(): string
    {
        return $this->eventListKeyFormat;
    }

    public function setEventListKeyFormat(string $eventListKeyFormat): void
    {
        $this->eventListKeyFormat = $eventListKeyFormat;
    }


    public static function expandMultiDayEvents(Collection $events): Collection {
        $extra = collect();
        foreach ($events as $event) {
            if (!$event->event->is_allday) continue;
            if ($event->start->setTimezone('Europe/Berlin')->format('Ymd') == $event->end->setTimezone('Europe/Berlin')->format('Ymd')) continue;
            $cursor = $event->start->copy()->addDay();
            while ($cursor < $event->end) {
                $newEvent = clone $event;
                $newEvent->event = clone $event->event;
                $newEvent->start = $cursor;
                $newEvent->event->date = $cursor;
                $extra->push($newEvent);
                $cursor->addDay();
            }
        }
        return $events->concat($extra)->sortBy('start');
    }

}
