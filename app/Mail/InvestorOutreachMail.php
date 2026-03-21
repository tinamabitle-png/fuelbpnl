<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvestorOutreachMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly array $payload,
        public readonly array $attachmentPayloads = [],
    ) {
    }

    public function build(): self
    {
        $fromAddress = config('mail.investor_outreach_from.address', 'tlhologelo.mabitle@bwiser.co.za');
        $fromName = config('mail.investor_outreach_from.name', 'Tlhologelo Mabitle');
        $replyTo = config('mail.investor_outreach_from.reply_to', 'support@bwiser.co.za');

        $mail = $this
            ->from($fromAddress, $fromName)
            ->replyTo($replyTo, 'Bwiser Support')
            ->subject($this->payload['subject'] ?? 'Bwiser pre-seed investment opportunity')
            ->view('emails.marketing.investor-outreach', $this->payload);

        foreach ($this->attachmentPayloads as $attachment) {
            $mail->attachData(
                $attachment['content'],
                $attachment['name'],
                ['mime' => $attachment['mime']]
            );
        }

        return $mail;
    }
}
