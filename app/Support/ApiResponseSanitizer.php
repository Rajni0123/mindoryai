<?php

namespace App\Support;

use App\Models\User;

class ApiResponseSanitizer
{
    public static function userProfile(User $user, array $extras = []): array
    {
        return array_merge([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'phone_number' => $user->mobile,
            'student_class' => $user->student_class,
            'target_exam' => $user->target_exam,
            'subjects' => $user->favorite_subject,
            'plan_id' => $user->plan_id,
            'plan_expires_at' => $user->plan_expires_at,
            'is_profile_complete' => (bool) $user->is_profile_complete,
            'profile_completed' => (bool) ($user->profile_completed ?? $user->is_profile_complete),
            'login_count' => $user->login_count,
            'role' => $user->role,
            'is_active' => (bool) $user->is_active,
            'current_streak' => $user->current_streak,
            'referral_code' => $user->referral_code,
            'is_topper' => (bool) ($user->is_topper ?? false),
        ], $extras);
    }
}
