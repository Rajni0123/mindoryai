<?php

use App\Http\Controllers\AiController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImageAnalysisController;
use App\Http\Controllers\ProfileCompletionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::post('/profile/complete', [ProfileCompletionController::class, 'store']);

    Route::get('/chat', [HomeController::class, 'index'])
        ->middleware('subscription.active')
        ->name('chat');

    Route::prefix('api/chat')->group(function () {
        Route::get('/history', [\App\Http\Controllers\Api\ChatController::class, 'history']);
        Route::get('/{id}', [\App\Http\Controllers\Api\ChatController::class, 'show']);
        Route::post('/send', [\App\Http\Controllers\Api\ChatController::class, 'send']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\ChatController::class, 'destroy']);
        Route::put('/{id}', [\App\Http\Controllers\Api\ChatController::class, 'update']);
    });

    Route::post('/analyze-image', [ImageAnalysisController::class, 'analyze'])
        ->middleware(['throttle:ai', 'throttle:upload', 'ai.access'])
        ->name('analyze.image');

    Route::post('/analyze-pdf', [ImageAnalysisController::class, 'analyzePdf'])
        ->middleware(['throttle:ai', 'throttle:upload', 'ai.access'])
        ->name('analyze.pdf');

    Route::post('/ask-question', [ImageAnalysisController::class, 'askQuestion'])
        ->middleware(['throttle:ai', 'ai.access'])
        ->name('ask.question');

    Route::post('/chat/stream', [ImageAnalysisController::class, 'stream'])
        ->middleware(['throttle:ai', 'ai.access'])
        ->name('chat.stream');

    Route::post('/ask-ai', [AiController::class, 'chat']);

    Route::post('/chat/clear', [ImageAnalysisController::class, 'clearChat'])
        ->name('chat.clear');

    Route::prefix('conversations')->name('conversations.')->group(function () {
        Route::get('/active', [\App\Http\Controllers\ConversationController::class, 'getOrCreateConversation'])
            ->name('active');
        Route::post('/message', [\App\Http\Controllers\ConversationController::class, 'sendMessage'])
            ->middleware(['throttle:ai', 'throttle:upload', 'ai.access'])
            ->name('message');
        Route::post('/new', [\App\Http\Controllers\ConversationController::class, 'createNewConversation'])
            ->name('new');
        Route::get('/list', [\App\Http\Controllers\ConversationController::class, 'getUserConversations'])
            ->name('list');
        Route::get('/{conversation}', [\App\Http\Controllers\ConversationController::class, 'loadConversation'])
            ->name('load');
        Route::delete('/{conversation}', [\App\Http\Controllers\ConversationController::class, 'deleteConversation'])
            ->name('delete');
    });

    Route::post('/enhance-prompt', [ImageAnalysisController::class, 'enhancePrompt'])
        ->middleware(['throttle:20,1'])
        ->name('enhance.prompt');
});
