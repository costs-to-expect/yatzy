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
class ForgotPassword extends Notification implements ShouldQueue
{
    use Queueable;

    private string $email;
    private string $encrypted_token;

    public function __construct(string $email, string $encrypted_token)
    {
        $this->email = $email;
        $this->encrypted_token = $encrypted_token;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Yatzy Game Scorer: Create a new Password!')
            ->greeting('Hi Yatzy Player!')
            ->line('Please find below the link to create a new password for your account.')
            ->action(
                'Create Password',
                url('/create-new-password') . '?encrypted_token=' . urlencode($this->encrypted_token) .
                    '&email=' . urlencode($this->email)
            )
            ->line('If you did not start this request, please ignore it, let us know privately if it continues to happen.')
            ->line('Thank you for using our Game Scorer, we hope you enjoy it!');
    }

    public function toArray($notifiable)
    {
        return [
            'email' => $this->email,
        ];
    }
}
