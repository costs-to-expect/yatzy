<?php

namespace App\View\Components;

use Illuminate\Support\Facades\Config;
use Illuminate\View\Component;

class Footer extends Component
{
    public function __construct()
    {
        //
    }

    public function render()
    {
        $config = Config::get('app.config');

        return view(
            'components.footer',
            [
                'version' => $config['version'],
                'release_date' => $config['release_date'],
            ]
        );
    }
}
