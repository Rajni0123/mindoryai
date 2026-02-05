<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml">
<head>
    <meta charset="utf-8">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no, url=no">
    <!--[if mso]>
    <noscript><xml><o:OfficeDocumentSettings xmlns:o="urn:schemas-microsoft-com:office:office"><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
    <style>td,th,div,p,a,h1,h2,h3,h4,h5,h6 {font-family: "Segoe UI", sans-serif; mso-line-height-rule: exactly;}</style>
    <![endif]-->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #f1f5f9; color: #1a1a2e; -webkit-font-smoothing: antialiased; }
        .email-wrapper { max-width: 600px; margin: 0 auto; background: #ffffff; }
        @media only screen and (max-width: 600px) {
            .email-wrapper { width: 100% !important; }
            .content-padding { padding: 24px 20px !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9;">
        <tr>
            <td align="center" style="padding: 32px 16px;">
                <table role="presentation" class="email-wrapper" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 4px 24px rgba(0,0,0,0.06);">

                    {{-- Header --}}
                    <tr>
                        <td style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); padding: 28px 40px; text-align: center;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        @php
                                            $icon = match($alertType) {
                                                'new_user' => '&#128100;',
                                                'payment_success' => '&#128176;',
                                                'payment_failed' => '&#9888;',
                                                default => '&#128276;',
                                            };
                                            $badgeColor = match($alertType) {
                                                'new_user' => '#3b82f6',
                                                'payment_success' => '#059669',
                                                'payment_failed' => '#dc2626',
                                                default => '#f59e0b',
                                            };
                                        @endphp
                                        <img src="{{ config('app.url') }}/images/blinkstudy-logo.png" alt="BlinkStudy" width="48" height="48" style="display:block; margin:0 auto 10px; border-radius:10px;">
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center">
                                        <p style="font-size:11px; color:rgba(255,255,255,0.5); text-transform:uppercase; letter-spacing:1px; margin-bottom:4px;">Admin Alert</p>
                                        <h1 style="font-family:'Inter',sans-serif; font-size:20px; font-weight:700; color:#ffffff; margin:0; line-height:1.3;">
                                            {{ $alertTitle }}
                                        </h1>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td class="content-padding" style="padding: 28px 40px;">
                            {{-- Details Table --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                                <tr>
                                    <td style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:20px;">
                                        <p style="font-size:12px; color:{{ $badgeColor }}; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:12px;">Details</p>
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            @foreach($details as $label => $value)
                                            <tr>
                                                <td style="padding:6px 0; font-size:13px; color:#64748b; width:40%; vertical-align:top;">{{ $label }}</td>
                                                <td style="padding:6px 0; font-size:13px; color:#0f172a; font-weight:600; text-align:right;">{{ $value }}</td>
                                            </tr>
                                            @endforeach
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            {{-- Timestamp --}}
                            <p style="font-size:12px; color:#94a3b8; text-align:center;">
                                Alert generated at {{ now()->format('d M Y, h:i:s A') }} IST
                            </p>

                            {{-- CTA --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:20px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ config('app.url') }}/admin/dashboard"
                                           style="display:inline-block; background:linear-gradient(135deg,#1e293b 0%,#0f172a 100%); color:#ffffff; font-size:14px; font-weight:600; text-decoration:none; padding:12px 32px; border-radius:10px; text-align:center;">
                                            Open Admin Panel
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background:#f8fafc; padding:20px 40px; border-top:1px solid #e2e8f0;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <p style="font-size:11px; color:#94a3b8; line-height:1.6;">
                                            {{ config('app.name', 'BlinkStudy') }} Admin Notifications
                                            <br>This is an automated alert. Do not reply to this email.
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
