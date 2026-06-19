<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ₹1 Trial + UPI Autopay (Textbook-style)
    |--------------------------------------------------------------------------
    */

    'enabled' => env('TRIAL_AUTOPAY_ENABLED', true),
    'price' => (int) env('TRIAL_PRICE', 1),
    'days' => (int) env('TRIAL_DAYS', 2),
    'plan_slug' => env('TRIAL_PLAN_SLUG', 'lite'),
    'renewal_price' => (int) env('TRIAL_RENEWAL_PRICE', 79),
    'razorpay_plan_id' => env('RAZORPAY_LITE_MONTHLY_PLAN_ID'),
    'total_billing_cycles' => (int) env('TRIAL_TOTAL_BILLING_CYCLES', 60),
    'addon_name' => env('TRIAL_ADDON_NAME', 'BlinkStudy 2-Day Trial'),

    'offer' => [
        'headline' => '₹1 me 2 din try karo',
        'subline' => 'Phir auto ₹79/month — kabhi bhi cancel karo',
        'tag' => 'NEW USER OFFER',
    ],

    /*
    |--------------------------------------------------------------------------
    | Mobile paywall UI copy (served via GET /api/trial/offer)
    |--------------------------------------------------------------------------
    */
    'ui' => [
        'screen_title' => 'Padhai Shuru Karo',
        'screen_subtitle' => 'Pehle try karo, phir decide karo',

        'hero_badge' => '🔥 Limited Offer',
        'hero_title' => 'Sirf ₹1 me 2 Din',
        'hero_subtitle' => 'Full Lite access — AI chat, quiz, scan & solve',
        'hero_price_strike' => '₹79',
        'hero_price_trial' => '₹1',
        'hero_price_note' => '2 din ke liye',

        'cta_primary' => '₹1 me Start Karo',
        'cta_loading' => 'Payment khul raha hai…',
        'cta_secondary' => 'Saare Plans Dekho',

        'trust_items' => [
            'UPI se safe payment',
            'Kabhi bhi cancel — Settings se',
            'Sirf 1 baar trial',
        ],

        'timeline' => [
            ['day' => 'Aaj', 'text' => '₹1 pay karo → Lite unlock'],
            ['day' => '2 Din', 'text' => 'Full AI padhai — limits ke saath'],
            ['day' => 'Day 3', 'text' => 'Auto ₹79/month (cancel optional)'],
        ],

        'legal_short' => 'Continue karke aap UPI Autopay approve karte ho. Trial ke baad ₹79/month charge hoga jab tak cancel na karo.',
        'legal_link_text' => 'Terms & Refund Policy',

        'ineligible_title' => 'Trial pehle use ho chuka',
        'ineligible_body' => 'Aap paid plan choose kar sakte ho — Lite se shuru karo ₹79/month.',

        'success_title' => 'Trial Active! 🎉',
        'success_body' => 'Ab 2 din Lite features use karo. Settings se autopay cancel kar sakte ho.',

        'cancel_title' => 'Autopay Cancel?',
        'cancel_body' => 'Cancel karne ke baad naya charge nahi hoga. Jo din bache hain un tak access rahega.',
        'cancel_confirm' => 'Haan, Cancel Karo',
        'cancel_dismiss' => 'Nahi, Rakh Lo',

        'features_trial' => [
            'Unlimited AI Chat (Lite)',
            '10 Scan & Solve / day',
            'Topic Quiz & Exam Prep',
            'No ads on Lite',
        ],

        'features_after' => [
            'headline' => 'Trial ke baad',
            'price' => '₹79/month',
            'note' => 'Autopay — Google Play jaisa cancel anytime',
        ],
    ],
];
