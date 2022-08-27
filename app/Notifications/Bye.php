<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * @author Dean Blackborough <dean@g3d-development.com>
 * @copyright Dean Blackborough (Costs to Expect) 2018-2022
 * https://github.com/costs-to-expect/yatzy/blob/main/LICENSE
 */
class Bye extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        //
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Yatzy Game Scorer: Bye')
            ->greeting('Hi!')
            ->line('Your Yatzy account has been deleted.')
            ->line('As per our account page, your Yatzy account has been removed but you 
             may still have data on other parts of our service.')
            ->line('If you want to remove all your data from our service please use the relevant option within one 
             of our Apps')
            ->line('Thank you for using our Yatzy scorer, we hope we will see you again.');
    }

    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}