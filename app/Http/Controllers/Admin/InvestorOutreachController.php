<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\InvestorOutreachMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InvestorOutreachController extends Controller
{
    public function create()
    {
        return view('admin.communications.investor-outreach', [
            'defaults' => [
                'from_name' => config('mail.investor_outreach_from.name', 'Tlhologelo Mabitle'),
                'from_email' => config('mail.investor_outreach_from.address', 'tlhologelo.mabitle@bwiser.co.za'),
                'subject' => 'Bwiser pre-seed investment opportunity',
                'headline' => 'Bwiser is opening pre-seed conversations',
                'preheader' => 'Fuel finance, voucher rails, and real-time station operations built for scale.',
                'intro' => "Hi,\n\nI am reaching out from Bwiser because we are opening a focused set of pre-seed investment conversations with aligned operators and early-stage fintech investors.\n\nBwiser is building a South African fuel finance and voucher platform connecting drivers, stations, and finance teams on one operating rail.",
                'thesis' => "We are solving a practical operational gap:\n- fuel access and controlled voucher issuance for drivers\n- role-based approvals and credit controls\n- station-level redemption with settlement visibility\n- a platform layer designed for real-time operations and auditability",
                'traction' => "We already have live merchant and driver onboarding workflows, repayment logic, operational controls, and a growing early-access base positioned ahead of funding activation.",
                'ask' => 'We are seeking pre-seed investment to deepen product execution, scale network onboarding, and accelerate commercial rollout.',
                'cta_text' => 'View Bwiser',
                'cta_url' => config('app.url') ?: url('/'),
                'closing' => "If this is within your current investment focus, I would value the chance to share the deck and walk you through the operating model.\n\nRegards,\nTlhologelo Mabitle\nFounder, Bwiser",
            ],
        ]);
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'recipients' => ['required', 'string'],
            'subject' => ['required', 'string', 'max:180'],
            'headline' => ['required', 'string', 'max:180'],
            'preheader' => ['nullable', 'string', 'max:255'],
            'intro' => ['required', 'string', 'max:5000'],
            'thesis' => ['nullable', 'string', 'max:5000'],
            'traction' => ['nullable', 'string', 'max:5000'],
            'ask' => ['nullable', 'string', 'max:5000'],
            'cta_text' => ['required', 'string', 'max:80'],
            'cta_url' => ['required', 'url', 'max:500'],
            'closing' => ['required', 'string', 'max:3000'],
            'attachments' => ['nullable', 'array', 'max:6'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,ppt,pptx,doc,docx,xls,xlsx,jpg,jpeg,png'],
        ]);

        $recipients = $this->normalizeRecipients($validated['recipients']);
        if (empty($recipients)) {
            return back()
                ->withErrors(['recipients' => 'Add at least one valid email recipient.'])
                ->withInput();
        }

        $attachments = collect($request->file('attachments', []))
            ->filter()
            ->map(fn ($file) => [
                'name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType() ?: 'application/octet-stream',
                'content' => file_get_contents($file->getRealPath()),
            ])
            ->values()
            ->all();

        $payload = [
            'subject' => trim($validated['subject']),
            'headline' => trim($validated['headline']),
            'preheader' => trim((string) ($validated['preheader'] ?? '')),
            'intro' => trim($validated['intro']),
            'thesis' => trim((string) ($validated['thesis'] ?? '')),
            'traction' => trim((string) ($validated['traction'] ?? '')),
            'ask' => trim((string) ($validated['ask'] ?? '')),
            'cta_text' => trim($validated['cta_text']),
            'cta_url' => trim($validated['cta_url']),
            'closing' => trim($validated['closing']),
            'heroImageUrl' => asset('images/bwsr.png'),
        ];

        $sent = [];
        $failed = [];

        foreach ($recipients as $email) {
            try {
                Mail::to($email)->send(new InvestorOutreachMail($payload, $attachments));
                $sent[] = $email;
            } catch (\Throwable $e) {
                $failed[] = $email;
                Log::warning('Investor outreach email failed', [
                    'recipient' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $message = 'Investor outreach sent to ' . count($sent) . ' recipient(s).';
        if (!empty($failed)) {
            $message .= ' Failed: ' . implode(', ', $failed);
        }

        return back()->with($failed ? 'warning' : 'success', $message);
    }

    private function normalizeRecipients(string $raw): array
    {
        return collect(preg_split('/[\s,;]+/', $raw) ?: [])
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL))
            ->map(fn ($value) => Str::lower($value))
            ->unique()
            ->values()
            ->all();
    }
}
