<?php

use App\Http\Controllers\ChatSubdomainController;
use Illuminate\Support\Facades\Route;

$chatDomain = config('domains.chat', 'chat.' . config('domains.main', 'localhost'));

Route::domain($chatDomain)
    ->middleware(['web', 'ip.whitelist'])
    ->group(function () {
        Route::get('/', [ChatSubdomainController::class, 'handleRoot'])->name('chat.index');

        require __DIR__ . '/includes/chat_application.php';
    });
