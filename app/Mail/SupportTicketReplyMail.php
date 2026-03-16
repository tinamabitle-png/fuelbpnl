<?php

namespace App\Mail;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SupportTicketReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly SupportTicket $ticket,
        public readonly SupportTicketMessage $message,
    ) {
    }

    public function build(): self
    {
        $subject = 'Bwiser Support: Ticket #' . $this->ticket->id . ' Reply';

        return $this
            ->subject($subject)
            ->view('emails.support.ticket-reply', [
                'ticket' => $this->ticket,
                'msg' => $this->message,
            ]);
    }
}

