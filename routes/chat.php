<?php

use App\Http\Controllers\ChatSubdomainController;
use Illuminate\Support\Facades\Route;

$chatDomain = env('CHAT_SUBDOMAIN', 'chat.' . env('MAIN_DOMAIN', 'localhost'));

Route::domain($chatDomain)
    ->middleware(['web', 'ip.whitelist'])
    ->group(function () {
        Route::get('/', [ChatSubdomainController::class, 'handleRoot'])->name('chat.index');

        require __DIR__ . '/includes/chat_application.php';
    });
