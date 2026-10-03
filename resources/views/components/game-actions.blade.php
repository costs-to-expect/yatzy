@props(['gameId'])
{{-- The things to do with a game in progress, the dialogs they open are in <x-game-dialogs> --}}
<div class="mt-6 flex flex-wrap items-center gap-x-1 gap-y-3 border-t border-stone-100 pt-4 text-sm">
    <button type="button" data-dialog-open="share-dialog" class="btn-link"><x-icon name="share" class="h-5 w-5" />Share links</button>
    <a href="{{ route('game.add-players.view', ['game_id' => $gameId]) }}" class="btn-link"><x-icon name="plus" class="h-5 w-5" />Add player</a>

    <div class="flex w-full items-center gap-2 sm:ml-auto sm:w-auto">
        <button type="button" data-dialog-open="finish-dialog" class="btn btn-secondary min-h-11 flex-1 sm:flex-none">Finish game</button>

        <details data-menu class="relative">
            <summary class="flex h-11 w-11 cursor-pointer list-none items-center justify-center rounded-xl text-stone-500 hover:bg-stone-100 hover:text-stone-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 [&::-webkit-details-marker]:hidden" aria-label="More game options"><x-icon name="more" class="h-6 w-6" /></summary>
            <div class="absolute bottom-full right-0 z-10 mb-2 w-56 rounded-2xl bg-white p-1.5 shadow-lift ring-1 ring-stone-200">
                <button type="button" data-dialog-open="remove-dialog" class="flex min-h-11 w-full items-center gap-2.5 rounded-xl px-3 text-left font-semibold text-stone-800 hover:bg-stone-100"><x-icon name="user-minus" class="h-5 w-5" />Remove a player&hellip;</button>
                <button type="button" data-dialog-open="delete-dialog" class="flex min-h-11 w-full items-center gap-2.5 rounded-xl px-3 text-left font-semibold text-red-700 hover:bg-red-50"><x-icon name="trash" class="h-5 w-5" />Delete game&hellip;</button>
            </div>
        </details>
    </div>
</div>
