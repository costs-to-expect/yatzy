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
 *
 * @property string $token
 * @property string $email
 */
class Registered extends Notification implements ShouldQueue
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
            ->subject('Yatzy Game Scorer: Registered')
            ->greeting('Hi Yatzy Player!')
            ->line('Thank you for creating an account on our Yatzy game scorer.')
            ->line('When you sign-in you will be able to start scoring your games.')
            ->line('The Yatzy game scorer is powered by the Costs to Expect API, your account is usable across all of our services')
            ->line('If you registered in error or want to delete your account, just access the account section of our App.')
            ->action('Sign-in', url('/sign-in'))
            ->line('Again, Thank you for choosing our scorer, we hope you have fun scoring your games.');
    }

    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}