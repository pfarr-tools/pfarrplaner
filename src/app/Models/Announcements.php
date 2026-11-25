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

namespace App\Models;

use App\Liturgy\ItemHelpers\PsalmItemHelper;
use App\Liturgy\ItemHelpers\SongItemHelper;
use App\Models\Calendar\Occurence;
use App\Models\Liturgy\Item;
use App\Models\Places\City;
use App\Models\Rites\Baptism;
use App\Models\Rites\Funeral;
use App\Models\Rites\Wedding;
use App\Models\Scopes\ServicesOnlyScope;
use App\Services\NameService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Announcements
{
    public const DEFAULT_BAPTISM_VOTE = 'Christus hat der Kirche den Auftrag gegeben: Gehet hin und machet zu Jüngern alle Völker und taufet sie auf den Namen des Vaters und des Sohnes und des Heiligen Geistes.';

    public const DEFAULT_FUNERAL_VOTE = 'Unser keiner lebt sich selber, und keiner stirbt sich selber. Leben wir, so leben wir dem Herrn; sterben wir, so sterben wir dem Herrn. Darum: wir leben oder sterben, so sind wir des Herrn. Denn dazu ist Christus gestorben und wieder lebendig geworden, dass er über Tote und Lebende Herr sei. (Röm 14,7–9)';

    public const DEFAULT_WEDDING_VOTE = 'Vater im Himmel, wir bitten für dieses Hochzeitspaar. Begleite sie auf ihrem gemeinsamen Weg. Lass sie deine Liebe erfahren und stärke ihre Liebe zueinander in guten und in schweren Tagen.';

    protected Service $service;

    protected City $city;

    /** @var Collection<array-key, Collection<int, Occurence>> */
    protected $events;

    /** @var Collection<int, Occurence> */
    protected $featuredEvents;

    /** @var Collection<array-key, mixed> */
    protected $texts;

    /** @var Collection<array-key, Collection<int, Baptism>> */
    protected $baptisms;

    /** @var EloquentCollection<int, Funeral> */
    protected $funerals;

    /** @var Collection<array-key, Collection<int, Wedding>> */
    protected $weddings;

    /** @var bool Use tabs \t in output? */
    protected bool $useTabs = false;

    /** @var string */
    protected $eventListKeyFormat = 'dddd, D. MMMM';

    /**
     * @param  City|array<int, int|City>|int  $city  Cities included in the announcements.
     * @param  bool  $excludeRegularWeekly  Whether weekly recurring services are excluded.
     * @param  string  $eventListKeyFormat  Date format used as the event group key.
     */
    public function __construct(Service $service, $city, $excludeRegularWeekly = false, $eventListKeyFormat = 'dddd, D. MMMM')
    {
        $this->service = $service;
        if ($city instanceof City) {
            $city = [$city->id];
        } elseif (! is_array($city)) {
            $city = [$city];
        }
        $city = City::whereIn('id', $city)->get();
        $this->eventListKeyFormat = $eventListKeyFormat;

        /** @var Carbon $serviceDate Service date provided by the datetime cast. */
        $serviceDate = $service->date;
        $lastWeek = Carbon::createFromTimeString($serviceDate->format('Y-m-d').' 0:00:00 last Sunday');
        $nextWeek = $lastWeek->copy()->addWeeks(2)->setTime(
            23,
            59,
            59
        );

        $events = Occurence::with('event')
            ->between($serviceDate->copy()->addHours(2), $nextWeek)
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
        $this->events = static::expandMultiDayEvents($events)->groupBy(function ($event) {
            return $event->start->setTimezone('Europe/Berlin')->isoFormat($this->eventListKeyFormat);
        });

        $this->featuredEvents = Occurence::with('event')
            ->whereHas('service', function ($query) use ($service, $city) {
                $query->inCities($city)->displayable($service->date);
            })
            ->adRunningAt('bekanntgaben', $serviceDate)
            ->orderBy('start')
            ->get();

        $this->funerals = Funeral::where('announcement', $serviceDate->format('Y-m-d'))
            ->whereHas(
                'service',
                function ($query) use ($service, $city) {
                    $query->inCities($city)
                        ->displayable($service->date);
                }
            )
            ->get();

        /** @var Collection<array-key, Collection<int, Wedding>> $weddings Weddings grouped by service. */
        $weddings = Wedding::with('service')
            ->whereHas(
                'service',
                function ($query) use ($serviceDate, $nextWeek, $city) {
                    $query->between($serviceDate, $nextWeek)
                        ->inCities($city)
                        ->displayable($serviceDate)
                        ->ordered();
                }
            )->get()
            ->groupBy(function ($rite) use ($serviceDate) {
                /** @var Service $riteService Service relation of the rite. */
                $riteService = $rite->service;
                /** @var Carbon $riteDate Service date provided by the datetime cast. */
                $riteDate = $riteService->date;
                $day = ($riteDate->format('Ymd') == $serviceDate->format(
                    'Ymd'
                )) ? 'heute' : 'am '.$riteDate->isoFormat('dddd, DD. MMMM,');

                return 'Im Gottesdienst '.$day.' um '.$riteService->timeText(
                ).' in '.$riteService->city?->name;
            });
        $this->weddings = $weddings;

        /** @var Collection<array-key, Collection<int, Baptism>> $baptisms Baptisms grouped by service. */
        $baptisms = Baptism::with('service')
            ->whereHas(
                'service',
                function ($query) use ($serviceDate, $nextWeek, $city) {
                    $query->between($serviceDate, $nextWeek)
                        ->inCities($city)
                        ->displayable($serviceDate)
                        ->ordered();
                }
            )->get()
            ->reject(function ($rite) use ($service) {
                /** @var Service $riteService Service relation of the rite. */
                $riteService = $rite->service;

                return $riteService->id == $service->id;
            })
            ->groupBy(function ($rite) use ($serviceDate) {
                /** @var Service $riteService Service relation of the rite. */
                $riteService = $rite->service;
                /** @var Carbon $riteDate Service date provided by the datetime cast. */
                $riteDate = $riteService->date;
                $day = ($riteDate->format('Ymd') == $serviceDate->format(
                    'Ymd'
                )) ? 'heute' : 'am '.$riteDate->isoFormat('dddd, DD. MMMM,');

                return 'Im Gottesdienst '.$day.' um '.$riteService->timeText(
                ).' in '.$riteService->city?->name;
            });
        $this->baptisms = $baptisms;
    }

    /**
     * @param  list<string>  $items  Paragraphs to join.
     * @return string
     */
    protected static function defaultCallback(int|string $key, array $items)
    {
        return count($items) ? implode("\n", $items)."\n" : '';
    }

    /**
     * @param  mixed  $lastService  Last service description.
     * @param  mixed  $offerings  Offering amount.
     * @param  (callable(mixed, mixed): mixed)|null  $callback  Optional paragraph renderer.
     * @param  string|null  $separator  Separator for a flattened result.
     * @param  list<string>  $remove  Keys to omit.
     * @return array<string, mixed>|string
     */
    public function render($lastService = '', $offerings = '', $callback = null, $separator = null, array $remove = [])
    {
        $data = $this->renderArray(self::stringify($lastService), self::stringify($offerings));

        $result = [];
        foreach ($data as $key => $items) {
            if (! in_array($key, $remove)) {
                $result[$key] = $callback ? $callback($key, $items) : static::defaultCallback($key, $items);
            }
        }

        return $separator ? self::stringify(collect($result)->reject(function ($item) {
            return $item == '';
        })->join($separator)) : $result;
    }

    /**
     * @return array<string, list<string>>
     */
    public function renderArray(string $lastService = '', string $offerings = ''): array
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

    /**
     * @return list<string>
     */
    public function getRitesArray(): array
    {
        $paragraphs = array_merge($this->getBaptismsArray(), $this->getWeddingsArray(), $this->getFuneralsArray());
        array_shift($paragraphs);

        return $paragraphs;
    }

    /**
     * @return list<string>
     */
    public function getBaptismsArray(): array
    {
        if ($this->baptisms->count() == 0) {
            return [];
        }
        $paragraphs = [''];
        foreach ($this->baptisms as $day => $baptisms) {
            $paragraphs[] = implode(' ', [
                (string) $day,
                self::stringify(Str::choice($baptisms->count(), 'wird', 'werden')),
                $baptisms->map(function ($baptism) {
                    return NameService::fromName(self::stringify($baptism->candidate_name))->format(NameService::FIRST_LAST);
                })->join(', ', 'und'),
                'getauft.',
            ]);
        }
        $paragraphs[] = '';
        $paragraphs[] = static::DEFAULT_BAPTISM_VOTE;

        /** @var list<string> $result Baptism paragraphs including the closing vote. */
        $result = $paragraphs;

        return $result;
    }

    /**
     * @return list<string>
     */
    public function getFuneralsArray(): array
    {
        if ($this->funerals->count() == 0) {
            return [];
        }
        $paragraphs = collect([
            '',
        ])->merge(
            $this->funerals->map(function ($funeral) {
                $funeralService = $funeral->service;
                if (! $funeralService) {
                    return '';
                }
                $funeralLocation = $funeralService->location;
                if ($funeralLocation) {
                    $location = (self::stringify($funeralLocation->at_text) ?: 'auf dem Friedhof').' '
                    .(self::stringify($funeralLocation->general_location_name) ?: 'in '.self::stringify($funeralLocation->city?->name));
                } else {
                    $location = 'in '.self::stringify($funeralService->city?->name);
                }
                /** @var string $buriedName Formatted name of the deceased. */
                $buriedName = NameService::fromName(self::stringify($funeral->buried_name))->format(NameService::FIRST_LAST);
                $buriedAddress = self::stringify($funeral->buried_address);
                $funeralDate = $funeralService->date;

                return 'Am '.($funeral->dod?->isoFormat('D. MMMM') ?? '').' ist '
                    .$buriedName
                    .($buriedAddress ? ', '.$buriedAddress.',' : '')
                    .($funeral->age ? ' im Alter von '.$funeral->age.' Jahren' : '')
                    .' verstorben. Die '.self::stringify($funeral->type)
                    .($funeralDate < $this->service->date ? ' fand bereits am ' : ' findet am ')
                    .($funeralDate?->isoFormat('dddd, DD. MMMM') ?? '').', um '.$funeralService->timeText()
                    .' '.$location.' statt.';
            })
        );
        $paragraphs[] = '';
        $paragraphs[] = static::DEFAULT_FUNERAL_VOTE;

        /** @var list<string> $result Funeral paragraphs including the closing vote. */
        $result = $paragraphs->toArray();

        return $result;
    }

    /**
     * @return list<string>
     */
    public function getWeddingsArray(): array
    {
        if ($this->weddings->count() == 0) {
            return [];
        }
        $paragraphs = [''];
        foreach ($this->weddings as $day => $weddings) {
            $paragraphs[] = implode(' ', [
                (string) $day,
                'werden',
                $weddings->map(function ($wedding) {
                    /** @var string $spouse1 Formatted name of the first spouse. */
                    $spouse1 = NameService::fromName(self::stringify($wedding->spouse1_name))->format(NameService::FIRST_LAST);
                    /** @var string $spouse2 Formatted name of the second spouse. */
                    $spouse2 = NameService::fromName(self::stringify($wedding->spouse2_name))->format(NameService::FIRST_LAST);

                    return $spouse1.' und '.$spouse2;
                })->join('; ', 'und'),
                'kirchlich getraut.',
            ]);
        }
        $paragraphs[] = '';
        $paragraphs[] = static::DEFAULT_WEDDING_VOTE;

        /** @var list<string> $result Wedding paragraphs including the closing vote. */
        $result = $paragraphs;

        return $result;
    }

    /**
     * @param  string  $separator  Paragraph separator.
     */
    public function getRitesText($separator = ''): string
    {
        return implode($separator, $this->getRitesArray());
    }

    /**
     * @return list<string>
     */
    public function getCustomAnnouncementsArray(): array
    {
        return [];
    }

    /**
     * @param  string  $separator  Paragraph separator.
     */
    public function getCustomAnnouncementsText($separator = ''): string
    {
        return implode($separator, $this->getCustomAnnouncementsArray());
    }

    /**
     * @return list<string>
     */
    public function getFinalSongArray(): array
    {
        /** @var Collection<int, Item> $liturgy Liturgy items after the announcements. */
        $liturgy = collect();
        foreach ($this->service->liturgyBlocks->pluck('items') as $items) {
            if (! is_iterable($items)) {
                continue;
            }
            foreach ($items as $item) {
                if ($item instanceof Item) {
                    $liturgy->push($item);
                }
            }
        }
        $afterAnnouncements = false;
        $finalSong = $liturgy->filter(function ($item) use (&$afterAnnouncements) {
            if (in_array($item->title, ['Abkündigungen', 'Ankündigungen', 'Bekanntgaben', 'Bekanntmachungen'])) {
                $afterAnnouncements = true;
            }

            return $afterAnnouncements && ($item->data_type == 'song');
        })->first();

        if (! $finalSong) {
            return [];
        }

        $verses = self::stringify($finalSong->data['verses'] ?? '');
        $helper = $finalSong->getHelper();
        $titleText = ($helper instanceof SongItemHelper || $helper instanceof PsalmItemHelper) ? $helper->getTitleText() : '';

        return ['Wir singen:', $titleText.($verses ? ', '.$verses : '')];
    }

    /**
     * @param  string  $separator  Paragraph separator.
     */
    public function getFinalSongText($separator = ''): string
    {
        return implode($separator, $this->getFinalSongArray());
    }

    /**
     * @return list<string>
     */
    public function getEventsIntroArray()
    {
        if ($this->events->count() == 0) {
            return [];
        }

        return [
            'Ganz herzlich laden wir Sie '
            .self::stringify(Str::choice($this->events->count(), 'zu folgender Veranstaltung', 'zu den folgenden Veranstaltungen'))
            .' ein:',
        ];
    }

    /**
     * @param  string  $separator  Paragraph separator.
     */
    public function getEventsIntroText($separator = ''): string
    {
        return implode($separator, $this->getEventsIntroArray());
    }

    /**
     * @return list<string>
     */
    public function getFeaturedEventsIntroArray()
    {
        if ($this->featuredEvents->count() == 0) {
            return [];
        }

        return [
            'Ganz besonders möchten wir Sie auf folgende '
            .self::stringify(Str::choice($this->events->count(), 'Veranstaltung', 'Veranstaltungen'))
            .' hinweisen:',
        ];
    }

    /**
     * @param  string  $separator  Paragraph separator.
     */
    public function getFeaturedEventsIntroText($separator = ''): string
    {
        return implode($separator, $this->getFeaturedEventsIntroArray());
    }

    /**
     * @return list<string>
     */
    public function getThanksArray(): array
    {
        $music = $this->service->organists;
        foreach (['Musik', 'Band', 'Klavier', 'Schlagzeug', 'Cajon', 'Bass'] as $instrument) {
            if ($people = $this->service->participantsByCategory($instrument)) {
                $music->merge($people);
            }
        }
        /** @var Collection<int, string> $musicians Names of the musicians. */
        $musicians = collect();
        foreach ($music as $musician) {
            $musicians->push(NameService::fromUser($musician)->format(NameService::FIRST_LAST));
        }

        return [
            'Herzlichen Dank an '.$musicians->join(
                ', ',
                ' und '
            ).' für die schöne musikalische Begleitung des Gottesdiensts.',
        ];
    }

    /**
     * @return string
     */
    protected function tab()
    {
        return $this->useTabs ? "\t" : ' ';
    }

    /**
     * @return list<string>
     */
    public function getEventsArray(): array
    {
        $paragraphs = [];
        foreach ($this->events as $day => $events) {
            $paragraphs[] = '';
            $paragraphs[] = (string) $day;
            foreach ($events as $event) {
                $eventService = $event->event;
                if (! $eventService) {
                    continue;
                }
                $pastors = $eventService->pastors;
                $paragraphs[] = ($eventService->is_allday ? '' : $eventService->timeText().$this->tab())
                    .Str::replace('&', '&amp;', self::stringify($eventService->titleText(false, false)))
                    .(count($pastors ?? []) ?
                        ' mit '.$pastors->map(function ($pastor) {
                            return NameService::fromUser($pastor)->format(NameService::TITLE_FIRST_LAST);
                        })->join(', ')
                        : '')
                    .' ('.self::stringify($eventService->locationTextWithCity).')'
                    .($eventService->description ? "\n".self::stringify($eventService->description) : '');
            }
        }

        array_shift($paragraphs);

        return $paragraphs;
    }

    /**
     * @param  string  $separator  Paragraph separator.
     */
    public function getEventsText($separator = ''): string
    {
        return implode($separator, $this->getEventsArray());
    }

    /**
     * @return list<string>
     */
    public function getFeaturedEventsArray(): array
    {
        /** @var list<string> $paragraphs Featured event paragraphs. */
        $paragraphs = [];
        foreach ($this->featuredEvents as $event) {
            $eventService = $event->event;
            $eventOccurenceService = $event->service;
            if (! $eventService || ! $eventOccurenceService) {
                continue;
            }
            $start = $event->start;
            $end = $event->end;
            $paragraphs[] = $start->setTimeZone('Europe/Berlin')->isoFormat('dddd, DD. MMMM')
                .($eventService->is_allday ? ($start->setTimeZone('Europe/Berlin')->format('Ymd') != $end->setTimeZone('Europe/Berlin')->format('Ymd') ? ' - '.$end->setTimeZone('Europe/Berlin')->isoFormat('dddd, DD. MMMM') : '') : ', '.$eventOccurenceService->timeText())
                .', '.self::stringify($eventOccurenceService->locationTextWithCity);
            $paragraphs[] = self::stringify($eventOccurenceService->titleText(false));
            $paragraphs[] = $event->getAdText('newsletter');
        }
        array_shift($paragraphs);

        return $paragraphs;
    }

    /**
     * @param  string  $separator  Paragraph separator.
     */
    public function getFeaturedEventsText($separator = ''): string
    {
        return implode($separator, $this->getFeaturedEventsArray());
    }

    /**
     * @return list<string>
     */
    public function getEventsExtroArray(): array
    {
        $sources = collect(['in den Aushängen']);
        if ($this->service->city?->homepage) {
            $sources->push('auf unserer Homepage');
        }
        if ($this->service->city?->communiapp_token) {
            $sources->push('in unserer App');
        }

        return [
            'Alle weiteren Veranstaltungen finden Sie '.$sources->join(', ', ' oder ').'.',
        ];
    }

    /**
     * @param  string  $separator  Paragraph separator.
     */
    public function getEventsExtroText($separator = ''): string
    {
        return implode($separator, $this->getEventsExtroArray());
    }

    /**
     * @param  string  $separator  Separator.\n     * @return string
     */
    public function getThanksText(string $separator = ''): string
    {
        return implode($separator, $this->getThanksArray());
    }

    /**
     * @return list<string>
     */
    public function getLastOfferingsArray(string $lastService = '', string $offerings = ''): array
    {
        $paragraphs = [];
        if ($lastService) {
            $lastService = Carbon::parse($lastService);
            if ($offerings == "0,00\u{A0}€") {
                $offerings = '';
            }
            $paragraphs[] = 'Das Opfer vom letzten '.$lastService->isoFormat('dddd').' ergab '.($offerings ?: '______________').'.';
        }

        return $paragraphs;
    }

    /**
     * @param  string  $separator  Paragraph separator.
     */
    public function getLastOfferingsText($separator = ''): string
    {
        return implode($separator, $this->getLastOfferingsArray());

    }

    /**
     * @return list<string>
     */
    public function getOfferingsArray(string $lastService = '', string $offerings = ''): array
    {
        $paragraphs = [];
        if (! empty($this->service->offeringGoal())) {
            $paragraphs[] = 'Das Opfer heute erbitten wir für: '.$this->service->offeringGoal();
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

    /**
     * @param  string  $lastService  Last service.\n     * @param  string  $offerings  Offerings.\n     * @param  string  $separator  Separator.\n     * @return string
     */
    public function getOfferingsText(string $lastService = '', string $offerings = '', string $separator = ''): string
    {
        return implode($separator, $this->getOfferingsArray($lastService, $offerings));
    }

    public function getService(): Service
    {
        return $this->service;
    }

    /** @return Collection<array-key, Collection<int, Occurence>> */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    /** @return Collection<int, Occurence> */
    public function getFeaturedEvents(): Collection
    {
        return $this->featuredEvents;
    }

    /** @return Collection<array-key, mixed> */
    public function getTexts(): Collection
    {
        return $this->texts;
    }

    /** @return Collection<array-key, Collection<int, Baptism>> */
    public function getBaptisms(): Collection
    {
        return $this->baptisms;
    }

    /** @return EloquentCollection<int, Funeral> */
    public function getFunerals(): Collection
    {
        return $this->funerals;
    }

    /** @return Collection<array-key, Collection<int, Wedding>> */
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

    /**
     * @param  bool  $useTabs  Use tabs.\n     * @return void
     */
    public function setUseTabs(bool $useTabs): void
    {
        $this->useTabs = $useTabs;
    }

    public function getEventListKeyFormat(): string
    {
        return $this->eventListKeyFormat;
    }

    /**
     * @param  string  $eventListKeyFormat  Key format.\n     * @return void
     */
    public function setEventListKeyFormat(string $eventListKeyFormat): void
    {
        $this->eventListKeyFormat = $eventListKeyFormat;
    }

    /**
     * @param  Collection<int, Occurence>  $events
     * @return Collection<int, Occurence>
     */
    public static function expandMultiDayEvents(Collection $events): Collection
    {
        /** @var Collection<int, Occurence> $extra Additional occurences for multi-day events. */
        $extra = collect();
        foreach ($events as $event) {
            $eventService = $event->event;
            if (! $eventService || ! $eventService->is_allday) {
                continue;
            }
            if ($event->start->setTimezone('Europe/Berlin')->format('Ymd') == $event->end->setTimezone('Europe/Berlin')->format('Ymd')) {
                continue;
            }
            $cursor = $event->start->copy()->addDay();
            while ($cursor < $event->end) {
                $newEvent = clone $event;
                $eventClone = clone $eventService;
                $eventClone->date = $cursor;
                $newEvent->setRelation('event', $eventClone);
                $newEvent->start = $cursor;
                $extra->push($newEvent);
                $cursor->addDay();
            }
        }

        return $events->concat($extra)->sortBy('start');
    }

    /**
     * Convert a mixed value to a string for announcement output.
     *
     * @param  mixed  $value  Value to convert.
     * @return string String representation.
     */
    private static function stringify(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        if (is_bool($value)) {
            return $value ? '1' : '';
        }
        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        return '';
    }
}
