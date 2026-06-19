<?php

namespace App\Support;

class TestAccountHelper
{
    public static function testPhone(): ?string
    {
        $phone = config('test.phone');

        return is_string($phone) && $phone !== '' ? $phone : null;
    }

    public static function testOtp(): ?string
    {
        $otp = config('test.otp');

        return is_string($otp) && $otp !== '' ? $otp : null;
    }

    public static function isConfiguredTestAccount(string $phone): bool
    {
        return self::testPhone() === $phone && self::testOtp() !== null;
    }

    public static function verifyConfiguredTestOtp(string $phone, string $otp): bool
    {
        return self::isConfiguredTestAccount($phone) && hash_equals(self::testOtp(), $otp);
    }

    /**
     * @return array<string, string>
     */
    public static function additionalAccounts(): array
    {
        $accounts = config('test.accounts', []);

        return is_array($accounts) ? $accounts : [];
    }

    public static function isAdditionalTestAccount(string $phone): bool
    {
        return array_key_exists($phone, self::additionalAccounts());
    }

    public static function verifyAdditionalTestOtp(string $phone, string $otp): bool
    {
        if (!self::isAdditionalTestAccount($phone)) {
            return false;
        }

        return hash_equals((string) self::additionalAccounts()[$phone], $otp);
    }

    public static function isAnyTestAccount(string $phone): bool
    {
        return self::isConfiguredTestAccount($phone) || self::isAdditionalTestAccount($phone);
    }

    public static function verifyAnyTestOtp(string $phone, string $otp): bool
    {
        return self::verifyConfiguredTestOtp($phone, $otp)
            || self::verifyAdditionalTestOtp($phone, $otp);
    }
}
