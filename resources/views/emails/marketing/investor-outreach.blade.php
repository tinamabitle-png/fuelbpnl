<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject ?? 'Bwiser Investor Outreach' }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f7fb;color:#0f172a;font-family:Arial,Helvetica,sans-serif;">
    @php
        $appName = config('app.name', 'Bwiser');
        $logoUrl = asset('images/logo.png');
        $heroImageUrl = $heroImageUrl ?? asset('images/bwsr.png');
        $preheaderText = trim((string) ($preheader ?? 'Fuel finance, voucher rails, and real-time station operations built for scale.'));
        $sections = collect([
            trim((string) ($intro ?? '')),
            trim((string) ($thesis ?? '')),
            trim((string) ($traction ?? '')),
            trim((string) ($ask ?? '')),
        ])->filter(fn ($section) => $section !== '');
    @endphp

    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">
        {{ $preheaderText }}
    </div>

    <div style="max-width:760px;margin:0 auto;padding:32px 18px;">
        <div style="text-align:center;margin-bottom:18px;">
            <img src="{{ $logoUrl }}" alt="{{ $appName }} logo" style="width:104px;max-width:100%;height:auto;">
        </div>

        <div style="background:linear-gradient(135deg,#ffffff 0%,#f8fafc 100%);border:1px solid #dbe4f0;border-radius:24px;overflow:hidden;box-shadow:0 20px 40px rgba(15,23,42,0.08);">
            <div style="background:linear-gradient(135deg,#0f172a 0%,#1e3a8a 100%);padding:32px 28px;color:#ffffff;">
                <div style="display:inline-block;padding:7px 12px;border-radius:999px;background:rgba(255,255,255,0.14);font-size:12px;letter-spacing:0.08em;text-transform:uppercase;">
                    Pre-Seed Investment
                </div>
                <p style="margin:14px 0 0;font-size:13px;line-height:1.6;letter-spacing:0.12em;text-transform:uppercase;color:rgba(255,255,255,0.72);">
                    Fetch Your Life
                </p>
                <h1 style="margin:16px 0 10px;font-size:30px;line-height:1.15;font-weight:700;color:#ffffff;">
                    {{ $headline ?? 'Bwiser is opening pre-seed conversations' }}
                </h1>
                <p style="margin:0;font-size:16px;line-height:1.7;color:rgba(255,255,255,0.88);">
                    {{ $preheaderText }}
                </p>
            </div>

            <div style="padding:30px 28px 26px;">
                <div style="margin:0 0 24px;background:#eff6ff;border:1px solid #dbeafe;border-radius:22px;padding:12px;">
                    <img src="{{ $heroImageUrl }}" alt="Bwiser preview" style="display:block;width:100%;height:auto;border-radius:16px;">
                </div>

                @foreach($sections as $section)
                    <div style="margin:0 0 18px;font-size:15px;line-height:1.8;color:#334155;white-space:pre-line;">{{ $section }}</div>
                @endforeach

                <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:18px;padding:18px 18px 14px;margin-bottom:22px;">
                    <div style="font-size:14px;font-weight:700;color:#0f172a;margin-bottom:10px;">Why this matters now</div>
                    <ul style="margin:0;padding-left:18px;color:#334155;font-size:14px;line-height:1.8;">
                        <li>Fuel access and voucher issuance can be controlled in one workflow.</li>
                        <li>Stations, drivers, and finance teams operate on the same audit trail.</li>
                        <li>The product is positioned for early network expansion and institutional funding readiness.</li>
                    </ul>
                </div>

                <div style="text-align:center;margin:24px 0 18px;">
                    <span style="display:inline-block;padding:2px;border-radius:999px;background:linear-gradient(135deg,#00ffff 0%,#ff00ff 100%);box-shadow:0 0 24px rgba(0,255,255,0.18);">
                        <a href="{{ $cta_url }}" style="display:inline-block;background:#1d4ed8;color:#ffffff;text-decoration:none;font-weight:700;font-size:15px;padding:15px 28px;border-radius:999px;border:1px solid rgba(255,255,255,0.18);letter-spacing:0.02em;">
                            {{ $cta_text }}
                        </a>
                    </span>
                </div>

                <div style="margin:0 0 12px;font-size:15px;line-height:1.8;color:#334155;white-space:pre-line;">{{ $closing }}</div>

                <p style="margin:0 0 8px;font-size:13px;line-height:1.7;color:#64748b;text-align:center;">
                    If the button does not open, use this link:
                </p>
                <p style="margin:0 0 8px;text-align:center;word-break:break-word;">
                    <a href="{{ $cta_url }}" style="color:#1d4ed8;font-size:13px;line-height:1.7;text-decoration:underline;">
                        {{ $cta_url }}
                    </a>
                </p>
            </div>
        </div>

        <p style="margin:16px 0 0;text-align:center;font-size:12px;color:#94a3b8;line-height:1.7;">
            Sent by {{ config('mail.investor_outreach_from.name', 'Tlhologelo Mabitle') }} via {{ $appName }}. Replies go to <strong>{{ config('mail.investor_outreach_from.reply_to', 'support@bwiser.co.za') }}</strong>.
        </p>
    </div>
</body>
</html>
