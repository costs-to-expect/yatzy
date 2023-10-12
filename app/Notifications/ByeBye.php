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
class ByeBye extends Notification implements ShouldQueue
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
            ->line('Your account has been deleted.')
            ->line('As per our account page, we have removed all your data from across all our apps.')
            ->line('Thank you for using our Yatzy scorer and other apps, we hope we will see you again.');
    }

    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
