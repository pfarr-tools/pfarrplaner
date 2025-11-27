@if(isset($liturgy['Bezeichnung']))
<div class="day-description">{{ $liturgy['Bezeichnung'] }}</div>
@endif
@if (isset($liturgy['perikope']))
    <div class="liturgy">
        <div class="liturgy-sermon">
            <div class="liturgy-color"
                 style="background-color: {{ $liturgy['litColor'].';'.($liturgy['litColor'] == 'white' ? 'border-color: darkgray;' : '') }}"
                 title="{{ $liturgy['feastCircleName'] }}"></div>
            {{ $liturgy['litTextsPerikope'.$liturgy['perikope']] }}</div>
    </div>
@endif
