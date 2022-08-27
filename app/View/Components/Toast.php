<?php

namespace App\View\Components;

use Illuminate\Support\Arr;
use Illuminate\View\Component;

/**
 * @author Dean Blackborough <dean@g3d-development.com>
 * @copyright Dean Blackborough (Costs to Expect) 2018-2022
 * https://github.com/costs-to-expect/yatzy/blob/main/LICENSE
 */
class Toast extends Component
{
    public array $messages;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->messages = [
            'toast_yatzy' => [
                [
                    'heading' => '50 points!',
                    'message' => 'Well done, this should help!',
                ],
                [
                    'heading' => 'Fifty points!',
                    'message' => 'Look at everyone, they can\'t believe it',
                ]
            ],
            'toast_yatzy_scratch' => [
                [
                    'heading' => 'Bye-bye 50 points!',
                    'message' => 'I\'m guessing the game isn\'t going too well.',
                ],
                [
                    'heading' => 'Oops!',
                    'message' => 'Did you click the wrong button?',
                ],
                [
                    'heading' => 'Tactics!',
                    'message' => 'Was this tactical, are you Psyching out your opponents.',
                ]
            ],
        ];
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view(
            'components.toast',
            [
                'toast_yatzy' => Arr::random($this->messages['toast_yatzy']),
                'toast_yatzy_scratch' => Arr::random($this->messages['toast_yatzy_scratch']),
            ]
        );
    }
}
