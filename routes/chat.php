<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NotebookController;

// Chat subdomain - redirects to main app chat functionality
Route::domain(env('CHAT_SUBDOMAIN', 'chat.' . env('MAIN_DOMAIN', 'localhost')))
    ->group(function () {
        // Redirect to main app chat interface
        Route::get('/', function () {
            return app(\App\Http\Controllers\ChatSubdomainController::class)->handleRoot(request());
        })->name('chat.index');

        // Proxy other routes to main app
        Route::get('/{path}', function ($path) {
            return redirect()->to(config('app.url') . '/' . $path);
        });

        Route::post('/{path}', function ($path) {
            // For POST requests, we need to handle them differently
            // This will redirect back to the main domain
            return redirect()->to(config('app.url') . '/' . $path);
        });
    });
