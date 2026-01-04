<!DOCTYPE html>
<head>
    <title>{{ $service->titleText(false) }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="x-ua-compatible" content="ie=edge">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <style>
        .fake-button {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
            padding: var(--bs-accordion-btn-padding-y) var(--bs-accordion-btn-padding-x);
            font-size: 1rem;
            color: var(--bs-accordion-btn-color);
            text-align: left;
            background-color: var(--bs-accordion-btn-bg);
            border: 0;
            border-radius: 0;
            overflow-anchor: none;
            font-weight: 400;
        }

        pre {
            font-family: var(--bs-body-font-family);
            font-size: var(--bs-body-font-size);
        }

        .text-muted a {
            text-decoration: none;
            font-weight: bold;
            color: inherit;
        }
    </style>
</head>
<body>
    <?php $first = true; ?>
    <div class="p-3">
        <h1>{{ $service->titleText(false) }}</h1>
        {{ $service->timeText() }}, {{ $service->locationTextWithCity }}
    </div>
    <hr />

    @if($service->youtube_url || true)
        <div class="alert alert-warning p-3 m-2">
            Wir weisen darauf hin, dass dieser Gottesdienst live ins Internet übertragen wird. Die ersten Reihen werden dabei
            möglicherweise von der Kamera mit erfasst. Bitte wählen Sie Ihren Sitzplatz entsprechend, wenn Sie bei
            der Übertragung nicht sichtbar sein wollen.
        </div>
    @endif


    <div class="accordion" id="liturgyAccordion">
        @foreach ($service->liturgyBlocks as $blockIndex => $block)
            <div class="p-3"><h2>{{ $block->title }}</h2></div>
            @foreach($block->items as $itemIndex => $item)
                @if (in_array($item->data_type, ['song', 'psalm']) || ($item->data['showInHandouts'] ?? false) || ($item->data['handoutText'] ?? false) || ($item->title == 'Ehr sei dem Vater'))
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading_{{ $blockIndex }}_{{ $itemIndex }}">
                        <button class="accordion-button {{ $first ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_{{ $blockIndex }}_{{ $itemIndex }}" aria-expanded="{{ $blockIndex==0 && $itemIndex==0 ? 'true' : 'false' }})" aria-controls="collapse_{{ $blockIndex }}_{{ $itemIndex }}">
                            {{ $item->title }}
                        </button>
                    </h2>
                    <div id="collapse_{{ $blockIndex }}_{{ $itemIndex }}" class="accordion-collapse collapse {{ $first ? 'show' : '' }}" aria-labelledby="heading_{{ $blockIndex }}_{{ $itemIndex }}" data-bs-parent="#liturgyAccordion">
                        <?php $first = false; ?>
                        <div class="accordion-body">
                        <?php $helper = $item->getHelper() ?>
                        @switch($item->data_type)
                            @case('psalm')
                                <h3>{{ $helper->getTitleText() }}</h3>
                                <div>
                                    <pre>{{ $item->data['psalm']['text'] }}</pre>
                                </div>
                            @break
                            @case('song')
                                <h3>{{ $helper->getTitleText() }}</h3>
                                @if($item->data['song']['song']['copyrights'] ?? false)
                                    <div style="font-size: .7em;">{{ $item->data['song']['song']['copyrights'] }}</div>
                                @endif
                                    @foreach($helper->getActiveVerses() as $verse)
                                        @if($verse['refrain_before'])<div class="mt-2 fst-italic">{{ $item->data['song']['song']['refrain'] }}</div>@endif
                                        <div class="mt-2">{{ $verse['number'].'. '.$verse['text'] }}</div>
                                        @if($verse['refrain_after'])<div class="mt-2 fst-italic">{{ $item->data['song']['song']['refrain'] }}</div>@endif
                                    @endforeach
                            @break
                            @case('reading')
                                <h3>{{ $helper->getReference()['correctedReference'] }}</h3>
                                @if ($helper->getReference()['versionCopyrights'])
                                    <div style="font-size: .7em;">{{ $helper->getReference()['versionCopyrights'] }}</div>
                                @endif
                                <div class="mt-2">
                                    {!! $helper->getHtml() !!}
                                </div>
                            @break
                            @case('freetext')
                                @if($item->title == 'Ehr sei dem Vater')
                                    <div class="fst-italic">
                                        Ehr sei dem Vater und dem Sohn und dem Heiligen Geist,
                                        wie es war im Anfang, jetzt und immerdar und von Ewigkeit zu Ewigkeit.
                                        Amen. Amen.
                                    </div>
                                @else
                                    <div>{!! $item->data['handoutText'] ?: '' !!}</div>
                                @endif
                            @break
                        @endswitch
                        </div>
                    </div>
                </div>
            @else
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading_{{ $blockIndex }}_{{ $itemIndex }}">
                        <div class="fake-button">{{ $item->title }}</div>
                    </h2>
                </div>
            @endif
            @endforeach
        @endforeach
    </div>

    <div class="p-3 mt-3">
        <h2>Mitwirkende</h2>
        @if(count($service->pastors))
            <div class="fw-bold">Liturgie</div>
            @foreach($service->pastors as $participant)
                <div>{{ \App\Services\NameService::fromUser($participant)->format(\App\Services\NameService::TITLE_FIRST_LAST) }}</div>
            @endforeach
        @endif
        @if(count($service->organists))
            <div class="fw-bold">Orgel</div>
            @foreach($service->organists as $participant)
                <div>{{ \App\Services\NameService::fromUser($participant)->format(\App\Services\NameService::TITLE_FIRST_LAST) }}</div>
            @endforeach
        @endif
        @if(count($service->sacristans))
            <div class="fw-bold">Mesnerdienst</div>
            @foreach($service->sacristans as $participant)
                <div>{{ \App\Services\NameService::fromUser($participant)->format(\App\Services\NameService::TITLE_FIRST_LAST) }}</div>
            @endforeach
        @endif
        @foreach($service->ministriesByCategory as $category => $participants)
            <div class="fw-bold">{{ $category }}</div>
            @foreach($participants as $participant)
                <div>{{ \App\Services\NameService::fromUser($participant)->format(\App\Services\NameService::TITLE_FIRST_LAST) }}</div>
            @endforeach
        @endforeach
    </div>
    <hr />
    <div class="p-3">
        @if ($service->offering_goal)
            Anlässlich dieses Gottesdiensts bitten wir um ein Opfer für: {{ $service->offering_goal }}.
        @else
            Anlässlich dieses Gottesdiensts bitten wir um ein Opfer zugunsten der vielfältigen Aufgaben in unserer Kirchengemeinde.
        @endif
        Herzlichen Dank für alles, was Sie geben.
    </div>
    <hr />
    <div class="p-3 text-muted" style="font-size: .7em">
        &copy; {{ $service->date->year }} <a href="{{ $service->city->homepage }}" target="_blank" title="Homepage der Kirchengemeinde">
            {{ $service->city->official_name ?: $service->city->name ?: '' }}
        </a>

    </div>

</body>



