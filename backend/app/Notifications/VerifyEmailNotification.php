<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        // Signed link valid for 60 minutes (config auth.verification.expire)
        $url = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Confirmez votre adresse e-mail')
            ->greeting('Bonjour ' . $notifiable->first_name . ',')
            ->line('Merci pour votre inscription sur Global Rental Car.')
            ->line('Confirmez votre adresse e-mail pour pouvoir vous connecter.')
            ->action('Confirmer mon adresse e-mail', $url)
            ->line('Ce lien expire dans 60 minutes.')
            ->line("Si vous n'avez pas créé de compte, ignorez cet e-mail.");
    }
}
