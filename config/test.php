<?php

$accounts = json_decode(env('TEST_ACCOUNTS', '{}'), true);

return [
    'phone' => env('TEST_PHONE'),
    'otp' => env('TEST_OTP'),
    'accounts' => is_array($accounts) ? $accounts : [],
];
