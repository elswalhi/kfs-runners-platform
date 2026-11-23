<!doctype html>
<html lang="ar" dir="rtl">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;padding:24px;background:#f2f6f3;color:#173e34;font-family:Arial,Helvetica,sans-serif">
    <div style="max-width:600px;margin:auto;padding:28px;background:white;border-radius:12px">
        <strong>KFS RUNNERS</strong>
        <h1>حجز باص جديد</h1>
        <p>سجّل {{ $booking->name }} حجزًا وأفاد بتحويل المبلغ. راجع المحفظة وأكّد الدفع من الداشبورد.</p>
        <p>رقم الحجز: <bdi>{{ $booking->booking_number }}</bdi></p>
        <p>رقم المحفظة المُحوِّلة: <bdi>{{ $booking->phone }}</bdi></p>
        <p>المبلغ: {{ number_format((float) $booking->amount, 2) }} جنيه</p>
        <p>حالة الدفع: في انتظار المراجعة</p>
        <a href="{{ route('admin.bus.index', ['search' => $booking->booking_number]) }}">عرض الحجز في الداشبورد</a>
    </div>
</body>
</html>
