<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileCompletionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();
        $needsPhone = $user->needsPhoneCollection();

        $validated = $request->validate([
            'name' => 'required|string|min:2|max:50',
            'email' => 'nullable|email|max:255|unique:users,email,' . $user->id,
            'mobile' => ($needsPhone ? 'required' : 'nullable') . '|string|regex:/^[6-9]\d{9}$/|unique:users,mobile,' . $user->id,
        ]);

        $user->name = $validated['name'];

        if ($needsPhone) {
            $user->email = $user->profileEmail() ?? $user->email;
            $user->mobile = $validated['mobile'];
            $user->mobile_verified_at = now();
        } elseif (! empty($validated['email'])) {
            $user->email = $validated['email'];
        }

        $user->save();
        $user->markProfileCompleted();

        return response()->json(['success' => true, 'message' => 'Profile updated!']);
    }
}
