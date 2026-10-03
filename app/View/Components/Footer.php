<?php

namespace App\View\Components;

use Illuminate\Support\Facades\Config;
use Illuminate\View\Component;

class Footer extends Component
{
    public string $version;

    public function __construct()
    {
        $this->version = (string) Config::get('app.version')['app'];
    }

    public function render()
    {
        return view('components.footer');
    }
}
