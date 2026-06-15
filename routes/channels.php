<?php

use App\Models\ChatConversation;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// User's personal channel for notifications
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private chat conversation channel
Broadcast::channel('chat.conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = ChatConversation::find($conversationId);

    if (!$conversation) {
        return false;
    }

    // Only student and topper in this conversation can listen
    return $user->id === $conversation->student_id || $user->id === $conversation->topper_id;
});

// Topper's inbox channel for new requests
Broadcast::channel('topper.inbox.{topperId}', function ($user, $topperId) {
    // Only the topper can listen to their inbox
    return (int) $user->id === (int) $topperId && $user->is_verified_topper;
});

// Presence channel for online status
Broadcast::channel('topper.presence', function ($user) {
    if ($user->is_verified_topper) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'rating' => $user->topper_rating,
        ];
    }
    return false;
});

// Student's channel for request updates
Broadcast::channel('student.requests.{studentId}', function ($user, $studentId) {
    return (int) $user->id === (int) $studentId;
});
