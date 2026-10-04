<svg {{ $attributes->merge(['class' => 'h-14 w-14']) }} viewBox="0 0 76 76" fill="none" aria-hidden="true">
    <circle cx="38" cy="38" r="{{ \App\View\Components\Ring::RADIUS }}" class="stroke-stone-200" stroke-width="5"/>
    <circle cx="38" cy="38" r="{{ \App\View\Components\Ring::RADIUS }}" class="stroke-brand-600" stroke-width="5" stroke-linecap="round" stroke-dasharray="{{ number_format($dash, 1, '.', '') }} {{ number_format($circumference, 1, '.', '') }}" transform="rotate(-90 38 38)"/>
</svg>
