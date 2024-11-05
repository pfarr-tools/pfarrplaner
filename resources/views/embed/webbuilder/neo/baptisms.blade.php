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
        <th colspan="2">Taufmöglichkeit</th>
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
            <td style="padding: 0; font-size: 30pt; font-weight: bold;">
                @if(count($occurence->event->baptisms) == 0) <span style="color: limegreen;">&bull;</span>
                @elseif(count($occurence->event->baptisms) < $options['maxBaptisms'] ?? 0) <span style="color: orange;">&bull;</span>
                @else <span style="color: red;">&bull;</span> @endif
            </td>
            <td>
                @if(count($occurence->event->baptisms) == 0)Taufanmeldung möglich
                @elseif(count($occurence->event->baptisms) < $options['maxBaptisms'] ?? 0)Taufanmeldung möglich <br /><small>(bereits {{ count ($occurence->event->baptisms) }} {{ count ($occurence->event->baptisms) == 1 ? 'Taufe' : 'Taufen' }})</small>
                @else Taufanmeldung nicht mehr möglich @endif
            </td>
@endif
        </tr>
    @endforeach
    </tbody>
</table>
@endif
