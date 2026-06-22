<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminCredentialService
{
    public function normalizeMobile(string $mobile): string
    {
        $digits = preg_replace('/\D/', '', $mobile);

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        }

        return $digits;
    }

    public function findAdmin(?string $email = null, ?string $mobile = null): ?User
    {
        if ($mobile) {
            $user = User::where('mobile', $this->normalizeMobile($mobile))->first();
            if ($user?->isAdmin()) {
                return $user;
            }
        }

        if ($email) {
            $user = User::where('email', strtolower(trim($email)))->first();
            if ($user?->isAdmin()) {
                return $user;
            }
        }

        return null;
    }

    /**
     * Create or update an admin account with a correctly hashed password.
     */
    public function resetAdmin(array $options): User
    {
        $password = (string) ($options['password'] ?? '');
        if ($password === '') {
            throw new \InvalidArgumentException('Password is required.');
        }

        $mobile = isset($options['mobile']) ? $this->normalizeMobile((string) $options['mobile']) : null;
        $email = isset($options['email']) ? strtolower(trim((string) $options['email'])) : null;
        $name = (string) ($options['name'] ?? 'Admin');

        $user = null;
        if ($mobile) {
            $user = User::where('mobile', $mobile)->first();
        }
        if (! $user && $email) {
            $user = User::where('email', $email)->first();
        }

        if (! $user) {
            $user = new User();
            $user->email = $email ?? ($mobile . '@admin.blinkstudy.local');
            $user->mobile = $mobile;
            $user->token_limit = 999999;
            $user->tokens_used = 0;
            $user->is_active = true;
        }

        $user->name = $name;
        if ($email) {
            $user->email = $email;
        }
        if ($mobile) {
            $user->mobile = $mobile;
        }
        $user->is_active = true;
        $user->failed_login_attempts = 0;
        $user->locked_until = null;
        $user->save();

        DB::table('users')->where('id', $user->id)->update([
            'password' => Hash::make($password),
            'role' => 'admin',
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'is_active' => 1,
            'updated_at' => now(),
        ]);

        return $user->fresh();
    }

    public function verifyPassword(User $user, string $password): bool
    {
        return Hash::check($password, $user->password);
    }
}
