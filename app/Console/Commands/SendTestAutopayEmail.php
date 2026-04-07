<?php

namespace App\Console\Commands;

use App\Mail\RepaymentAutopayNotificationMail;
use App\Models\Repayment;
use App\Models\User;
use App\Support\StationBrandAssets;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class SendTestAutopayEmail extends Command
{
    protected $signature = 'repayments:send-test-autopay-email {email : Recipient email address} {--repayment_id= : Optional repayment id to use for the Paystack button}';

    protected $description = 'Send a sample BWISER auto-pay voucher ticket email to an address.';

    public function handle(): int
    {
        $email = trim((string) $this->argument('email'));
        if ($email === '') {
            $this->error('Recipient email is required.');
            return self::FAILURE;
        }

        $appUrl = rtrim((string) config('app.url', 'https://bwiser.co.za'), '/');

        $repayment = $this->resolveRepaymentForEmail($email);
        if ($repayment) {
            $payload = $this->buildPayloadForRepayment($repayment, $appUrl);
        } else {
            $payload = $this->buildFallbackPayload($appUrl);
        }

        Mail::to($email)->send(new RepaymentAutopayNotificationMail($payload));

        $this->info('Sent test auto-pay email to: ' . $email);
        return self::SUCCESS;
    }

    private function resolveRepaymentForEmail(string $email): ?Repayment
    {
        $user = User::query()
            ->where('email', $email)
            ->first();

        $repaymentId = (int) $this->option('repayment_id');
        if ($repaymentId > 0) {
            $candidate = Repayment::query()
                ->visibleInSystem()
                ->with(['user', 'lease.vouchers.fuelStation'])
                ->find($repaymentId);

            if ($candidate && $user && (int) $candidate->user_id === (int) $user->id) {
                return $candidate;
            }
        }

        if (!$user) {
            return null;
        }

        return Repayment::query()
            ->visibleInSystem()
            ->with(['user', 'lease.vouchers.fuelStation'])
            ->where('user_id', (int) $user->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->orderBy('due_date')
            ->first();
    }

    private function buildPayloadForRepayment(Repayment $repayment, string $appUrl): array
    {
        $repayment->loadMissing(['user', 'lease.vouchers.fuelStation']);

        $user = $repayment->user;

        $voucher = $repayment->lease?->vouchers?->sortByDesc('id')->first();
        $voucherCode = (string) ($voucher?->code ?: ($repayment->lease_id ? ('LEASE-' . (string) $repayment->lease_id) : ('REPAYMENT-' . (string) $repayment->id)));
        $voucherQrValue = (string) ($voucher?->qr_code ?: $voucherCode);
        $voucherQrImage = $voucherQrValue !== ''
            ? ('https://api.qrserver.com/v1/create-qr-code/?size=160x160&margin=10&ecc=H&format=png&data=' . urlencode($voucherQrValue))
            : null;

        $stationName = $voucher?->fuelStation?->name
            ?? ($repayment->lease?->vouchers?->first()?->fuelStation?->name ?? 'N/A');
        $stationCompany = trim((string) ($voucher?->fuelStation?->company ?? $repayment->lease?->vouchers?->first()?->fuelStation?->company ?? ''));
        $stationLogoUrl = StationBrandAssets::resolveLogoUrl((string) $stationName, $stationCompany);

        $pendingCount = 0;
        $pendingAmount = 0.0;
        $overdueCount = 0;
        $overdueAmount = 0.0;
        $overdueSince = null;
        $nextDueDate = $repayment->due_date
            ? \Illuminate\Support\Carbon::parse($repayment->due_date)->format('d M Y')
            : 'N/A';

        $paystackUrl = null;
        if ($repayment->lease_id && $user) {
            $pendingForLease = Repayment::query()
                ->visibleInSystem()
                ->where('user_id', (int) $user->id)
                ->where('lease_id', (int) $repayment->lease_id)
                ->whereIn('status', ['pending', 'overdue'])
                ->get(['id', 'amount', 'due_date', 'status']);

            $pendingCount = $pendingForLease->count();
            $pendingAmount = (float) $pendingForLease->sum('amount');

            $today = now()->toDateString();
            $overdue = $pendingForLease->filter(fn ($r) => $r->due_date && (string) $r->due_date < $today);
            $upcoming = $pendingForLease->filter(fn ($r) => $r->due_date && (string) $r->due_date >= $today);

            $overdueCount = $overdue->count();
            $overdueAmount = (float) $overdue->sum('amount');
            $oldestOverdue = $overdue->sortBy('due_date')->first();
            if ($oldestOverdue && $oldestOverdue->due_date) {
                $overdueSince = \Illuminate\Support\Carbon::parse($oldestOverdue->due_date)->format('d M Y');
            }

            $nextUpcoming = $upcoming->sortBy('due_date')->first();
            if ($nextUpcoming && $nextUpcoming->due_date) {
                $nextDueDate = \Illuminate\Support\Carbon::parse($nextUpcoming->due_date)->format('d M Y');
            } elseif ($overdueCount > 0) {
                $nextDueDate = 'Due now';
            } else {
                $nextDueDate = 'N/A';
            }

            $payNowTarget = $overdueCount > 0
                ? $overdue->sortBy('due_date')->first()
                : $upcoming->sortBy('due_date')->first();

            if ($payNowTarget && (int) ($payNowTarget->id ?? 0) > 0) {
                $paystackUrl = URL::temporarySignedRoute(
                    'driver.repayments.request.pay_now',
                    now()->addDays(7),
                    ['repayment' => (int) $payNowTarget->id]
                );
            }
        }

        $ticket = [
            'voucher_code' => $voucherCode,
            'voucher_qr_image' => $voucherQrImage,
            'station_name' => Str::limit((string) $stationName, 32),
            'station_logo_url' => $stationLogoUrl,
            'pending_count' => $pendingCount,
            'pending_amount_display' => number_format(abs($pendingAmount), 2),
            'next_due_date' => $nextDueDate,
            'overdue_count' => $overdueCount,
            'overdue_amount_display' => number_format(abs($overdueAmount), 2),
            'overdue_since' => $overdueSince,
            'driver_name' => Str::limit((string) ($user?->name ?? 'Driver'), 26),
            'lease_id' => $repayment->lease_id ? (string) $repayment->lease_id : '--',
        ];

        $body = 'Your repayment of R ' . number_format((float) $repayment->amount, 2) . ' was automatically paid successfully.';
        $preheader = $voucherCode . ' • ' . Str::limit((string) $stationName, 32);
        if ($pendingCount > 0) {
            $preheader .= ' • ' . $pendingCount . ' due • -R ' . number_format(abs($pendingAmount), 2);
        }
        if ($overdueCount > 0) {
            $preheader .= ' • ' . $overdueCount . ' overdue';
        }

        return [
            'subject' => 'Repayment auto-paid',
            'heading' => 'Repayment auto-paid',
            'body' => $body,
            'preheader' => $preheader,
            'logo_url' => $appUrl . '/images/brand-logo.png',
            'cta_url' => $appUrl . '/driver/repayments',
            'cta_label' => 'View repayments',
            'ticket' => $ticket,
            'paystack_url' => $paystackUrl,
            'paystack_label' => 'Pay with Paystack',
        ];
    }

    private function buildFallbackPayload(string $appUrl): array
    {
        $voucherCode = 'VCAWJZEKO3';
        $pendingCount = 7;
        $pendingAmount = 527.38;
        $nextDueDate = '22 Nov 2025';
        $stationName = 'Shell Ladysmith';
        $driverName = 'David Ochieng';
        $leaseId = '63';

        $voucherQrImage = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&margin=10&ecc=H&format=png&data='
            . urlencode($voucherCode);

        return [
            'subject' => 'Repayment auto-paid',
            'heading' => 'Repayment auto-paid',
            'body' => 'Your repayment of R 84.11 was automatically paid successfully.',
            'preheader' => $voucherCode . ' • ' . $stationName . ' • ' . $pendingCount . ' due • -R ' . number_format($pendingAmount, 2),
            'logo_url' => $appUrl . '/images/brand-logo.png',
            'cta_url' => $appUrl . '/driver/repayments',
            'cta_label' => 'View repayments',
            'ticket' => [
                'voucher_code' => $voucherCode,
                'voucher_qr_image' => $voucherQrImage,
                'station_name' => $stationName,
                'station_logo_url' => StationBrandAssets::resolveLogoUrl($stationName),
                'pending_count' => $pendingCount,
                'pending_amount_display' => number_format($pendingAmount, 2),
                'next_due_date' => $nextDueDate,
                'driver_name' => $driverName,
                'lease_id' => $leaseId,
            ],
            'paystack_url' => null,
            'paystack_label' => 'Pay with Paystack',
        ];
    }
}
