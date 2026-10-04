<x-layouts.base :title="'Hey '.$player_name.', play '.config('app.game.name').' with us!'" :noindex="true">
    <x-score-sheet :config="$config" :public="true" />
</x-layouts.base>
