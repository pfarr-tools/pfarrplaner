@extends('reports.newsletter.layouts.container')

@section('content')
    @if($data['includeWeeklyVerse']) @component('reports.newsletter.layouts.image', ['src' => $start->format('Y-m-d')])@endcomponent @endif
    @component('reports.newsletter.layouts.h1')Unsere nächsten Veranstaltungen @endcomponent
    <hr/>
    <table border="0" cellpadding="2" cellspacing="0">
        @foreach($events as $date => $theseEvents)
            @php $firstLine = true; @endphp
            @foreach($theseEvents as $event)
                <tr>
                    <td valign="top" style="text-align: left;">@if($firstLine){!! str_replace(' ', '&nbsp;', $event->start->isoFormat('dd., DD. MMM')) !!}@php $firstLine = false;@endphp@endif</td>
                    <td valign="top" style="text-align: right;">{!!  str_replace(' ', '&nbsp;', $event->event->timeText() )!!}</td>
                    <td valign="top" style="text-align: left; font-weight: bold;">{{ $event->event->titleText(false) }}</td>
                    <td valign="top" style="text-align: left;">{{ $event->event->locationTextWithCity }}</td>
                </tr>
            @endforeach
            <tr></tr>
        @endforeach
    </table>
@endsection

