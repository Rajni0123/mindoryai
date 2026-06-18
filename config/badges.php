<?php

/**
 * BlinkStudy Badge System — matches assets/logo/batches.jpeg tiers.
 */
return [
    'league' => [
        ['id' => 'league_rookie', 'name' => 'ROOKIE', 'tagline' => 'Just Started', 'icon' => 'book_open', 'gradient' => ['#3B82F6', '#2563EB'], 'border' => '#60A5FA', 'min' => 1, 'max' => 2],
        ['id' => 'league_bronze', 'name' => 'BRONZE', 'tagline' => 'Keep Learning', 'icon' => 'shield', 'gradient' => ['#CD7F32', '#8B4513'], 'border' => '#D4A574', 'min' => 3, 'max' => 5],
        ['id' => 'league_silver', 'name' => 'SILVER', 'tagline' => 'Getting Better', 'icon' => 'shield', 'gradient' => ['#C0C0C0', '#808080'], 'border' => '#E5E7EB', 'min' => 6, 'max' => 9],
        ['id' => 'league_gold', 'name' => 'GOLD', 'tagline' => 'On Fire', 'icon' => 'shield', 'gradient' => ['#FFD700', '#D97706'], 'border' => '#FBBF24', 'min' => 10, 'max' => 14],
        ['id' => 'league_platinum', 'name' => 'PLATINUM', 'tagline' => 'Great Progress', 'icon' => 'gem', 'gradient' => ['#14B8A6', '#0D9488'], 'border' => '#5EEAD4', 'min' => 15, 'max' => 19],
        ['id' => 'league_diamond', 'name' => 'DIAMOND', 'tagline' => 'Excellent', 'icon' => 'diamond', 'gradient' => ['#38BDF8', '#0284C7'], 'border' => '#7DD3FC', 'min' => 20, 'max' => 29],
        ['id' => 'league_master', 'name' => 'MASTER', 'tagline' => 'Almost There', 'icon' => 'crown', 'gradient' => ['#8B5CF6', '#6D28D9'], 'border' => '#A78BFA', 'min' => 30, 'max' => 39],
        ['id' => 'league_grandmaster', 'name' => 'GRANDMASTER', 'tagline' => 'Elite Learner', 'icon' => 'crown', 'gradient' => ['#7C3AED', '#4C1D95'], 'border' => '#C4B5FD', 'min' => 40, 'max' => 49],
        ['id' => 'league_legend', 'name' => 'LEGEND', 'tagline' => 'The Best', 'icon' => 'sparkles', 'gradient' => ['#F59E0B', '#7C3AED'], 'border' => '#FBBF24', 'min' => 50, 'max' => null],
    ],

    'streak' => [
        ['id' => 'streak_3', 'name' => '3 DAYS', 'tagline' => 'Getting Started', 'icon' => 'fire', 'gradient' => ['#F97316', '#EA580C'], 'border' => '#FDBA74', 'min' => 3, 'max' => 6],
        ['id' => 'streak_7', 'name' => '7 DAYS', 'tagline' => 'Keep it Up', 'icon' => 'fire', 'gradient' => ['#FB923C', '#DC2626'], 'border' => '#F97316', 'min' => 7, 'max' => 14],
        ['id' => 'streak_15', 'name' => '15 DAYS', 'tagline' => 'Getting Stronger', 'icon' => 'bolt', 'gradient' => ['#FBBF24', '#F97316'], 'border' => '#FDE047', 'min' => 15, 'max' => 29],
        ['id' => 'streak_30', 'name' => '30 DAYS', 'tagline' => 'On a Roll', 'icon' => 'fire', 'gradient' => ['#FBBF24', '#D97706'], 'border' => '#FCD34D', 'min' => 30, 'max' => 59],
        ['id' => 'streak_60', 'name' => '60 DAYS', 'tagline' => 'Unstoppable', 'icon' => 'fire', 'gradient' => ['#3B82F6', '#1D4ED8'], 'border' => '#60A5FA', 'min' => 60, 'max' => 99],
        ['id' => 'streak_100', 'name' => '100 DAYS', 'tagline' => 'Dedication', 'icon' => 'fire', 'gradient' => ['#8B5CF6', '#6D28D9'], 'border' => '#A78BFA', 'min' => 100, 'max' => 364],
        ['id' => 'streak_365', 'name' => '365 DAYS', 'tagline' => 'Legendary', 'icon' => 'trophy', 'gradient' => ['#FBBF24', '#7C3AED'], 'border' => '#FDE047', 'min' => 365, 'max' => null],
    ],

    'battle' => [
        ['id' => 'battle_rookie', 'name' => 'BATTLE ROOKIE', 'tagline' => 'Enter the Arena', 'icon' => 'swords', 'gradient' => ['#78716C', '#57534E'], 'border' => '#A8A29E', 'min' => 0, 'max' => 4],
        ['id' => 'battle_champion', 'name' => 'CHAMPION', 'tagline' => 'Skilled Warrior', 'icon' => 'swords', 'gradient' => ['#3B82F6', '#1E40AF'], 'border' => '#60A5FA', 'min' => 5, 'max' => 14],
        ['id' => 'battle_dominator', 'name' => 'DOMINATOR', 'tagline' => 'Unbeatable', 'icon' => 'swords', 'gradient' => ['#8B5CF6', '#5B21B6'], 'border' => '#A78BFA', 'min' => 15, 'max' => 29],
        ['id' => 'battle_arena_king', 'name' => 'ARENA KING', 'tagline' => 'Fear the Name', 'icon' => 'crown', 'gradient' => ['#FBBF24', '#7C3AED'], 'border' => '#FDE047', 'min' => 30, 'max' => null],
    ],

    'topper' => [
        ['id' => 'topper_50', 'name' => 'TOP 50%', 'tagline' => 'Above Average', 'icon' => 'star', 'gradient' => ['#22C55E', '#15803D'], 'border' => '#86EFAC', 'min' => 50, 'max' => 74],
        ['id' => 'topper_25', 'name' => 'TOP 25%', 'tagline' => 'Well Above', 'icon' => 'star', 'gradient' => ['#3B82F6', '#1D4ED8'], 'border' => '#93C5FD', 'min' => 75, 'max' => 89],
        ['id' => 'topper_10', 'name' => 'TOP 10%', 'tagline' => 'In the Top 10%', 'icon' => 'star', 'gradient' => ['#FBBF24', '#D97706'], 'border' => '#FDE047', 'min' => 90, 'max' => 94],
        ['id' => 'topper_5', 'name' => 'TOP 5%', 'tagline' => 'Top 5% Student', 'icon' => 'star', 'gradient' => ['#8B5CF6', '#6D28D9'], 'border' => '#C4B5FD', 'min' => 95, 'max' => 98],
        ['id' => 'topper_1', 'name' => 'TOP 1%', 'tagline' => 'Among the Best', 'icon' => 'crown', 'gradient' => ['#FBBF24', '#DC2626'], 'border' => '#FDE047', 'min' => 99, 'max' => 99],
        ['id' => 'topper_national', 'name' => 'NATIONAL TOPPER', 'tagline' => 'The One!', 'icon' => 'trophy', 'gradient' => ['#FBBF24', '#7C3AED'], 'border' => '#FDE047', 'min' => 100, 'max' => null],
    ],

    'achievement' => [
        ['id' => 'first_quiz', 'name' => 'FIRST QUIZ', 'tagline' => 'Take your first quiz', 'icon' => 'star', 'gradient' => ['#8B5CF6', '#6D28D9'], 'border' => '#A78BFA'],
        ['id' => 'quiz_master', 'name' => 'QUIZ MASTER', 'tagline' => 'Quiz Champion', 'icon' => 'trophy', 'gradient' => ['#FBBF24', '#D97706'], 'border' => '#FDE047'],
        ['id' => 'revision_hero', 'name' => 'REVISION HERO', 'tagline' => 'Revise and Win', 'icon' => 'book_open', 'gradient' => ['#8B5CF6', '#6D28D9'], 'border' => '#A78BFA'],
        ['id' => 'speed_solver', 'name' => 'SPEED SOLVER', 'tagline' => 'Quick Thinker', 'icon' => 'bolt', 'gradient' => ['#3B82F6', '#1D4ED8'], 'border' => '#93C5FD'],
        ['id' => 'accuracy_king', 'name' => 'ACCURACY KING', 'tagline' => 'Perfect Aim', 'icon' => 'target', 'gradient' => ['#EF4444', '#B91C1C'], 'border' => '#FCA5A5'],
        ['id' => 'consistency_beast', 'name' => 'CONSISTENCY BEAST', 'tagline' => 'Never Stops', 'icon' => 'fire', 'gradient' => ['#F97316', '#EA580C'], 'border' => '#FDBA74'],
        ['id' => 'top_performer', 'name' => 'TOP PERFORMER', 'tagline' => 'Top of the Class', 'icon' => 'crown', 'gradient' => ['#FBBF24', '#7C3AED'], 'border' => '#FDE047'],
    ],

    'special' => [
        ['id' => 'daily_winner', 'name' => 'DAILY WINNER', 'tagline' => 'Daily Challenge', 'icon' => 'calendar_check', 'gradient' => ['#22C55E', '#15803D'], 'border' => '#86EFAC'],
        ['id' => 'battle_champion_special', 'name' => 'BATTLE CHAMPION', 'tagline' => 'Win Battles', 'icon' => 'swords', 'gradient' => ['#3B82F6', '#1E40AF'], 'border' => '#93C5FD'],
        ['id' => 'speed_king', 'name' => 'SPEED KING', 'tagline' => 'Fast & Fearless', 'icon' => 'timer', 'gradient' => ['#3B82F6', '#1D4ED8'], 'border' => '#93C5FD'],
        ['id' => 'perfect_score', 'name' => 'PERFECT SCORE', 'tagline' => '100% Accuracy', 'icon' => 'sparkles', 'gradient' => ['#8B5CF6', '#5B21B6'], 'border' => '#C4B5FD'],
    ],
];
