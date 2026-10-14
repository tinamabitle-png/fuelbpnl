<?php

namespace Tests\Feature;

use App\Mail\FinanceTeamPlanRequestMail;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FinanceTeamPlanRequestTest extends TestCase
{
    public function test_finance_team_can_email_a_plan_access_request(): void
    {
        Mail::fake();
        config()->set('mail.finance_team_requests_to', 'tlhologelo.mabitle3@gmail.com');

        $response = $this->post(route('finance-team-subscriptions.request-access'), [
            'plan_slug' => 'portfolio',
            'company_name' => 'Example Finance',
            'contact_name' => 'Jane Manager',
            'email' => 'jane@example.test',
            'phone' => '+27 82 123 4567',
            'message' => 'We manage 900 active leases.',
            'website' => '',
        ]);

        $response
            ->assertRedirect('/#finance-team-pricing')
            ->assertSessionHas('success');

        Mail::assertSent(FinanceTeamPlanRequestMail::class, function (FinanceTeamPlanRequestMail $mail): bool {
            return $mail->hasTo('tlhologelo.mabitle3@gmail.com')
                && $mail->requestDetails['plan_name'] === 'Portfolio Pro'
                && $mail->requestDetails['company_name'] === 'Example Finance'
                && $mail->requestDetails['email'] === 'jane@example.test';
        });
    }

    public function test_plan_access_request_rejects_unknown_plans(): void
    {
        Mail::fake();

        $response = $this->from('/#finance-team-pricing')->post(
            route('finance-team-subscriptions.request-access'),
            [
                'plan_slug' => 'unknown',
                'company_name' => 'Example Finance',
                'contact_name' => 'Jane Manager',
                'email' => 'jane@example.test',
            ]
        );

        $response
            ->assertRedirect('/#finance-team-pricing')
            ->assertSessionHasErrors('plan_slug');

        Mail::assertNothingSent();
    }
}
