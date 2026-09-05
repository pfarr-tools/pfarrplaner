<section class="ekd-element element-text list-type-color-light-0 element-bgcolor-0 display-on-top-0">
<div class="container ekd-text">
<ul>
    @foreach ($occurences as $event)
        <li>{{ $event->start->format('d.m.Y') }}
            @if($event->event->timeText() != ($options['suppressTime'] ?? '')), {{ $event->event->timeText() }}@endif
            @if($event->event->locationTextWithCity != ($options['suppressLocation'] ?? '')), {{ $event->event->locationTextWithCity }}@endif
            @if($event->event->titleText(false) != ($options['suppressTitle'] ?? '')):
        <span style="font-weight: bold;">{{ $event->event->titleText(false) }}</span>
            @endif
        </li>
    @endforeach
</div>
</section>
