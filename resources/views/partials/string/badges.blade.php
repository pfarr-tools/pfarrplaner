@foreach($items as $item)
    <span class="badge bg-{{ $badge_type ?? 'primary' }}">{{ $item }}</span>
@endforeach
