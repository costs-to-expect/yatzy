<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\PartialRegistration;
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
class CreatePassword extends Notification implements ShouldQueue
{
    use Queueable;

    private string $email;
    private string $token;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(string $email, string $token)
    {
        $this->email = $email;
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    public function shouldSend($notifiable, $channel)
    {
        return PartialRegistration::query()
                ->where('email', '=', $this->email)
                ->first() !== null;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Yatzy Game Scorer: Create Password')
            ->greeting('Hi Yatzy Player!')
            ->line('You account has been created, you now need to create your password.')
            ->line('Chances are, you have already created your password, this email is just in case, you can pick up where you left off.')
            ->action('Create Password', url('/create-password') . '?token=' . urlencode($this->token) . '&email=' . urlencode($this->email))
            ->line('Thank you for using our Game Scorer, we hope you enjoy it!');

    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'token' => $this->token,
            'email' => $this->email,
        ];
    }
}
