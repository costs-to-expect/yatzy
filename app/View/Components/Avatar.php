<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\View\Component;

/**
 * A player's initial on a soft colour. The colour comes from the player's place in the players list, so they
 * keep it in every game and nothing has to be stored. The classes are written out in full so Tailwind finds them,
 * public/js/ui.js has the same list for the avatars it draws.
 */
class Avatar extends Component
{
    public const TONES = [
        'bg-rose-100 text-rose-800',
        'bg-sky-100 text-sky-800',
        'bg-lime-100 text-lime-800',
        'bg-violet-100 text-violet-800',
        'bg-orange-100 text-orange-800',
        'bg-slate-200 text-slate-700',
    ];

    public string $initial;

    public string $tone;

    public function __construct(string $name, int $index = 0)
    {
        $this->initial = mb_strtoupper(mb_substr(trim($name), 0, 1));
        $this->tone = self::TONES[abs($index) % count(self::TONES)];
    }

    public function render()
    {
        return view('components.avatar');
    }
}
