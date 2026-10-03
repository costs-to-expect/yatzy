@props(['type' => 'info', 'title' => null])
@php
    $icon = match ($type) {
        'error' => 'alert',
        'warning' => 'alert',
        'success' => 'check-circle',
        default => 'info',
    };
@endphp
<div {{ $attributes->merge(['class' => 'alert alert-'.$type, 'role' => in_array($type, ['error', 'warning'], true) ? 'alert' : 'status']) }}>
    <x-icon :name="$icon" class="mt-0.5 h-5 w-5 shrink-0" />
    <div class="min-w-0 flex-1">
        @if ($title)
            <p class="font-extrabold">{{ $title }}</p>
        @endif
        <div @class(['space-y-1.5', 'mt-0.5' => $title])>{{ $slot }}</div>
    </div>
</div>
