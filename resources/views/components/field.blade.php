@props(['name', 'label', 'type' => 'text', 'help' => null, 'bag' => null, 'value' => null, 'autocomplete' => null])
@php
    // The API's validation errors arrive as ['field' => ['errors' => ['message', ...]]]
    // (named bag, not errors: Laravel shares its own $errors with every view and that would win over the prop)
    $messages = $bag[$name]['errors'] ?? [];
    $described = collect([$help ? $name.'-help' : null, $messages !== [] ? $name.'-error' : null])->filter()->implode(' ');
@endphp
<div {{ $attributes->only('class') }}>
    <label for="{{ $name }}" class="form-label">{{ $label }}</label>
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        @class(['form-control', 'form-control-error' => $messages !== []])
        @if ($value !== null) value="{{ $value }}" @endif
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if ($messages !== []) aria-invalid="true" @endif
        @if ($described !== '') aria-describedby="{{ $described }}" @endif
        {{ $attributes->except('class') }}
    >
    @if ($help)
        <p id="{{ $name }}-help" class="form-help">{{ $help }}</p>
    @endif
    @if ($messages !== [])
        <p id="{{ $name }}-error" class="form-error"><x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" /><span>{{ implode(' ', $messages) }}</span></p>
    @endif
</div>
