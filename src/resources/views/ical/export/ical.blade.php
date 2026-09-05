BEGIN:VCALENDAR
VERSION:2.0
PRODID:{{ $calendarLink->wrap($calendarLink->getLink()) }}
METHOD:PUBLISH
@foreach($data as $service)@if(is_object($service))BEGIN:VEVENT
UID:{{ $service->id }}{{ '@' }}{{ parse_url(env('APP_URL'), PHP_URL_HOST) }}
LOCATION:{{ $service->locationText() }}
SUMMARY:{{ $calendarLink->wrap($service->titleText().' '.config('labels.code_pastor').': '.$service->participantsText('P').' '.config('labels.code_organist').': '.$service->participantsText('O').' '.config('labels.code_sacristan').': '.$service->participantsText('M').($service->description ? ' ('.$service->description.')' : '')) }}
DESCRIPTION: {{ $calendarLink->wrap($service->descriptionText()) }}
CLASS:PUBLIC
DTSTART:{{ \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $service->date->format('Y-m-d').' '.$service->timeText(false).':00', 'Europe/Berlin')->setTimezone('UTC')->format('Ymd\THis\Z') }}
DTEND:{{ \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $service->date->format('Y-m-d').' '.$service->timeText(false).':00', 'Europe/Berlin')->addHour(1)->setTimezone('UTC')->format('Ymd\THis\Z') }}
DTSTAMP:{{ $service->updated_at->setTimezone('UTC')->format('Ymd\THis\Z') }}
END:VEVENT
@endif
@endforeach
END:VCALENDAR
