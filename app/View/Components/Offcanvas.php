<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Offcanvas extends Component
{
    public string $active;

    public function __construct(string $active = 'home')
    {
        $this->active = $active;
    }

    public function render()
    {
        return view(
            'components.offcanvas',
            ['active' => $this->active]
        );
    }
}
