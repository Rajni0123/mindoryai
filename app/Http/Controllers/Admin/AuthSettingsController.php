<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FrontendConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class AuthSettingsController extends Controller
{
    /**
     * Display authentication settings page
     */
    public function index()
    {
        // Get OTP settings
        $otpSettings = [
            'provider' => FrontendConfig::getValue('auth.otp.provider', 'renflair'),
            'method' => FrontendConfig::getValue('auth.otp.method', 'sms'),
            'enabled' => FrontendConfig::getValue('auth.otp.enabled', true),
            'length' => FrontendConfig::getValue('auth.otp.length', 4),
            'expiry_minutes' => FrontendConfig::getValue('auth.otp.expiry_minutes', 5),
            'resend_cooldown' => FrontendConfig::getValue('auth.otp.resend_cooldown', 60),
            'max_attempts' => FrontendConfig::getValue('auth.otp.max_attempts', 3),
            'sms_template' => FrontendConfig::getValue('auth.otp.sms_template', 'Your verification code is: {otp}. Valid for {minutes} minutes. - {app_name}'),
            'test_mode' => FrontendConfig::getValue('auth.otp.test_mode', false),
        ];

        // Get Email OTP settings
        $emailOtpSettings = [
            'enabled' => FrontendConfig::getValue('auth.email_otp.enabled', true),
            'length' => FrontendConfig::getValue('auth.email_otp.length', 4),
            'expiry_minutes' => FrontendConfig::getValue('auth.email_otp.expiry_minutes', 5),
            'resend_cooldown' => FrontendConfig::getValue('auth.email_otp.resend_cooldown', 60),
            'max_attempts' => FrontendConfig::getValue('auth.email_otp.max_attempts', 3),
            'email_subject' => FrontendConfig::getValue('auth.email_otp.email_subject', 'Your Verification Code'),
            'email_template' => FrontendConfig::getValue('auth.email_otp.email_template', 'Your verification code is: {otp}. Valid for {minutes} minutes. - {app_name}'),
        ];

        // Get login screen customization
        $loginSettings = [
            'title' => FrontendConfig::getValue('auth.login.title', 'Welcome Back!'),
            'subtitle' => FrontendConfig::getValue('auth.login.subtitle', 'Sign in to continue to your AI assistant'),
            'logo' => FrontendConfig::getValue('auth.login.logo'),
            'background_color' => FrontendConfig::getValue('auth.login.background_color', '#0a0a0a'),
            'primary_color' => FrontendConfig::getValue('auth.login.primary_color', '#0df259'),
            'text_color' => FrontendConfig::getValue('auth.login.text_color', '#ffffff'),
            'phone_placeholder' => FrontendConfig::getValue('auth.login.phone_placeholder', 'Enter your mobile number'),
            'email_placeholder' => FrontendConfig::getValue('auth.login.email_placeholder', 'Enter your email address'),
            'button_text' => FrontendConfig::getValue('auth.login.button_text', 'Send OTP'),
            'terms_text' => FrontendConfig::getValue('auth.login.terms_text', 'By continuing, you agree to our Terms of Service and Privacy Policy'),
            'footer_text' => FrontendConfig::getValue('auth.login.footer_text', 'Need help? Contact ' . config('services.support_email')),
        ];

        // Get social login settings
        $socialSettings = [
            'otp_login_enabled' => FrontendConfig::getValue('auth.otp_login_enabled', true),
            'email_otp_login_enabled' => FrontendConfig::getValue('auth.email_otp_login_enabled', true),
            'whatsapp_otp_enabled' => FrontendConfig::getValue('auth.social.whatsapp_otp_enabled', false),
            'renflair_api_key' => FrontendConfig::getValue('auth.social.renflair_api_key', ''),
            'google_enabled' => FrontendConfig::getValue('auth.social.google.enabled', false),
            'google_client_id' => FrontendConfig::getValue('auth.social.google.client_id'),
            'google_client_secret' => FrontendConfig::getValue('auth.social.google.client_secret'),
            'google_redirect_url' => FrontendConfig::getValue('auth.social.google.redirect_url', url('/auth/google/callback')),
            'apple_enabled' => FrontendConfig::getValue('auth.social.apple.enabled', false),
            'facebook_enabled' => FrontendConfig::getValue('auth.social.facebook.enabled', false),
            'divider_text' => FrontendConfig::getValue('auth.social.divider_text', 'OR'),
        ];

        // Get security settings
        $securitySettings = [
            'max_devices' => FrontendConfig::getValue('auth.security.max_devices', 5),
            'session_timeout' => FrontendConfig::getValue('auth.security.session_timeout', 1440),
            'auto_logout_inactive' => FrontendConfig::getValue('auth.security.auto_logout_inactive', false),
            'require_phone_verification' => FrontendConfig::getValue('auth.security.require_phone_verification', true),
        ];

        return view('admin.auth-settings', compact('otpSettings', 'emailOtpSettings', 'loginSettings', 'socialSettings', 'securitySettings'));
    }

    /**
     * Update OTP settings
     */
    public function updateOtpSettings(Request $request)
    {
        $validated = $request->validate([
            'provider' => 'required|in:renflair,twilio,msg91',
            'method' => 'required|in:sms,whatsapp',
            'enabled' => 'nullable',
            'length' => 'required|integer|min:4|max:8',
            'expiry_minutes' => 'required|integer|min:1|max:30',
            'resend_cooldown' => 'required|integer|min:30|max:300',
            'max_attempts' => 'required|integer|min:3|max:10',
            'sms_template' => 'required|string|max:500',
            'test_mode' => 'nullable',
        ]);

        try {
            FrontendConfig::setValue('auth.otp.provider', $request->provider, 'string');
            FrontendConfig::setValue('auth.otp.method', $request->method, 'string');
            FrontendConfig::setValue('auth.otp.enabled', $request->input('enabled') == '1' ? 'true' : 'false', 'boolean');
            FrontendConfig::setValue('auth.otp.length', $request->length, 'number');
            FrontendConfig::setValue('auth.otp.expiry_minutes', $request->expiry_minutes, 'number');
            FrontendConfig::setValue('auth.otp.resend_cooldown', $request->resend_cooldown, 'number');
            FrontendConfig::setValue('auth.otp.max_attempts', $request->max_attempts, 'number');
            FrontendConfig::setValue('auth.otp.sms_template', $request->sms_template, 'string');
            FrontendConfig::setValue('auth.otp.test_mode', $request->input('test_mode') == '1' ? 'true' : 'false', 'boolean');

            Cache::forget('auth_otp_config');
            FrontendConfig::clearCache();

            return redirect()->back()->with('success', 'OTP settings updated successfully!');
        } catch (\Exception $e) {
            \Log::error('OTP settings update failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Failed to update OTP settings: ' . $e->getMessage());
        }
    }

    /**
     * Update Email OTP settings
     */
    public function updateEmailOtpSettings(Request $request)
    {
        $validated = $request->validate([
            'email_otp_enabled' => 'nullable',
            'email_otp_length' => 'required|integer|min:4|max:8',
            'email_otp_expiry_minutes' => 'required|integer|min:1|max:30',
            'email_otp_resend_cooldown' => 'required|integer|min:30|max:300',
            'email_otp_max_attempts' => 'required|integer|min:3|max:10',
            'email_otp_subject' => 'required|string|max:200',
            'email_otp_template' => 'required|string|max:1000',
            'email_placeholder' => 'required|string|max:100',
        ]);

        try {
            FrontendConfig::setValue('auth.email_otp.enabled', $request->input('email_otp_enabled') == '1' ? 'true' : 'false', 'boolean');
            FrontendConfig::setValue('auth.email_otp.length', $request->email_otp_length, 'number');
            FrontendConfig::setValue('auth.email_otp.expiry_minutes', $request->email_otp_expiry_minutes, 'number');
            FrontendConfig::setValue('auth.email_otp.resend_cooldown', $request->email_otp_resend_cooldown, 'number');
            FrontendConfig::setValue('auth.email_otp.max_attempts', $request->email_otp_max_attempts, 'number');
            FrontendConfig::setValue('auth.email_otp.email_subject', $request->email_otp_subject, 'string');
            FrontendConfig::setValue('auth.email_otp.email_template', $request->email_otp_template, 'string');
            FrontendConfig::setValue('auth.login.email_placeholder', $request->email_placeholder, 'string');

            Cache::forget('auth_email_otp_config');
            FrontendConfig::clearCache();

            return redirect()->back()->with('success', 'Email OTP settings updated successfully!');
        } catch (\Exception $e) {
            \Log::error('Email OTP settings update failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Failed to update email OTP settings: ' . $e->getMessage());
        }
    }

    /**
     * Update login screen customization
     */
    public function updateLoginSettings(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:100',
            'subtitle' => 'nullable|string|max:200',
            'logo' => 'nullable|image|max:2048',
            'background_color' => 'required|string|regex:/^#[0-9A-F]{6}$/i',
            'primary_color' => 'required|string|regex:/^#[0-9A-F]{6}$/i',
            'text_color' => 'required|string|regex:/^#[0-9A-F]{6}$/i',
            'phone_placeholder' => 'required|string|max:100',
            'button_text' => 'required|string|max:50',
            'terms_text' => 'nullable|string|max:500',
            'footer_text' => 'nullable|string|max:200',
        ]);

        try {
            FrontendConfig::setValue('auth.login.title', $request->title, 'string');
            FrontendConfig::setValue('auth.login.subtitle', $request->subtitle ?? '', 'string');
            FrontendConfig::setValue('auth.login.background_color', $request->background_color, 'string');
            FrontendConfig::setValue('auth.login.primary_color', $request->primary_color, 'string');
            FrontendConfig::setValue('auth.login.text_color', $request->text_color, 'string');
            FrontendConfig::setValue('auth.login.phone_placeholder', $request->phone_placeholder, 'string');
            FrontendConfig::setValue('auth.login.button_text', $request->button_text, 'string');
            FrontendConfig::setValue('auth.login.terms_text', $request->terms_text ?? '', 'string');
            FrontendConfig::setValue('auth.login.footer_text', $request->footer_text ?? '', 'string');

            // Handle logo upload
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('auth/logos', 'public');
                FrontendConfig::setValue('auth.login.logo', $logoPath, 'string');
            }

            Cache::forget('auth_login_config');

            return redirect()->back()->with('success', 'Login screen settings updated successfully!');
        } catch (\Exception $e) {
            \Log::error('Login settings update failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Failed to update login settings: ' . $e->getMessage());
        }
    }

    /**
     * Update social login settings
     */
    public function updateSocialSettings(Request $request)
    {
        $validated = $request->validate([
            'otp_login_enabled' => 'nullable',
            'email_otp_login_enabled' => 'nullable',
            'whatsapp_otp_enabled' => 'nullable',
            'renflair_api_key' => 'nullable|string|max:255',
            'google_enabled' => 'nullable',
            'google_client_id' => 'nullable|string|max:255',
            'google_client_secret' => 'nullable|string|max:255',
            'google_redirect_url' => 'nullable|string|max:500',
            'apple_enabled' => 'nullable',
            'facebook_enabled' => 'nullable',
            'divider_text' => 'nullable|string|max:20',
        ]);

        try {
            // Log request data for debugging
            \Log::info('Social settings update request', [
                'otp_login_enabled' => $request->input('otp_login_enabled'),
                'email_otp_login_enabled' => $request->input('email_otp_login_enabled'),
                'whatsapp_otp_enabled' => $request->input('whatsapp_otp_enabled'),
                'google_enabled' => $request->input('google_enabled'),
                'renflair_api_key' => $request->input('renflair_api_key'),
                'all_inputs' => $request->all()
            ]);

            // Validate that at least one login method is enabled
            // Check if value equals "1" (checked) instead of just checking if field exists
            $otpEnabled = $request->input('otp_login_enabled') == '1';
            $emailOtpEnabled = $request->input('email_otp_login_enabled') == '1';
            $whatsappOtpEnabled = $request->input('whatsapp_otp_enabled') == '1';
            $googleEnabled = $request->input('google_enabled') == '1';

            \Log::info('Processed boolean values', [
                'otp_enabled' => $otpEnabled,
                'email_otp_enabled' => $emailOtpEnabled,
                'whatsapp_otp_enabled' => $whatsappOtpEnabled,
                'google_enabled' => $googleEnabled
            ]);

            if (!$otpEnabled && !$emailOtpEnabled && !$whatsappOtpEnabled && !$googleEnabled) {
                return redirect()->back()->with('error', 'At least one login method must be enabled!');
            }

            // Save login method toggles
            FrontendConfig::setValue('auth.otp_login_enabled', $otpEnabled ? 'true' : 'false', 'boolean');
            FrontendConfig::setValue('auth.email_otp_login_enabled', $emailOtpEnabled ? 'true' : 'false', 'boolean');
            FrontendConfig::setValue('auth.social.whatsapp_otp_enabled', $whatsappOtpEnabled ? 'true' : 'false', 'boolean');
            FrontendConfig::setValue('auth.social.renflair_api_key', $request->renflair_api_key ?? '', 'string');

            // Save Google OAuth settings
            FrontendConfig::setValue('auth.social.google.enabled', $googleEnabled ? 'true' : 'false', 'boolean');
            FrontendConfig::setValue('auth.social.google.client_id', $request->google_client_id ?? '', 'string');
            FrontendConfig::setValue('auth.social.google.client_secret', $request->google_client_secret ?? '', 'string');
            FrontendConfig::setValue('auth.social.google.redirect_url', $request->google_redirect_url ?? url('/auth/google/callback'), 'string');

            // Save other social providers
            FrontendConfig::setValue('auth.social.apple.enabled', $request->input('apple_enabled') == '1' ? 'true' : 'false', 'boolean');
            FrontendConfig::setValue('auth.social.facebook.enabled', $request->input('facebook_enabled') == '1' ? 'true' : 'false', 'boolean');
            FrontendConfig::setValue('auth.social.divider_text', $request->divider_text ?? 'OR', 'string');

            Cache::forget('auth_social_config');
            FrontendConfig::clearCache();

            return redirect()->back()->with('success', 'Social login settings updated successfully!');
        } catch (\Exception $e) {
            \Log::error('Social settings update failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Failed to update social login settings: ' . $e->getMessage());
        }
    }

    /**
     * Update security settings
     */
    public function updateSecuritySettings(Request $request)
    {
        $validated = $request->validate([
            'max_devices' => 'required|integer|min:1|max:10',
            'session_timeout' => 'required|integer|min:60|max:10080',
            'auto_logout_inactive' => 'nullable',
            'require_phone_verification' => 'nullable',
        ]);

        try {
            FrontendConfig::setValue('auth.security.max_devices', $request->max_devices, 'number');
            FrontendConfig::setValue('auth.security.session_timeout', $request->session_timeout, 'number');
            FrontendConfig::setValue('auth.security.auto_logout_inactive', $request->input('auto_logout_inactive') == '1' ? 'true' : 'false', 'boolean');
            FrontendConfig::setValue('auth.security.require_phone_verification', $request->input('require_phone_verification') == '1' ? 'true' : 'false', 'boolean');

            Cache::forget('auth_security_config');
            FrontendConfig::clearCache();

            return redirect()->back()->with('success', 'Security settings updated successfully!');
        } catch (\Exception $e) {
            \Log::error('Security settings update failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Failed to update security settings: ' . $e->getMessage());
        }
    }
}
