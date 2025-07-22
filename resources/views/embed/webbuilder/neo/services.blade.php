@php
    $events = $occurences->groupBy(function (App\Models\Calendar\Occurence $item, int $key) {
                return $item->start->format('Ymd');
            });
@endphp
<style id="buttonstyles">
    .events-list-table tr.hoverable:hover {
        background-color: #B76FA5;
        cursor: pointer;
    }
    .events-list-table td {
        vertical-align: top;
    }
    .small-button {
        font-size: .8em;
        border: solid 1px darkgray;
        padding: 4px 7px;
        border-radius: 0;
        color: black;
        cursor: pointer;
    }
    .youtube-button {
        font-size: 1.2em;
        border: solid 1px darkgray;
        padding: 4px 7px;
        border-radius: 0;
        cursor: pointer;
    }
    .youtube-button .fab {
        color: red;
    }
    .youtube-button:hover, .small-button:hover {
        background-color: white;
        box-shadow: 0 5px 11px 0 rgba(0,0,0,0.18),0 4px 15px 0 rgba(0,0,0,0.15);
    }
</style>
<div id="{{ $randomId }}">
    <div id="{{ $randomId }}_table" class="events-list-table">
        @if(count($events))
            <table class="ce-table striped">
                <thead>
                <th>Datum</th>
                <th>Uhrzeit</th>
                <th>Veranstaltung</th>
                <th>Ort</th>
                </thead>
                <tbody>
                @foreach ($events as $theseEvents)@if(trim($theseEvents->first()->liturgicalInfo['title'] ?? '') && (substr($theseEvents->first()->liturgicalInfo['date'] ?? '',0,10) == $theseEvents->first()->event->date->format('Y-m-d')))
                    <tr style="background-color: #ccc !important;">
                        <td valign="top"
                            style="vertical-align:top;">{!! $theseEvents->first()->start->isoFormat('dd.,\&\n\b\s\p;DD.MM.') !!}</td>
                        <td></td>
                        <td valign="top" colspan="2"
                            style="vertical-align:top; font-weight: bold;">{{ str_replace('So.', 'Sonntag', $theseEvents->first()->liturgicalInfo['title'] ?? '') }}</td>
                    </tr>
                @endif @foreach($theseEvents as $occurence)
                    <tr>
                        <td valign="top"
                            style="vertical-align:top;">{!! $occurence->start->isoFormat('dd.,\&\n\b\s\p;DD.MM.') !!}</td>
                        <td valign="top">{{ $occurence->event->timeText(true, '.') }}</td>
                        <td valign="top">
                            <b>{{ $occurence->event->titleText(false, false) }}</b> @if($occurence->event->participantsText('P') != '')
                                ({{ $occurence->event->participantsText('P') }})@endif
                            @if($occurence->event->descriptionText())<br/>{{ $occurence->event->descriptionText() }}@endif
                            @if($occurence->event->controlled_access) @component('components.service.controlledAccess', ['service' => $occurence->event]) @endcomponent @endif
                            @if ($occurence->event->offering_goal)<br/>Opfer: {{ $occurence->event->offering_goal }}@endif
                            @if ($occurence->event->event_class == 'service')
                                <div>
                                    @if ($occurence->event->songsheet) <span class="small-button" href="{{ $occurence->event->songsheetUrl }}" title="Klicken Sie hier, um das Liedblatt herunterzuladen"><span class="fa fa-file-pdf"></span> Liedblatt</span> @endif
                                    @if ($occurence->event->offerings_url) <span class="small-button" href="{{ $occurence->event->offerings_url }}" title="Klicken Sie hier, um online zu spenden"><span class="fa fa-coins"></span> Opfer</span> @endif
                                    @if ($occurence->event->cc_streaming_url) <span class="small-button" href="{{ $occurence->event->cc_streaming_url }}"title="Klicken Sie hier, um den Kindergottesdienst auf YouTube anzuschauen"><img src="{{ asset('img/cc.png') }}" height="12px"> Kinderkirche</span> @endif
                                    @if ($occurence->event->external_url) <span class="small-button" href="{{ $occurence->event->external_url }}"  title="Klicken Sie hier, um zur Predigtseite zu gelangen"><span class="fa fa-globe"></span> Externe Seite zur Predigt</span> @endif
                                </div>
                            @endif
                        </td>
                        <td valign="top" style="vertical-align:top;">
                            {{ count($options['cities']) > 1 ? $occurence->event->locationText() : $occurence->event->locationTextWithCity }}
                            @if($occurence->event->youtube_url)
                                <div><a class="youtube-button" target="_blank" href="{{ $occurence->event->youtube_url }}"><span class="fab fa-youtube"></span> Auf YouTube ansehen</a></div>
                            @endif
                            @if ($occurence->event->location && ($occurence->event->location->instructions != ''))<div>
                                <small>{!! nl2br($occurence->event->location->instructions) !!}</small></div>
                            @endif
                        </td>
                    </tr>
                    @if ($occurence->event->cc)
                        <tr>
                            <td valign="top"
                                style="vertical-align:top;">{!! $occurence->event->date->isoFormat('dd.,\&\n\b\s\p;DD.MM.') !!}</td>
                            <td valign="top">{{ str_replace(':', '.', ($occurence->event->cc_alt_text ?? $occurence->event->timeText(true, '.'))) }}</td>
                            <td valign="top">
                                <b>Kinderkirche</b>
                            </td>
                            <td valign="top" style="vertical-align:top;">
                                {{ $occurence->event->cc_location ?? $occurence->event->locationText() }}
                            </td>
                        </tr>
                    @endif
                @endforeach
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
