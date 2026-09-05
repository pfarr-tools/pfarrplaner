@if(count($occurences))
<table class="ce-table">
    <thead>
    <th>Datum</th>
    <th>Uhrzeit</th>
    @if (!isset($locationIds) || count($locationIds) >1)
        <th>Kirche</th>
    @endif
    <th>Pfarrer</th>
    @if ($options['maxBaptisms'] ?? 0 >0 )
        <th>Taufmöglichkeit</th>
    @endif
    </thead>
    <tbody>
    <?php $lastDate = ''; ?>
    @foreach($occurences as $occurence)
        <tr>
            @if ($lastDate != $occurence->event->date->format('d.m.') )
                <td>{{ $lastDate = $occurence->event->date->format('d.m.') }}</td>
            @else
                <td></td>
            @endif
            <td>{{ $occurence->event->timeText() }}</td>
            @if (!isset($locationIds) || count($locationIds) >1)
                <td>{{ $occurence->event->locationText() }}</td>
            @endif
            <td>{{ $occurence->event->participantsText('P') }}</td>
@if ($options['maxBaptisms'] ?? 0 >0 )
            <td style="padding: 0; vertical-align: middle;">
                @if(count($occurence->event->baptisms) == 0) <div style="width: 10px; height: 10px; border-radius: 50%; background-color: limegreen; display: inline-block; margin: 0 5px;"></div> Taufanmeldung möglich
                @elseif(count($occurence->event->baptisms) < $options['maxBaptisms'] ?? 0) <div style="width: 10px; height: 10px; border-radius: 50%; background-color: orange; display: inline-block; margin: 0 5px;"></div> Taufanmeldung möglich<div style="padding: 0 0 0 30px;margin: 0;font-size: .8em;">(bereits {{ count ($occurence->event->baptisms) }} {{ count ($occurence->event->baptisms) == 1 ? 'Taufe' : 'Taufen' }})</div>
                @else <div style="width: 10px; height: 10px; border-radius: 50%; background-color: red; display: inline-block; margin: 0 5px;"></div> Taufanmeldung nicht mehr möglich @endif
            </td>
@endif
        </tr>
    @endforeach
    </tbody>
</table>
@endif
