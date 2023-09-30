<li id="{{ $id }}Tab" class="nav-item">
    <a class="nav-link @if(isset($active) && $active) active @endif" href="#{{ $id }}" role="tab" data-bs-toggle="tab">{{ $title }}@if(isset($count) && ($count > 0)) <span
            class="badge bg-{{ $badge_type ?? 'primary' }}">{{ $count }}</span> @endif</a>
</li>
