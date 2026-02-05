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
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #fef2f2; color: #1a1a2e; -webkit-font-smoothing: antialiased; }
        .email-wrapper { max-width: 600px; margin: 0 auto; background: #ffffff; }
        @media only screen and (max-width: 600px) {
            .email-wrapper { width: 100% !important; }
            .content-padding { padding: 24px 20px !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#fef2f2;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#fef2f2;">
        <tr>
            <td align="center" style="padding: 32px 16px;">
                <table role="presentation" class="email-wrapper" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 4px 24px rgba(220,38,38,0.08);">

                    {{-- Header --}}
                    <tr>
                        <td style="background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); padding: 32px 40px; text-align: center;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <div style="width:48px; height:48px; background:rgba(255,255,255,0.2); border-radius:12px; display:inline-block; line-height:48px; font-size:24px; margin-bottom:12px;">
                                            &#10060;
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center">
                                        <h1 style="font-family:'Inter',sans-serif; font-size:22px; font-weight:700; color:#ffffff; margin:0; line-height:1.3;">
                                            Payment Failed
                                        </h1>
                                        <p style="font-size:14px; color:rgba(255,255,255,0.8); margin-top:8px;">Don't worry, no amount has been deducted</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td class="content-padding" style="padding: 32px 40px;">
                            <p style="font-size:16px; color:#334155; margin-bottom:20px; line-height:1.6;">
                                Hi <strong>{{ $user->name ?? 'there' }}</strong>,
                            </p>

                            <p style="font-size:14px; color:#475569; margin-bottom:24px; line-height:1.7;">
                                We're sorry, but your payment for the <strong>{{ $planName }}</strong> plan could not be processed. Here are the details:
                            </p>

                            {{-- Failed Payment Details --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                                <tr>
                                    <td style="background:#fef2f2; border:1px solid #fecaca; border-radius:12px; padding:24px;">
                                        <p style="font-size:12px; color:#dc2626; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:12px;">Payment Details</p>
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding:6px 0; font-size:13px; color:#64748b;">Plan</td>
                                                <td style="padding:6px 0; font-size:13px; color:#0f172a; font-weight:600; text-align:right;">{{ $planName }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0; font-size:13px; color:#64748b;">Amount</td>
                                                <td style="padding:6px 0; font-size:13px; color:#0f172a; font-weight:600; text-align:right;">&#8377;{{ $amount }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0; font-size:13px; color:#64748b;">Transaction ID</td>
                                                <td style="padding:6px 0; font-size:13px; color:#0f172a; font-weight:600; text-align:right;">{{ $transactionId }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0; font-size:13px; color:#64748b;">Status</td>
                                                <td style="padding:6px 0; font-size:13px; text-align:right;">
                                                    <span style="background:#fef2f2; color:#dc2626; font-size:11px; font-weight:600; padding:3px 10px; border-radius:20px; border:1px solid #fecaca;">FAILED</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0; font-size:13px; color:#64748b;">Reason</td>
                                                <td style="padding:6px 0; font-size:13px; color:#dc2626; font-weight:500; text-align:right;">{{ $reason }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0; font-size:13px; color:#64748b;">Date</td>
                                                <td style="padding:6px 0; font-size:13px; color:#0f172a; font-weight:600; text-align:right;">{{ now()->format('d M Y, h:i A') }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            {{-- What to do --}}
                            <p style="font-size:14px; font-weight:600; color:#0f172a; margin-bottom:12px;">What you can do:</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                                <tr><td style="padding:6px 0; font-size:13px; color:#475569; line-height:1.6;">&#128073; &nbsp;Check your bank/UPI app for sufficient balance</td></tr>
                                <tr><td style="padding:6px 0; font-size:13px; color:#475569; line-height:1.6;">&#128073; &nbsp;Try again with a different payment method</td></tr>
                                <tr><td style="padding:6px 0; font-size:13px; color:#475569; line-height:1.6;">&#128073; &nbsp;If amount was deducted, it will be refunded in 3-5 business days</td></tr>
                                <tr><td style="padding:6px 0; font-size:13px; color:#475569; line-height:1.6;">&#128073; &nbsp;Contact support if the issue persists</td></tr>
                            </table>

                            {{-- CTA --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding-bottom:16px;">
                                        <a href="{{ config('app.url') }}/plans"
                                           style="display:inline-block; background:linear-gradient(135deg,#0D9488 0%,#0f766e 100%); color:#ffffff; font-size:15px; font-weight:600; text-decoration:none; padding:14px 40px; border-radius:12px; text-align:center;">
                                            Try Again
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding-bottom:24px;">
                                        <a href="{{ config('app.url') }}/support"
                                           style="display:inline-block; color:#64748b; font-size:13px; font-weight:500; text-decoration:underline;">
                                            Contact Support
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background:#f8fafc; padding:24px 40px; border-top:1px solid #e2e8f0;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <p style="font-size:13px; font-weight:600; color:#0d9488; margin-bottom:8px;">{{ config('app.name', 'BlinkStudy') }}</p>
                                        <p style="font-size:11px; color:#94a3b8; line-height:1.6; margin-bottom:8px;">Your AI-powered study companion for smarter learning.</p>
                                        <p style="font-size:10px; color:#cbd5e1; line-height:1.6;">
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
