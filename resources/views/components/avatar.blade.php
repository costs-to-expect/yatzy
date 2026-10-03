{{-- The size is whatever the caller passes, the default only applies when none is given, two sizes in one class list fight --}}
<span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center justify-center rounded-full font-bold '.$tone.($attributes->has('class') ? '' : ' h-9 w-9 text-sm')]) }} aria-hidden="true">{{ $initial }}</span>
