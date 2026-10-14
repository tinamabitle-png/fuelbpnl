<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FinanceTeamPlanRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly array $requestDetails)
    {
    }

    public function build(): self
    {
        return $this
            ->replyTo($this->requestDetails['email'], $this->requestDetails['contact_name'])
            ->subject('Finance-team access request: '.$this->requestDetails['plan_name'])
            ->view('emails.finance-team-plan-request', [
                'details' => $this->requestDetails,
            ]);
    }
}
