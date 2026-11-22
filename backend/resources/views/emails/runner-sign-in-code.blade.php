<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Your KFS Runners sign-in code') }}</title>
</head>
<body style="margin:0;padding:24px;background:#f2f6f3;color:#173e34;font-family:Arial,Helvetica,sans-serif">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:500px;margin:0 auto;background:#fff;border:1px solid #dce8e1;border-radius:14px;overflow:hidden">
        <tr>
            <td style="padding:20px 24px;background:#064b3e;color:#fff;font-weight:bold">KFS RUNNERS</td>
        </tr>
        <tr>
            <td style="padding:24px">
                <h1 style="margin:0 0 12px;font-size:23px">{{ __('Your KFS Runners sign-in code') }}</h1>
                <p style="margin:0 0 18px;line-height:1.6">{{ __('Enter this code on the page where you requested it. It expires in :minutes minutes.', ['minutes' => $expiresInMinutes]) }}</p>
                <p dir="ltr" style="margin:0 0 18px;padding:14px;background:#f2f6f3;text-align:center;font-size:30px;letter-spacing:8px;font-weight:bold">{{ $code }}</p>
                <p style="margin:0;line-height:1.6">{{ __('If you did not request this code, you can ignore this email.') }}</p>
            </td>
        </tr>
    </table>
</body>
</html>
