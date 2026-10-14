<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Finance-team access request</title>
</head>
<body style="margin:0;padding:24px;background:#f8fafc;color:#0f172a;font-family:Arial,Helvetica,sans-serif;">
    <div style="max-width:680px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:20px;overflow:hidden;">
        <div style="padding:24px 28px;background:#0f172a;color:#ffffff;">
            <div style="font-size:12px;letter-spacing:.12em;text-transform:uppercase;color:#f9a8d4;">Bwiser Finance Teams</div>
            <h1 style="margin:10px 0 0;font-size:24px;">New loan-book access request</h1>
        </div>
        <div style="padding:26px 28px;">
            <table role="presentation" style="width:100%;border-collapse:collapse;font-size:15px;line-height:1.6;">
                <tr><td style="padding:7px 0;color:#64748b;width:170px;">Plan</td><td style="padding:7px 0;font-weight:700;">{{ $details['plan_name'] }}</td></tr>
                <tr><td style="padding:7px 0;color:#64748b;">Price</td><td style="padding:7px 0;">R {{ number_format((float) $details['amount'], 2) }} / month</td></tr>
                <tr><td style="padding:7px 0;color:#64748b;">Company</td><td style="padding:7px 0;">{{ $details['company_name'] }}</td></tr>
                <tr><td style="padding:7px 0;color:#64748b;">Contact person</td><td style="padding:7px 0;">{{ $details['contact_name'] }}</td></tr>
                <tr><td style="padding:7px 0;color:#64748b;">Email</td><td style="padding:7px 0;"><a href="mailto:{{ $details['email'] }}">{{ $details['email'] }}</a></td></tr>
                <tr><td style="padding:7px 0;color:#64748b;">Phone</td><td style="padding:7px 0;">{{ $details['phone'] ?: 'Not supplied' }}</td></tr>
                <tr><td style="padding:7px 0;color:#64748b;">Requested at</td><td style="padding:7px 0;">{{ $details['requested_at'] }}</td></tr>
            </table>

            @if($details['message'])
                <div style="margin-top:22px;padding:18px;border-radius:14px;background:#fdf2f8;border:1px solid #fbcfe8;">
                    <strong>Additional information</strong>
                    <p style="margin:8px 0 0;white-space:pre-line;">{{ $details['message'] }}</p>
                </div>
            @endif
        </div>
    </div>
</body>
</html>
