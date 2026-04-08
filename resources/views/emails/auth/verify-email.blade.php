@php
    $appName = 'Bwiser';
    $logoUrl = $logoUrl ?? asset('images/brand-logo.png');
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify your email for {{ $appName }}</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;color:#0f172a;font-family:Arial,Helvetica,sans-serif;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;line-height:1px;">
        Verify your email to complete your {{ $appName }} registration.
    </div>

    <div style="max-width:680px;margin:0 auto;padding:28px 16px;">
        <div style="text-align:center;margin-bottom:14px;">
            <div style="display:inline-flex;align-items:center;gap:10px;">
                <img src="{{ $logoUrl }}" width="34" height="34" alt="{{ $appName }}" style="display:block;border-radius:10px;">
                <span style="font-weight:900;letter-spacing:-0.02em;font-size:18px;color:#0f172a;">{{ strtoupper($appName) }}</span>
            </div>
        </div>

        <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:18px;padding:22px 18px 18px 18px;">
            <p style="margin:0 0 8px 0;font-size:11px;font-weight:800;letter-spacing:0.16em;text-transform:uppercase;color:#2563eb;">
                Registration
            </p>
            <h1 style="margin:0 0 10px 0;font-size:22px;line-height:1.25;color:#0f172a;">
                Welcome to {{ $appName }}
            </h1>
            <p style="margin:0 0 14px 0;font-size:14px;line-height:1.7;color:#475569;">
                Please confirm your email address to secure your account and continue with your onboarding.
            </p>

            <div style="margin:18px 0 18px 0;">
                <a href="{{ $actionUrl }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:800;border-radius:12px;padding:12px 16px;font-size:14px;">
                    {{ $actionText ?? 'Verify Email Address' }}
                </a>
            </div>

            <p style="margin:0;font-size:12px;line-height:1.7;color:#64748b;">
                If you did not create an account, no further action is required.
            </p>
        </div>

        <div style="margin-top:18px;">
            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
                <tr>
                    <td style="padding:0;">
                        <div style="position:relative;background:#111827;border-radius:22px;overflow:hidden;padding:0;">
                            <div style="background:#1f2937;height:44px;"></div>
                            <div style="margin-top:-44px;">
                                <div style="width:0;height:0;border-left:324px solid transparent;border-right:324px solid transparent;border-top:120px solid #0f172a;max-width:100%;line-height:0;font-size:0;"></div>
                            </div>
                            <div style="padding:0 20px 20px 20px;margin-top:-42px;position:relative;z-index:2;">
                                <div style="background:#ffffff;border-radius:20px;padding:24px 22px 20px 22px;box-shadow:0 16px 38px -26px rgba(15,23,42,0.45);text-align:center;">
                                    <img src="{{ $logoUrl }}" width="44" height="44" alt="{{ $appName }}" style="display:block;margin:0 auto 12px auto;border-radius:12px;background:#eff6ff;padding:4px;">
                                    <p style="margin:0 0 6px 0;font-size:11px;font-weight:800;letter-spacing:0.16em;text-transform:uppercase;color:#2563eb;">
                                        Welcome
                                    </p>
                                    <p style="margin:0 0 8px 0;font-size:20px;line-height:1.25;font-weight:900;color:#0f172a;">
                                        Your {{ $appName }} account is almost ready
                                    </p>
                                    <p style="margin:0;font-size:13px;line-height:1.7;color:#475569;">
                                        Verify your email to unlock voucher access, repayment updates, and the full Bwiser onboarding experience.
                                    </p>
                                </div>
                            </div>
                            <div style="margin-top:-10px;">
                                <div style="width:0;height:0;border-left:324px solid transparent;border-right:324px solid transparent;border-bottom:96px solid #1f2937;max-width:100%;line-height:0;font-size:0;"></div>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <p style="margin:14px 0 0 0;font-size:11px;color:#94a3b8;text-align:center;">
            © {{ date('Y') }} {{ $appName }}
        </p>
    </div>
</body>
</html>
