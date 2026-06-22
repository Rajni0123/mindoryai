<?php

namespace App\Http\Controllers;

use App\Models\AccessCode;
use App\Support\ChatSubdomainUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class AccessController extends Controller
{
    /**
     * Show access code verification page
     */
    public function showVerification()
    {
        // If already has access, redirect to chat
        if (Session::has('access_granted')) {
            return redirect()->away(ChatSubdomainUrl::appUrl());
        }

        return view('auth.verify-access');
    }

    /**
     * Verify access code
     */
    public function verifyCode(Request $request)
    {
        $request->validate([
            'access_code' => 'required|string',
        ]);

        $code = strtoupper(trim($request->access_code));

        if (AccessCode::verify($code)) {
            $accessCode = AccessCode::where('code', $code)->first();

            // Mark code as used
            $accessCode->use();

            // Store in session
            Session::put('access_granted', true);
            Session::put('access_code', $code);
            Session::put('access_plan', $accessCode->plan);

            return redirect()->away(ChatSubdomainUrl::appUrl())->with('success', 'Access granted! Welcome to BlinkStudy.');
        }

        return back()->withErrors([
            'access_code' => 'Invalid or expired access code. Please try again.',
        ])->withInput();
    }

    /**
     * Logout / Remove access
     */
    public function logout()
    {
        Session::forget('access_granted');
        Session::forget('access_code');
        Session::forget('access_plan');

        return redirect()->route('home')->with('success', 'You have been logged out successfully.');
    }

    /**
     * Check if user has access
     */
    public static function hasAccess(): bool
    {
        return Session::has('access_granted') && Session::get('access_granted') === true;
    }
}
