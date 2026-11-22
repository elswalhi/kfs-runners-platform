<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تأكيد حجز الباص</title>
</head>
<body style="margin:0;padding:24px;background:#f2f6f3;color:#173e34;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;margin:auto;background:#fff;border:1px solid #dce8e1;border-radius:14px;">
        <tr><td style="padding:24px 28px;background:#064b3e;color:#fff;font-weight:bold;">KFS RUNNERS</td></tr>
        <tr><td style="padding:28px;">
            <h1 style="margin:0 0 12px;font-size:24px;">تم تأكيد حجز باص سباق زايد الخيري</h1>
            <p style="line-height:1.7;">أهلًا {{ $booking->name }}، تم تأكيد دفع حجزك. احتفظ برقم الحجز للرجوع إليه.</p>
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
                <tr><td style="padding:12px 0;border-bottom:1px solid #e7eee9;">الاسم</td><td style="text-align:left;font-weight:bold;">{{ $booking->name }}</td></tr>
                <tr><td style="padding:12px 0;border-bottom:1px solid #e7eee9;">رقم الحجز</td><td dir="ltr" style="text-align:left;font-weight:bold;">{{ $booking->reference }}</td></tr>
                <tr><td style="padding:12px 0;border-bottom:1px solid #e7eee9;">المبلغ المدفوع</td><td dir="ltr" style="text-align:left;font-weight:bold;">{{ number_format((float) $booking->amount, 2) }} EGP</td></tr>
                <tr><td style="padding:12px 0;">حالة الدفع</td><td style="text-align:left;font-weight:bold;color:#17835f;">مدفوع</td></tr>
            </table>
            <p style="margin-top:24px;line-height:1.7;">شكرًا لانضمامك إلى KFS Runners.</p>
        </td></tr>
    </table>
</body>
</html>
