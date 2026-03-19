@props(['href' => '#', 'active' => false])

<a href="{{ $href }}"
   {{ $attributes->merge(['class' => 'btn']) }}
   style="{{ $active ? 'border-color: rgba(61,220,151,.45); background: rgba(61,220,151,.10);' : '' }}">
    {{ $slot }}
</a>

