<!doctype html>
<html lang="{{ $details['locale'] }}" dir="{{ $details['locale'] === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Registration details') }}</title>
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
            <td style="padding:28px;">
                <h1 style="margin:0 0 8px;font-size:25px;line-height:1.25;color:#123f35;">{{ __('Registration details') }}</h1>
                @if ($details['payment_status'] === 'paid')
                    <p style="margin:0 0 22px;color:#17835f;font-size:14px;line-height:1.6;">{{ __('Your registration is confirmed.') }}</p>
                @else
                    <p style="margin:0 0 22px;color:#755b20;font-size:14px;line-height:1.6;">{{ __('Your registration is saved. Payment is still pending.') }}</p>
                @endif

                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;font-size:14px;">
                    <tr><td style="padding:10px 0;border-bottom:1px solid #e7eee9;color:#718279;">{{ __('Registration reference') }}</td><td dir="ltr" style="padding:10px 0;border-bottom:1px solid #e7eee9;text-align:{{ $details['locale'] === 'ar' ? 'left' : 'right' }};font-weight:800;">{{ $details['reference'] }}</td></tr>
                    @if (! $details['is_group'])
                        @if ($details['race_number'])
                            <tr><td style="padding:10px 0;border-bottom:1px solid #e7eee9;color:#718279;">{{ __('Race number') }}</td><td dir="ltr" style="padding:10px 0;border-bottom:1px solid #e7eee9;text-align:{{ $details['locale'] === 'ar' ? 'left' : 'right' }};font-weight:800;">{{ $details['race_number'] }}</td></tr>
                        @endif
                    @endif
                    <tr><td style="padding:10px 0;border-bottom:1px solid #e7eee9;color:#718279;">{{ __('Name') }}</td><td style="padding:10px 0;border-bottom:1px solid #e7eee9;text-align:{{ $details['locale'] === 'ar' ? 'left' : 'right' }};font-weight:700;">{{ $details['name'] }}</td></tr>
                    @if ($details['is_group'])
                        <tr><td style="padding:10px 0;border-bottom:1px solid #e7eee9;color:#718279;">{{ __('Team') }}</td><td style="padding:10px 0;border-bottom:1px solid #e7eee9;text-align:{{ $details['locale'] === 'ar' ? 'left' : 'right' }};font-weight:700;">{{ $details['team_name'] }}</td></tr>
                    @else
                        <tr><td style="padding:10px 0;border-bottom:1px solid #e7eee9;color:#718279;">{{ __('Race') }}</td><td style="padding:10px 0;border-bottom:1px solid #e7eee9;text-align:{{ $details['locale'] === 'ar' ? 'left' : 'right' }};font-weight:700;">{{ $details['race_name'] }}</td></tr>
                        <tr><td style="padding:10px 0;border-bottom:1px solid #e7eee9;color:#718279;">{{ __('Distance') }}</td><td style="padding:10px 0;border-bottom:1px solid #e7eee9;text-align:{{ $details['locale'] === 'ar' ? 'left' : 'right' }};font-weight:700;">{{ $details['distance'] }}</td></tr>
                        <tr><td style="padding:10px 0;border-bottom:1px solid #e7eee9;color:#718279;">{{ __('Package') }}</td><td style="padding:10px 0;border-bottom:1px solid #e7eee9;text-align:{{ $details['locale'] === 'ar' ? 'left' : 'right' }};font-weight:700;">{{ $details['kit'] === 'full' ? __('Full Kit') : __('Basic Kit') }}@if($details['shirt_size']) ({{ __('Size') }}: {{ $details['shirt_size'] }})@endif</td></tr>
                        @if ($details['team_name'])
                            <tr><td style="padding:10px 0;border-bottom:1px solid #e7eee9;color:#718279;">{{ __('Team') }}</td><td style="padding:10px 0;border-bottom:1px solid #e7eee9;text-align:{{ $details['locale'] === 'ar' ? 'left' : 'right' }};font-weight:700;">{{ $details['team_name'] }}</td></tr>
                        @endif
                    @endif
                    <tr><td style="padding:10px 0;border-bottom:1px solid #e7eee9;color:#718279;">{{ __('Registration date') }}</td><td style="padding:10px 0;border-bottom:1px solid #e7eee9;text-align:{{ $details['locale'] === 'ar' ? 'left' : 'right' }};font-weight:700;">{{ $details['registered_at'] }}</td></tr>
                    <tr><td style="padding:10px 0;border-bottom:1px solid #e7eee9;color:#718279;">{{ __('Amount') }}</td><td style="padding:10px 0;border-bottom:1px solid #e7eee9;text-align:{{ $details['locale'] === 'ar' ? 'left' : 'right' }};font-weight:800;">{{ number_format($details['amount'], 2) }} {{ $details['currency'] }}</td></tr>
                    <tr><td style="padding:10px 0;color:#718279;">{{ __('Payment status') }}</td><td style="padding:10px 0;text-align:{{ $details['locale'] === 'ar' ? 'left' : 'right' }};font-weight:800;color:{{ $details['payment_status'] === 'paid' ? '#17835f' : '#a77919' }};">{{ $details['payment_status'] === 'paid' ? __('Paid') : __('Pending payment') }}</td></tr>
                </table>

                @if ($details['payment_status'] === 'paid' && ! empty($details['qr_codes']))
                    <div style="margin-top:26px;padding:18px;border:1px solid #dce8e1;border-radius:12px;background:#f8faf9;text-align:center;">
                        <h2 style="margin:0 0 8px;font-size:19px;color:#123f35;">{{ __('Your kit pickup QR') }}</h2>
                        <p style="margin:0 0 18px;color:#62746b;font-size:13px;line-height:1.6;">{{ __('Keep this QR code on your phone and show it at the KFS Runners kit pickup desk. A PNG copy is attached to this email.') }}</p>
                        @foreach ($details['qr_codes'] as $qrCode)
                            <div style="display:inline-block;vertical-align:top;margin:8px;padding:12px;border:1px solid #dce8e1;border-radius:10px;background:#fff;">
                                <img src="{{ $message->embedData($qrCode['png'], 'runner-qr-' . $qrCode['reference'] . '.png', 'image/png') }}" width="210" height="210" alt="{{ __('Runner kit pickup QR code') }}" style="display:block;width:210px;height:210px;">
                                <div style="margin-top:10px;font-size:12px;line-height:1.6;color:#62746b;">
                                    <strong style="color:#123f35;">{{ $qrCode['name'] }}</strong><br>
                                    {{ __('Race number') }}: <strong dir="ltr">#{{ $qrCode['race_number'] }}</strong>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($details['is_group'] && count($details['runners']))
                    <h2 style="margin:26px 0 10px;font-size:18px;color:#123f35;">{{ __('Runners in this team registration') }}</h2>
                    @foreach ($details['runners'] as $runner)
                        <div style="padding:12px 14px;margin-top:8px;background:#f5f8f6;border-radius:8px;font-size:13px;line-height:1.7;">
                            <strong>{{ $runner['name'] }}</strong> · {{ $runner['race_name'] }} ({{ $runner['distance'] }})<br>
                            {{ $runner['kit'] === 'full' ? __('Full Kit') : __('Basic Kit') }}@if($runner['shirt_size']) · {{ __('Size') }}: {{ $runner['shirt_size'] }}@endif<br>
                            @if ($runner['race_number'])
                                {{ __('Race number') }}: <strong dir="ltr">#{{ $runner['race_number'] }}</strong>
                            @else
                                {{ __('Race number will be assigned after payment is confirmed.') }}
                            @endif
                            · <span dir="ltr">{{ $runner['reference'] }}</span>
                        </div>
                    @endforeach
                @endif

                @if ($details['checkout_url'])
                    <div style="margin-top:25px;text-align:center;">
                        <a href="{{ $details['checkout_url'] }}" style="display:inline-block;padding:14px 22px;border-radius:8px;background:#e51b2b;color:#fff;text-decoration:none;font-size:14px;font-weight:800;">{{ __('Continue to payment') }}</a>
                    </div>
                @elseif ($details['payment_status'] !== 'paid')
                    <p style="margin:20px 0 0;padding:13px 15px;border-radius:8px;background:#fff8e8;color:#775d22;font-size:13px;line-height:1.6;">{{ __('Keep this reference and contact the race team before submitting another registration.') }}</p>
                @endif

                <p style="margin:26px 0 0;color:#75877d;font-size:12px;line-height:1.7;">{{ __('Thank you for joining KFS Runners.') }}</p>
            </td>
        </tr>
    </table>
</body>
</html>
