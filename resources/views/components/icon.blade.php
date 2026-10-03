@props(['name'])
@php
    $solid = isset(\App\View\Icons::SOLID[$name]);
@endphp
<svg {{ $attributes->merge(['class' => 'h-5 w-5']) }} viewBox="0 0 24 24" @if ($solid) fill="currentColor" @else fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" @endif aria-hidden="true"><use href="#i-{{ $name }}"/></svg>
