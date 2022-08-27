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
class ApiError extends Notification implements ShouldQueue
{
    use Queueable;

    private string $error;
    private string $message;

    public function __construct(
        string $error,
        string $message
    )
    {
        $this->error = $error;
        $this->message = $message;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Yatzy Game Scorer: Error')
            ->greeting('Hi!')
            ->line('Thank has been an error, details below.')
            ->line('Error: ' . $this->error)
            ->line('Message: ' . $this->message);
    }

    public function toArray($notifiable)
    {
        return [
            'error' => $this->error,
            'message' => $this->message,
        ];
    }
}