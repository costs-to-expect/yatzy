@props(['id', 'title', 'subtitle' => null])
{{-- A native <dialog>, a bottom sheet on a phone and a centred dialog from sm up. Open it with data-dialog-open="id",
     the browser traps focus, closes it on Escape and returns focus to the button that opened it. --}}
<dialog id="{{ $id }}" class="sheet" aria-labelledby="{{ $id }}-title" {{ $attributes }}>
    <div class="p-5 pb-[max(1.5rem,env(safe-area-inset-bottom))] sm:p-6">
        <div class="mx-auto mb-4 h-1 w-10 rounded-full bg-stone-200 sm:hidden"></div>
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <h2 id="{{ $id }}-title" class="text-xl font-extrabold tracking-tight">{{ $title }}</h2>
                @if ($subtitle)
                    <p class="mt-0.5 text-sm text-stone-600">{{ $subtitle }}</p>
                @endif
            </div>
            <button type="button" data-dialog-close class="-mr-3 -mt-2.5 shrink-0 rounded-full p-3 text-stone-500 hover:bg-stone-100 hover:text-stone-800 focus-visible:outline-2 focus-visible:outline-brand-600" aria-label="Close"><x-icon name="close" class="h-5 w-5" /></button>
        </div>
        <div class="mt-5">{{ $slot }}</div>
    </div>
</dialog>
