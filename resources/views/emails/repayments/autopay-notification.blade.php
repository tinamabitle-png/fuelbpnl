@php
    $appName = 'Bwiser';
    $supportEmail = config('seo.support_email', 'support@bwiser.co.za');
    $ticket = $ticket ?? [];
    $preheader = $preheader ?? ($subject ?? 'Auto-Pay update');
    $body = $body ?? null;
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject ?? 'Bwiser Auto-Pay Update' }}</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;color:#0f172a;font-family:Arial,Helvetica,sans-serif;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;line-height:1px;">
        {{ $preheader }}
    </div>

    <div style="max-width:680px;margin:0 auto;padding:28px 16px;">
        <div style="text-align:center;margin-bottom:14px;">
            <div style="display:inline-flex;align-items:center;gap:10px;">
                <img src="{{ $logo_url ?? asset('images/brand-logo.png') }}" width="34" height="34" alt="Bwiser" style="display:block;border-radius:10px;">
                <span style="font-weight:900;letter-spacing:-0.02em;font-size:18px;color:#0f172a;">BWISER</span>
            </div>
        </div>

        <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:18px;padding:18px 18px 16px 18px;">
            <h1 style="margin:0 0 8px 0;font-size:18px;line-height:1.3;color:#0f172a;">
                {{ $heading ?? ($subject ?? 'Auto-Pay update') }}
            </h1>
            @if(!empty($body))
                <p style="margin:0 0 14px 0;font-size:13px;line-height:1.6;color:#475569;">
                    {{ $body }}
                </p>
            @endif

            @if(!empty($ticket))
                <div style="margin-top:10px;">
                    <div style="border-radius:18px;overflow:hidden;border:1px solid rgba(15,23,42,0.12);background:#0b1220;">
                        <div style="padding:18px 18px 16px 18px;background:#1e1e24;color:#f8fafc;">
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
                                <tr>
                                    <td style="vertical-align:top;">
                                        <div style="display:inline-flex;align-items:center;gap:10px;">
                                            <img src="{{ $logo_url ?? asset('images/brand-logo.png') }}" width="28" height="28" alt="Bwiser" style="display:block;border-radius:9px;">
                                            <span style="font-weight:900;letter-spacing:-0.02em;font-size:14px;color:#ffffff;">BWISER</span>
                                        </div>
                                    </td>
                                    <td style="vertical-align:top;text-align:right;">
                                        <span style="display:inline-block;font-size:11px;letter-spacing:0.14em;text-transform:uppercase;color:#a5b4fc;border:1px solid rgba(165,180,252,0.7);padding:6px 10px;border-radius:999px;font-weight:700;">
                                            Voucher
                                        </span>
                                    </td>
                                </tr>
                            </table>

                            <div style="margin-top:14px;">
                                <div style="font-size:26px;line-height:1.05;font-weight:900;text-transform:uppercase;color:#ffffff;">
                                    Voucher<br>
                                    <span style="color:#a5b4fc;">{{ $ticket['voucher_code'] ?? 'N/A' }}</span>
                                </div>
                            <div style="margin-top:10px;font-size:13px;line-height:1.5;color:#94a3b8;">
                                <table role="presentation" cellpadding="0" cellspacing="0" style="display:inline-table;border-collapse:separate;border-spacing:0;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.12);border-radius:999px;">
                                    <tr>
                                        <td style="padding:6px 0 6px 10px;vertical-align:middle;">
                                            @if(!empty($ticket['station_logo_url']))
                                                <img src="{{ $ticket['station_logo_url'] }}" width="18" height="18" alt="{{ $ticket['station_name'] ?? 'Station' }}" style="display:block;border-radius:999px;background:#ffffff;padding:2px;">
                                            @else
                                                <span style="display:inline-block;width:18px;height:18px;border-radius:999px;background:rgba(165,180,252,0.22);color:#a5b4fc;font-weight:900;font-size:11px;line-height:18px;text-align:center;">
                                                    {{ strtoupper(substr((string) ($ticket['station_name'] ?? 'S'), 0, 1)) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td style="padding:6px 12px 6px 8px;vertical-align:middle;color:#e2e8f0;font-weight:800;font-size:13px;line-height:1;">
                                            {{ $ticket['station_name'] ?? 'N/A' }}
                                        </td>
                                    </tr>
                                </table>
                                @if(($ticket['pending_count'] ?? 0) > 0)
                                    <span style="color:#a5b4fc;">&nbsp;• {{ (int) $ticket['pending_count'] }} due • -R {{ $ticket['pending_amount_display'] ?? '0.00' }}</span>
                                @endif
                                @if(($ticket['overdue_count'] ?? 0) > 0)
                                    <span style="color:#fca5a5;">&nbsp;• {{ (int) $ticket['overdue_count'] }} overdue</span>
                                @endif
                            </div>
                        </div>

                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;margin-top:14px;">
                                <tr>
                                <td style="width:50%;padding:10px 0;">
                                    <div style="font-size:11px;letter-spacing:0.12em;text-transform:uppercase;color:#94a3b8;">Next Due</div>
                                    <div style="margin-top:4px;font-size:14px;font-weight:800;color:#f8fafc;">{{ $ticket['next_due_date'] ?? 'N/A' }}</div>
                                    @if(!empty($ticket['overdue_since']))
                                        <div style="margin-top:4px;font-size:11px;line-height:1.4;color:#94a3b8;">Overdue since {{ $ticket['overdue_since'] }}</div>
                                    @endif
                                </td>
                                    <td style="width:50%;padding:10px 0;">
                                        <div style="font-size:11px;letter-spacing:0.12em;text-transform:uppercase;color:#94a3b8;">Amount Due</div>
                                        <div style="margin-top:4px;font-size:14px;font-weight:800;color:#f8fafc;">-R {{ $ticket['pending_amount_display'] ?? '0.00' }}</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width:50%;padding:10px 0;">
                                        <div style="font-size:11px;letter-spacing:0.12em;text-transform:uppercase;color:#94a3b8;">Due Items</div>
                                        <div style="margin-top:4px;font-size:14px;font-weight:800;color:#f8fafc;">{{ (int) ($ticket['pending_count'] ?? 0) }}</div>
                                    </td>
                                    <td style="width:50%;padding:10px 0;">
                                        <div style="font-size:11px;letter-spacing:0.12em;text-transform:uppercase;color:#94a3b8;">Driver</div>
                                        <div style="margin-top:4px;font-size:14px;font-weight:800;color:#f8fafc;">{{ $ticket['driver_name'] ?? 'Driver' }}</div>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div style="height:1px;border-top:2px dashed rgba(255,255,255,0.18);"></div>

                        <div style="background:#2b2b36;color:#f8fafc;padding:16px 18px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
                                <tr>
                                    <td style="vertical-align:middle;">
                                        <div style="font-size:11px;letter-spacing:0.12em;text-transform:uppercase;color:#94a3b8;">Voucher QR</div>
                                        <div style="margin-top:10px;display:inline-block;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.08);border-radius:14px;padding:10px;">
                                            @if(!empty($ticket['voucher_qr_image']))
                                                <img src="{{ $ticket['voucher_qr_image'] }}" width="120" height="120" alt="Voucher QR {{ $ticket['voucher_code'] ?? '' }}" style="display:block;border-radius:10px;background:#ffffff;">
                                            @else
                                                <div style="width:120px;height:120px;border-radius:10px;background:rgba(255,255,255,0.08);display:flex;align-items:center;justify-content:center;font-weight:900;color:#a5b4fc;">
                                                    QR
                                                </div>
                                            @endif
                                        </div>
                                        <div style="margin-top:10px;font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;font-size:12px;letter-spacing:0.14em;color:#94a3b8;">
                                            {{ $ticket['voucher_code'] ?? 'N/A' }}
                                        </div>
                                    </td>
                                    <td style="vertical-align:middle;text-align:right;">
                                        <div style="font-size:11px;letter-spacing:0.12em;text-transform:uppercase;color:#94a3b8;">Lease</div>
                                        <div style="margin-top:6px;font-size:34px;font-weight:900;line-height:1;color:#a5b4fc;">
                                            {{ $ticket['lease_id'] ?? '--' }}
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            @if(!empty($paystack_url) || (!empty($cta_url) && !empty($cta_label)))
                <div style="margin-top:16px;">
                    @if(!empty($paystack_url))
                        <a href="{{ $paystack_url }}" style="display:inline-block;background:#0f172a;color:#ffffff;text-decoration:none;font-weight:900;border-radius:12px;padding:11px 14px;font-size:13px;">
                            {{ $paystack_label ?? 'Pay with Paystack' }}
                        </a>
                    @endif
                    @if(!empty($paystack_url) && !empty($cta_url) && !empty($cta_label))
                        <span style="display:inline-block;width:10px;"></span>
                    @endif
                    @if(!empty($cta_url) && !empty($cta_label))
                        <a href="{{ $cta_url }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:800;border-radius:12px;padding:11px 14px;font-size:13px;">
                            {{ $cta_label }}
                        </a>
                    @endif
                </div>
            @endif

            <p style="margin:16px 0 0 0;font-size:11px;color:#64748b;line-height:1.6;">
                Need help? Reply to this email or contact <a href="mailto:{{ $supportEmail }}" style="color:#2563eb;text-decoration:none;font-weight:700;">{{ $supportEmail }}</a>.
            </p>
        </div>

        <p style="margin:14px 0 0 0;font-size:11px;color:#94a3b8;text-align:center;">
            © {{ date('Y') }} {{ $appName }}
        </p>
    </div>
</body>
</html>
