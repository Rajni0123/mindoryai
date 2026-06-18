<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml">
<head>
    <meta charset="utf-8">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no, url=no">
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings xmlns:o="urn:schemas-microsoft-com:office:office">
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <style>
        td,th,div,p,a,h1,h2,h3,h4,h5,h6 {font-family: "Segoe UI", sans-serif; mso-line-height-rule: exactly;}
    </style>
    <![endif]-->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #f0fdfa; color: #1a1a2e; -webkit-font-smoothing: antialiased; }
        .email-wrapper { max-width: 600px; margin: 0 auto; background: #ffffff; }

        @media only screen and (max-width: 600px) {
            .email-wrapper { width: 100% !important; }
            .content-padding { padding: 24px 20px !important; }
            .plan-card { padding: 20px !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#f0fdfa;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f0fdfa;">
        <tr>
            <td align="center" style="padding: 32px 16px;">
                <table role="presentation" class="email-wrapper" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 4px 24px rgba(13,148,136,0.08);">

                    {{-- Header --}}
                    <tr>
                        <td style="background: linear-gradient(135deg, #0D9488 0%, #0f766e 100%); padding: 32px 40px; text-align: center;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <img src="{{ config('app.url') }}/images/blinkstudy-logo.png" alt="BlinkStudy" width="64" height="64" style="display:block; margin:0 auto 12px; border-radius:12px;">
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center">
                                        <h1 style="font-family:'Inter',sans-serif; font-size:22px; font-weight:700; color:#ffffff; margin:0; line-height:1.3;">
                                            @if($daysLeft === 0)
                                                Your Plan Expires Today!
                                            @elseif($daysLeft === 1)
                                                Your Plan Expires Tomorrow
                                            @else
                                                {{ $daysLeft }} Days Until Plan Expires
                                            @endif
                                        </h1>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td class="content-padding" style="padding: 32px 40px;">
                            {{-- Greeting --}}
                            <p style="font-size:16px; color:#334155; margin-bottom:20px; line-height:1.6;">
                                Hi <strong>{{ $user->name ?? 'there' }}</strong>,
                            </p>

                            <p style="font-size:14px; color:#475569; margin-bottom:24px; line-height:1.7;">
                                @if($daysLeft === 0)
                                    Your <strong>{{ $planName }}</strong> plan expires <strong style="color:#dc2626;">today ({{ $expiryDate }})</strong>. After expiry, your account will be downgraded to the Free plan with limited features.
                                @elseif($daysLeft === 1)
                                    Your <strong>{{ $planName }}</strong> plan expires <strong style="color:#ea580c;">tomorrow ({{ $expiryDate }})</strong>. Renew now to avoid any interruption in your study flow.
                                @elseif($daysLeft <= 3)
                                    Your <strong>{{ $planName }}</strong> plan expires on <strong>{{ $expiryDate }}</strong> — that's just <strong style="color:#ea580c;">{{ $daysLeft }} days away</strong>. Don't lose your premium features!
                                @else
                                    Just a heads up — your <strong>{{ $planName }}</strong> plan expires on <strong>{{ $expiryDate }}</strong>. Renew early to continue enjoying uninterrupted access to all features.
                                @endif
                            </p>

                            {{-- Plan Card --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                                <tr>
                                    <td class="plan-card" style="background:#f0fdfa; border:1px solid #99f6e4; border-radius:12px; padding:24px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td>
                                                    <p style="font-size:12px; color:#0d9488; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:8px;">Current Plan</p>
                                                    <p style="font-size:20px; font-weight:700; color:#0f172a; margin-bottom:4px;">{{ $planName }}</p>
                                                    <p style="font-size:13px; color:#64748b;">
                                                        Expires: {{ $expiryDate }}
                                                        @if($daysLeft <= 3)
                                                            <span style="display:inline-block; background:#fef2f2; color:#dc2626; font-size:11px; font-weight:600; padding:2px 8px; border-radius:20px; margin-left:8px;">
                                                                @if($daysLeft === 0) TODAY
                                                                @elseif($daysLeft === 1) TOMORROW
                                                                @else {{ $daysLeft }} DAYS LEFT
                                                                @endif
                                                            </span>
                                                        @endif
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            {{-- What you'll lose --}}
                            <p style="font-size:14px; font-weight:600; color:#0f172a; margin-bottom:12px;">What happens after expiry:</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                                <tr>
                                    <td style="padding:6px 0; font-size:13px; color:#64748b; line-height:1.6;">
                                        &#10060; &nbsp;Unlimited chat will be limited to 10 messages/day
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; font-size:13px; color:#64748b; line-height:1.6;">
                                        &#10060; &nbsp;Quiz and exam prep features will be restricted
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; font-size:13px; color:#64748b; line-height:1.6;">
                                        &#10060; &nbsp;Chat history will be limited to 3 days
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; font-size:13px; color:#64748b; line-height:1.6;">
                                        &#10060; &nbsp;Ads will be shown on free plan
                                    </td>
                                </tr>
                            </table>

                            {{-- CTA Button --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding-bottom:24px;">
                                        <a href="{{ config('app.url') }}/plans"
                                           style="display:inline-block; background:linear-gradient(135deg,#0D9488 0%,#0f766e 100%); color:#ffffff; font-size:15px; font-weight:600; text-decoration:none; padding:14px 40px; border-radius:12px; text-align:center;">
                                            Renew My Plan
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="font-size:13px; color:#94a3b8; text-align:center; line-height:1.6;">
                                Renew before {{ $expiryDate }} to keep all your premium features active.
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background:#f8fafc; padding:24px 40px; border-top:1px solid #e2e8f0;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <p style="font-size:13px; font-weight:600; color:#0d9488; margin-bottom:8px;">
                                            {{ config('app.name', 'BlinkStudy') }}
                                        </p>
                                        <p style="font-size:11px; color:#94a3b8; line-height:1.6; margin-bottom:8px;">
                                            Your AI-powered study companion for smarter learning.
                                        </p>
                                        <p style="font-size:10px; color:#cbd5e1; line-height:1.6;">
                                            You received this email because you have an active subscription on {{ config('app.name', 'BlinkStudy') }}.
                                            <br>
                                            <a href="{{ config('app.url') }}/support" style="color:#94a3b8; text-decoration:underline;">Contact Support</a>
                                            &nbsp;&bull;&nbsp;
                                            <a href="{{ config('app.url') }}/privacy" style="color:#94a3b8; text-decoration:underline;">Privacy Policy</a>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
