<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailBwiser extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $url = $this->verificationUrl($notifiable);

        $siteName = 'Bwiser';
        $fromAddress = (string) config('mail.from.address');

        return (new MailMessage)
            ->from($fromAddress, $siteName)
            ->subject('Verify your email for ' . $siteName)
            ->view('emails.auth.verify-email', [
                'appName' => $siteName,
                'logoUrl' => asset('images/brand-logo.png'),
                'actionUrl' => $url,
                'actionText' => 'Verify Email Address',
            ]);
    }
}
