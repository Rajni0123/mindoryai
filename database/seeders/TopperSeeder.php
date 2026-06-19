<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\TopperWallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TopperSeeder extends Seeder
{
    public function run(): void
    {
        $toppers = [
            [
                'name' => 'Rahul Sharma',
                'email' => 'rahul.topper@blinkstudy.in',
                'mobile' => '9999000001',
                'password' => Hash::make('topper123'),
                'is_topper' => true,
                'is_verified_topper' => true,
                'topper_exam' => 'JEE Advanced',
                'topper_rank' => 45,
                'topper_rating' => 4.9,
                'topper_total_chats' => 156,
                'topper_available' => true,
                'topper_bio' => 'JEE Advanced AIR 45. Currently at IIT Bombay CSE. Love helping students with Physics and Mathematics!',
                'topper_specialization' => 'Physics',
                'topper_subjects' => ['Physics', 'Mathematics', 'Chemistry'],
            ],
            [
                'name' => 'Priya Patel',
                'email' => 'priya.topper@blinkstudy.in',
                'mobile' => '9999000002',
                'password' => Hash::make('topper123'),
                'is_topper' => true,
                'is_verified_topper' => true,
                'topper_exam' => 'NEET',
                'topper_rank' => 12,
                'topper_rating' => 4.95,
                'topper_total_chats' => 234,
                'topper_available' => true,
                'topper_bio' => 'NEET AIR 12. AIIMS Delhi MBBS. Passionate about Biology and helping future doctors!',
                'topper_specialization' => 'Biology',
                'topper_subjects' => ['Biology', 'Chemistry', 'Physics'],
            ],
            [
                'name' => 'Amit Kumar',
                'email' => 'amit.topper@blinkstudy.in',
                'mobile' => '9999000003',
                'password' => Hash::make('topper123'),
                'is_topper' => true,
                'is_verified_topper' => true,
                'topper_exam' => 'JEE Main',
                'topper_rank' => 120,
                'topper_rating' => 4.7,
                'topper_total_chats' => 89,
                'topper_available' => false,
                'topper_bio' => 'JEE Main AIR 120. NIT Trichy ECE. Expert in Mathematics and problem solving.',
                'topper_specialization' => 'Mathematics',
                'topper_subjects' => ['Mathematics', 'Physics'],
            ],
            [
                'name' => 'Sneha Reddy',
                'email' => 'sneha.topper@blinkstudy.in',
                'mobile' => '9999000004',
                'password' => Hash::make('topper123'),
                'is_topper' => true,
                'is_verified_topper' => true,
                'topper_exam' => 'CBSE Board',
                'topper_rank' => 1, // State topper
                'topper_rating' => 4.8,
                'topper_total_chats' => 312,
                'topper_available' => true,
                'topper_bio' => 'CBSE Class 12 State Topper (99.6%). Now at SRCC Delhi. Expert in all subjects!',
                'topper_specialization' => 'Chemistry',
                'topper_subjects' => ['Chemistry', 'Physics', 'Mathematics', 'English'],
            ],
            [
                'name' => 'Karthik Iyer',
                'email' => 'karthik.topper@blinkstudy.in',
                'mobile' => '9999000005',
                'password' => Hash::make('topper123'),
                'is_topper' => true,
                'is_verified_topper' => true,
                'topper_exam' => 'JEE Advanced',
                'topper_rank' => 89,
                'topper_rating' => 4.85,
                'topper_total_chats' => 178,
                'topper_available' => true,
                'topper_bio' => 'JEE Advanced AIR 89. IIT Delhi EE. Chemistry olympiad medalist!',
                'topper_specialization' => 'Chemistry',
                'topper_subjects' => ['Chemistry', 'Physics', 'Mathematics'],
            ],
        ];

        foreach ($toppers as $topperData) {
            $user = User::updateOrCreate(
                ['email' => $topperData['email']],
                array_merge($topperData, [
                    'role' => 'user',
                    'is_active' => true,
                    'mobile_verified_at' => now(),
                    'email_verified_at' => now(),
                    'topper_last_active' => now(),
                ])
            );

            // Create topper wallet
            TopperWallet::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'balance' => rand(500, 2000),
                    'total_earned' => rand(5000, 15000),
                    'total_withdrawn' => rand(3000, 10000),
                    'upi_id' => strtolower(str_replace(' ', '', $topperData['name'])) . '@upi',
                ]
            );
        }

        $this->command->info('Created ' . count($toppers) . ' sample toppers with wallets');
    }
}
