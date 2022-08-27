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
            'toast_yahtzee' => [
                [
                    'heading' => '50 points in the bag!',
                    'message' => 'Are you going to try for a Yahtzee bonus?',
                ],
                [
                    'heading' => '50 points!',
                    'message' => 'Well done, this should help, now go score a Yahtzee bonus!',
                ],
                [
                    'heading' => 'Fifty points!',
                    'message' => 'Look at everyone, they can\'t believe it',
                ]
            ],
            'toast_yahtzee_scratch' => [
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
            'toast_yahtzee_bonus_one' => [
                [
                    'heading' => 'Yahtzee X 2',
                    'message' => 'Have you considered giving the other players a chance?',
                ],
                [
                    'heading' => 'Yahtzee X 2',
                    'message' => 'Yes, the other players suck!',
                ]
            ],
            'toast_yahtzee_bonus_two' => [
                [
                    'heading' => 'Yahtzee X 3',
                    'message' => 'Do the other players hate you?',
                ],
                [
                    'heading' => 'Bonus X 2',
                    'message' => 'Was one bonus not enough?',
                ],
                [
                    'heading' => 'Bonus X 2',
                    'message' => 'OMG!, are you going for three?',
                ]
            ],
            'toast_yahtzee_bonus_three' => [
                [
                    'heading' => 'Yahtzee X 4',
                    'message' => 'GAME OVER!',
                ],
                [
                    'heading' => 'Yahtzee X 4',
                    'message' => 'Score another bonus, I dare you!',
                ],
                [
                    'heading' => 'Bonus X 3',
                    'message' => 'Please don\'t score another bonus, we will need another checkbox!',
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
                'toast_yahtzee' => Arr::random($this->messages['toast_yahtzee']),
                'toast_yahtzee_scratch' => Arr::random($this->messages['toast_yahtzee_scratch']),
                'toast_yahtzee_bonus_one' => Arr::random($this->messages['toast_yahtzee_bonus_one']),
                'toast_yahtzee_bonus_two' => Arr::random($this->messages['toast_yahtzee_bonus_two']),
                'toast_yahtzee_bonus_three' => Arr::random($this->messages['toast_yahtzee_bonus_three']),
            ]
        );
    }
}
