<!doctype html>
<html lang="{{ $details['locale'] }}" dir="{{ $details['locale'] === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Your kit pickup QR') }}</title>
</head>
<body style="margin:0;padding:24px;background:#f2f6f3;color:#173e34;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;margin:0 auto;background:#fff;border:1px solid #dce8e1;border-radius:14px;overflow:hidden;">
        <tr>
            <td style="padding:24px 28px;background:#064b3e;color:#fff;">
                <div style="font-size:12px;font-weight:800;letter-spacing:2px;">KFS RUNNERS</div>
                <div style="margin-top:5px;color:#b8d7cb;font-size:12px;">{{ __('Kafr El-Sheikh, Egypt') }}</div>
            </td>
        </tr>
        <tr>
            <td style="padding:28px;text-align:center;">
                <h1 style="margin:0 0 10px;font-size:25px;line-height:1.25;color:#123f35;">{{ __('Your kit pickup QR is ready') }}</h1>
                <p style="margin:0 auto 22px;max-width:500px;color:#62746b;font-size:14px;line-height:1.7;">{{ __('Keep this email on your phone and show the QR at the KFS Runners kit pickup desk. The QR image is also attached as a PNG.') }}</p>

                <div style="display:inline-block;padding:14px;border:1px solid #dce8e1;border-radius:12px;background:#fff;">
                    <img src="{{ $message->embedData($qrPng, 'runner-qr-' . $details['reference'] . '.png', 'image/png') }}" width="240" height="240" alt="{{ __('Runner kit pickup QR code') }}" style="display:block;width:240px;height:240px;">
                </div>

                <div style="margin:20px auto 0;max-width:460px;padding:16px 18px;border-radius:10px;background:#f5f8f6;text-align:{{ $details['locale'] === 'ar' ? 'right' : 'left' }};font-size:14px;line-height:1.9;">
                    <div><span style="color:#718279;">{{ __('Runner') }}:</span> <strong>{{ $details['name'] }}</strong></div>
                    <div><span style="color:#718279;">{{ __('Race number') }}:</span> <strong dir="ltr">#{{ $details['race_number'] }}</strong></div>
                    <div><span style="color:#718279;">{{ __('Registration reference') }}:</span> <strong dir="ltr">{{ $details['reference'] }}</strong></div>
                    <div><span style="color:#718279;">{{ __('Race') }}:</span> <strong>{{ $details['race'] }}</strong></div>
                    <div><span style="color:#718279;">{{ __('Distance') }}:</span> <strong>{{ $details['distance'] }}</strong></div>
                    <div><span style="color:#718279;">{{ __('Package') }}:</span> <strong>{{ $details['kit'] === 'full' ? __('Full Kit') : __('Basic Kit') }}@if($details['shirt_size']) ({{ __('Size') }}: {{ $details['shirt_size'] }})@endif</strong></div>
                    @if ($details['team'])
                        <div><span style="color:#718279;">{{ __('Team') }}:</span> <strong>{{ $details['team'] }}</strong></div>
                    @endif
                </div>

                <p style="margin:22px 0 0;color:#17835f;font-size:13px;font-weight:700;">{{ __('Payment confirmed') }}</p>
                <p style="margin:20px 0 0;color:#75877d;font-size:12px;line-height:1.7;">{{ __('Thank you for joining KFS Runners.') }}</p>
            </td>
        </tr>
    </table>
</body>
</html>
