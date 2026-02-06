<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plan Expired</title>
</head>
<body style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7fa; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">

        <!-- Header -->
        <div style="background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); padding: 40px 30px; text-align: center;">
            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: 700;">Plan Expired</h1>
            <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0; font-size: 16px;">Your subscription has ended</p>
        </div>

        <!-- Content -->
        <div style="padding: 40px 30px;">
            <p style="font-size: 18px; color: #1f2937; margin: 0 0 20px;">Hi {{ $user->name ?? 'there' }},</p>

            <p style="font-size: 16px; color: #4b5563; line-height: 1.6; margin: 0 0 20px;">
                Your <strong style="color: #1f2937;">{{ $planName }}</strong> plan has expired. You've been downgraded to the Free plan with limited features.
            </p>

            <div style="background: #fef2f2; border-left: 4px solid #dc2626; padding: 20px; margin: 20px 0; border-radius: 0 8px 8px 0;">
                <p style="color: #991b1b; margin: 0; font-size: 14px;">
                    <strong>What changed:</strong><br>
                    • Limited AI Chat (50/day)<br>
                    • Limited Quizzes (3/day)<br>
                    • No Whiteboard Videos<br>
                    • Ads will be shown<br>
                    • Watermark on content
                </p>
            </div>

            <p style="font-size: 16px; color: #4b5563; line-height: 1.6; margin: 0 0 30px;">
                Renew your subscription to continue enjoying unlimited access to all premium features.
            </p>

            <!-- CTA Button -->
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ url('/plans') }}" style="display: inline-block; background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%); color: #ffffff; text-decoration: none; padding: 16px 40px; border-radius: 8px; font-size: 16px; font-weight: 600; box-shadow: 0 4px 15px rgba(13, 148, 136, 0.4);">
                    Renew Now
                </a>
            </div>

            <p style="font-size: 14px; color: #9ca3af; text-align: center; margin: 30px 0 0;">
                Questions? Reply to this email or contact our support team.
            </p>
        </div>

        <!-- Footer -->
        <div style="background: #f9fafb; padding: 20px 30px; text-align: center; border-top: 1px solid #e5e7eb;">
            <p style="font-size: 12px; color: #9ca3af; margin: 0;">
                © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
