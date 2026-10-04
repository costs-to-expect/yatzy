<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\View\Component;

/**
 * A progress ring that wraps an avatar, the fraction is 0 to 1 (the turns a player has played).
 */
class Ring extends Component
{
    public const RADIUS = 33;

    public float $circumference;

    public float $dash;

    public function __construct(float $fraction = 0.0)
    {
        $this->circumference = 2 * M_PI * self::RADIUS;
        $this->dash = $this->circumference * min(1.0, max(0.0, $fraction));
    }

    public function render()
    {
        return view('components.ring');
    }
}
