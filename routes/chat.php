<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NotebookController;

// Chat subdomain - redirects to main app chat functionality
Route::domain(env('CHAT_SUBDOMAIN', 'chat.mindory.local'))
    ->group(function () {
        // Redirect to main app chat interface
        Route::get('/', function () {
            return redirect()->to('http://mindory.local:8000/notebooks/chat');
        })->name('chat.index');

        // Proxy other routes to main app
        Route::get('/{path}', function ($path) {
            return redirect()->to('http://mindory.local:8000/' . $path);
        });

        Route::post('/{path}', function ($path) {
            // For POST requests, we need to handle them differently
            // This will redirect back to the main domain
            return redirect()->to('http://mindory.local:8000/' . $path);
        });
    });
