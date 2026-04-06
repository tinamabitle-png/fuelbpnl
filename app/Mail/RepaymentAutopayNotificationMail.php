<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RepaymentAutopayNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly array $payload,
    ) {
    }

    public function build(): self
    {
        $subject = (string) ($this->payload['subject'] ?? 'Bwiser Auto-Pay Update');
        $supportEmail = config('seo.support_email', 'support@bwiser.co.za');
        $fromAddress = config('mail.from.address');

        $mail = $this
            ->subject($subject)
            ->replyTo($supportEmail, 'Bwiser Support');

        if (is_string($fromAddress) && trim($fromAddress) !== '') {
            $mail->from($fromAddress, 'Bwiser Support');
        }

        return $mail->view('emails.repayments.autopay-notification', $this->payload);
    }
}

