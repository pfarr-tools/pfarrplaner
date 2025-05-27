BEGIN:VCALENDAR
VERSION:2.0
PRODID:{{ $calendarLink->getLink() }}
METHOD:PUBLISH
@foreach($data as $occurence)
BEGIN:VEVENT
UID:{{ $occurence->id }}{{ '@' }}{{ parse_url(env('APP_URL'), PHP_URL_HOST) }}
LOCATION:{{ $occurence->event->locationText() }}
SUMMARY:{{ $occurence->event->titleText(false).($occurence->event->event_class == 'service' ? ' ('.$occurence->event->participantsText('P').')' : '') }}
DESCRIPTION: {{ strtr(wordwrap ($occurence->event->descriptionText(), 62, "\\n  "), ["\r" =>'', "\n"=>'\\n']) }}

CLASS:PUBLIC
DTSTART:{{ $occurence->start->setTimezone('UTC')->format('Ymd\THis\Z') }}
DTEND:{{ $occurence->end->setTimezone('UTC')->format('Ymd\THis\Z') }}
DTSTAMP:{{ $occurence->updated_at->setTimezone('UTC')->format('Ymd\THis\Z') }}
END:VEVENT
@endforeach
END:VCALENDAR
