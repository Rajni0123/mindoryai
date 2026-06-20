<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\PersonalAccessToken;

class ChatSubdomainController extends Controller
{
    public function handleRoot(Request $request): RedirectResponse
    {
        if ($request->filled('auth_token')) {
            $this->loginFromTransferToken((string) $request->query('auth_token'));
        }

        if (Auth::check()) {
            return redirect('/chat');
        }

        return redirect()->away(rtrim((string) config('app.url'), '/') . '/login');
    }

    private function loginFromTransferToken(string $plainToken): void
    {
        $tokenHash = hash('sha256', $plainToken);
        $userId = Cache::pull("chat_auth_transfer:{$tokenHash}");

        if ($userId) {
            $user = User::find($userId);
            if ($user) {
                Auth::login($user);
                request()->session()->regenerate();
            }
        }

        $accessToken = PersonalAccessToken::findToken($plainToken);
        if ($accessToken?->tokenable instanceof User) {
            if (! Auth::check()) {
                Auth::login($accessToken->tokenable);
                request()->session()->regenerate();
            }
            $accessToken->delete();
        }
    }
}
