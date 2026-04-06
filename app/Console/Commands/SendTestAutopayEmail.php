<?php

namespace App\Console\Commands;

use App\Mail\RepaymentAutopayNotificationMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTestAutopayEmail extends Command
{
    protected $signature = 'repayments:send-test-autopay-email {email : Recipient email address}';

    protected $description = 'Send a sample BWISER auto-pay voucher ticket email to an address.';

    public function handle(): int
    {
        $email = trim((string) $this->argument('email'));
        if ($email === '') {
            $this->error('Recipient email is required.');
            return self::FAILURE;
        }

        $voucherCode = 'VCAWJZEKO3';
        $pendingCount = 7;
        $pendingAmount = 527.38;
        $nextDueDate = '22 Nov 2025';
        $stationName = 'Shell Ladysmith';
        $driverName = 'David Ochieng';
        $leaseId = '63';

        $voucherQrImage = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&margin=10&ecc=H&format=png&data='
            . urlencode($voucherCode);

        $appUrl = rtrim((string) config('app.url', 'https://bwiser.co.za'), '/');
        $payload = [
            'subject' => 'Auto-pay reminder • Voucher ' . $voucherCode,
            'heading' => 'Auto-pay reminder',
            'body' => 'Your voucher repayments are due. Please ensure your auto-pay method has sufficient funds.',
            'preheader' => $voucherCode . ' • ' . $stationName . ' • ' . $pendingCount . ' due • -R ' . number_format($pendingAmount, 2),
            'logo_url' => $appUrl . '/images/brand-logo.png',
            'cta_url' => $appUrl . '/driver/repayments',
            'cta_label' => 'View repayments',
            'ticket' => [
                'voucher_code' => $voucherCode,
                'voucher_qr_image' => $voucherQrImage,
                'station_name' => $stationName,
                'pending_count' => $pendingCount,
                'pending_amount_display' => number_format($pendingAmount, 2),
                'next_due_date' => $nextDueDate,
                'driver_name' => $driverName,
                'lease_id' => $leaseId,
            ],
        ];

        Mail::to($email)->send(new RepaymentAutopayNotificationMail($payload));

        $this->info('Sent test auto-pay email to: ' . $email);
        return self::SUCCESS;
    }
}
