<x-layouts.base :title="config('app.game.name').' Game Scorer: '.$player_name" body-class="pb-0">
    <x-score-sheet :config="$config" />
</x-layouts.base>
