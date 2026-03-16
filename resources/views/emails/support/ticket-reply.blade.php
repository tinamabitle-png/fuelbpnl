@php
    $userName = $ticket->user?->name ?? 'there';
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bwiser Support Reply</title>
</head>
<body style="margin:0;padding:0;background:#f8fafc;color:#0f172a;font-family:Arial,Helvetica,sans-serif;">
    <div style="max-width:680px;margin:0 auto;padding:24px;">
        <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:20px;">
            <h2 style="margin:0 0 8px 0;font-size:18px;line-height:1.3;">Support reply for Ticket #{{ $ticket->id }}</h2>
            <p style="margin:0 0 16px 0;font-size:14px;color:#475569;">
                Hi {{ $userName }}, our support team replied to your ticket:
            </p>

            <div style="border:1px solid #dbeafe;background:#eff6ff;border-radius:12px;padding:14px;">
                <div style="font-size:12px;color:#334155;margin-bottom:10px;">
                    <strong>Subject:</strong> {{ $ticket->subject }}<br>
                    <strong>Updated:</strong> {{ optional($msg->created_at)->toDayDateTimeString() }}
                </div>
                <div style="white-space:pre-wrap;font-size:14px;color:#0f172a;line-height:1.6;">{{ $msg->body }}</div>
            </div>

            <p style="margin:16px 0 0 0;font-size:12px;color:#64748b;">
                If you need to add more information, reply in the app or contact us at <strong>support@bwiser.co.za</strong>.
            </p>
        </div>
        <p style="margin:14px 0 0 0;font-size:11px;color:#94a3b8;text-align:center;">
            © {{ date('Y') }} Bwiser
        </p>
    </div>
</body>
</html>

