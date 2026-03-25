<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailBwiser extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $url = $this->verificationUrl($notifiable);

        $siteName = (string) config('seo.site_name', config('app.name', 'Bwiser'));
        $fromAddress = (string) config('mail.from.address');

        return (new MailMessage)
            // Force brand name even if APP_NAME / MAIL_FROM_NAME still says Fuel Levy.
            ->from($fromAddress, $siteName !== '' ? $siteName : 'Bwiser')
            ->subject('Verify your email for ' . ($siteName !== '' ? $siteName : 'Bwiser'))
            ->greeting('Welcome to ' . ($siteName !== '' ? $siteName : 'Bwiser'))
            ->line('Please confirm your email address to secure your account and continue.')
            ->action('Verify Email Address', $url)
            ->line('If you did not create an account, no further action is required.')
            ->salutation('Regards, ' . ($siteName !== '' ? $siteName : 'Bwiser'));
    }
}

