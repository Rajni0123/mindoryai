<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MobileChat as Chat;
use App\Models\MobileChatMessage as ChatMessage;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\AiModel;
use App\Services\UnifiedAIService;
use App\Services\FileStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    protected $aiService;
    protected $fileStorageService;

    public function __construct(UnifiedAIService $aiService, FileStorageService $fileStorageService)
    {
        $this->aiService = $aiService;
        $this->fileStorageService = $fileStorageService;
    }

    /**
     * Get user's chat history
     */
    public function history()
    {
        $chats = Chat::where('user_id', Auth::id())
            ->orderBy('updated_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($chat) {
                return [
                    'id' => $chat->id,
                    'title' => $chat->title ?? 'New Chat',
                    'created_at' => $chat->created_at,
                    'updated_at' => $chat->updated_at,
                ];
            });

        return response()->json([
            'success' => true,
            'chats' => $chats
        ]);
    }

    /**
     * Get specific chat with messages
     */
    public function show($id)
    {
        $chat = Chat::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$chat) {
            return response()->json([
                'success' => false,
                'message' => 'Chat not found'
            ], 404);
        }

        $messages = ChatMessage::where('mobile_chat_id', $id)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'role' => $message->sender,
                    'content' => $message->content,
                    'created_at' => $message->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'chat' => [
                'id' => $chat->id,
                'title' => $chat->title,
                'created_at' => $chat->created_at,
            ],
            'messages' => $messages
        ]);
    }

    /**
     * Send message and get AI response
     */
    public function send(Request $request)
    {
        \Log::info('ChatController::send - Request received', [
            'has_file' => $request->hasFile('file'),
            'all_input' => $request->all(),
            'files' => $request->allFiles(),
        ]);

        $validated = $request->validate([
            'message' => 'nullable|string|max:10000',
            'chat_id' => 'nullable|integer|exists:mobile_chats,id',
            'model_id' => 'nullable|integer|exists:ai_models,id',
            'file' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:25600' // Max 25MB (matching php.ini)
        ]);

        try {
            $user = Auth::user();
            $message = $validated['message'] ?? '';
            $chatId = $validated['chat_id'] ?? null;
            $modelId = $validated['model_id'] ?? null;
            $hasImage = $request->hasFile('file');

            if ($hasImage) {
                \Log::info('File detected in request', [
                    'file' => $request->file('file'),
                    'original_name' => $request->file('file')?->getClientOriginalName(),
                    'size' => $request->file('file')?->getSize(),
                    'mime_type' => $request->file('file')?->getMimeType(),
                    'valid' => $request->file('file')?->isValid(),
                    'error' => $request->file('file')?->getError(),
                ]);
            }

            // Get or create AI model
            if (!$modelId) {
                $aiModel = AiModel::where('is_active', true)->first();
                $modelId = $aiModel ? $aiModel->id : null;
            }

            // Handle image upload - cloud storage with Base64 fallback
            $imageData = null;
            $fileUrl = null;
            if ($hasImage) {
                $file = $request->file('file');

                if ($this->fileStorageService->isActive()) {
                    try {
                        $uploadResult = $this->fileStorageService->upload($file, 'chat-uploads');
                        if ($uploadResult) {
                            $fileUrl = $uploadResult['file_url'];
                            $imageData = [
                                'uri' => $fileUrl,
                                'type' => $file->getMimeType(),
                                'fileName' => $file->getClientOriginalName(),
                            ];
                        }
                    } catch (\Exception $e) {
                        \Log::warning('Cloud upload failed in ChatController, falling back to Base64: ' . $e->getMessage());
                    }
                }

                // Fallback to Base64 if cloud upload failed or not active
                if (!$imageData) {
                    $imageBase64 = base64_encode(file_get_contents($file->getRealPath()));
                    $imageData = [
                        'uri' => 'data:image/jpeg;base64,' . $imageBase64,
                        'type' => $file->getMimeType(),
                        'fileName' => $file->getClientOriginalName(),
                    ];
                }
            }

            // Set default message if only image provided
            if (empty($message) && $hasImage) {
                $message = 'Analyze this image';
            }

            // Require either message or image
            if (empty($message) && !$hasImage) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please provide a message or attach an image.'
                ], 400);
            }

            // Create or get chat
            if (!$chatId) {
                $chat = Chat::create([
                    'user_id' => $user->id,
                    'title' => $this->generateChatTitle($message),
                    'ai_model_id' => $modelId,
                ]);
                $chatId = $chat->id;
            } else {
                $chat = Chat::where('id', $chatId)
                    ->where('user_id', $user->id)
                    ->first();

                if (!$chat) {
                    throw new \Exception('Chat not found');
                }
            }

            // Save user message
            ChatMessage::create([
                'mobile_chat_id' => $chatId,
                'sender' => 'user',
                'content' => $message,
                'image' => $imageData,
            ]);

            // Get AI response
            $aiResponse = $this->getAIResponse($user, $message, $chatId, $modelId, $imageData);

            if (!$aiResponse) {
                throw new \Exception('Failed to get AI response');
            }

            // Save AI response
            ChatMessage::create([
                'mobile_chat_id' => $chatId,
                'sender' => 'assistant',
                'content' => $aiResponse,
            ]);

            // Update chat timestamp
            $chat->touch();

            return response()->json([
                'success' => true,
                'response' => $aiResponse,
                'chat_id' => $chatId,
                'message' => 'Message sent successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Chat send error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'message' => $request->message,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send message: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate chat title from first message
     */
    private function generateChatTitle($message)
    {
        // Take first 50 characters as title
        $title = strlen($message) > 50 ? substr($message, 0, 50) . '...' : $message;
        return $title;
    }

    /**
     * Get AI response using UnifiedAIService
     */
    private function getAIResponse($user, $message, $chatId, $modelId, $imageData = null)
    {
        try {
            // Get previous messages for context (excluding the current message)
            $previousMessages = ChatMessage::where('mobile_chat_id', $chatId)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->reverse()
                ->map(function ($msg) {
                    return [
                        'role' => $msg->sender,
                        'content' => $msg->content
                    ];
                })
                ->toArray();

            // Remove the last message (current user message) from previous messages if it exists
            if (!empty($previousMessages) && end($previousMessages)['content'] === $message) {
                array_pop($previousMessages);
            }

            // Call AI service with correct parameters
            $response = $this->aiService->chat(
                message: $message,
                modelId: $modelId,
                conversationHistory: $previousMessages,
                streamCallback: null,
                imageData: $imageData,
                feature: 'chat',
                userId: $user->id
            );

            // Extract the response text
            if (isset($response['success']) && $response['success'] === false) {
                \Log::error('AI Service error: ' . ($response['error'] ?? 'Unknown error'));
                return 'I apologize, but I encountered an error: ' . ($response['error'] ?? 'Unknown error');
            }

            if (is_array($response) && isset($response['content'])) {
                return $response['content'];
            } elseif (is_string($response)) {
                return $response;
            }

            \Log::error('Unexpected AI response format', ['response' => $response]);
            return 'I apologize, but I couldn\'t generate a response. Please try again.';

        } catch (\Exception $e) {
            \Log::error('AI Service error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return 'I apologize, but I encountered an error processing your request. Please try again.';
        }
    }

    /**
     * Delete a chat
     */
    public function destroy($id)
    {
        $chat = Chat::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$chat) {
            return response()->json([
                'success' => false,
                'message' => 'Chat not found'
            ], 404);
        }

        // Delete chat messages
        ChatMessage::where('mobile_chat_id', $id)->delete();

        // Delete chat
        $chat->delete();

        return response()->json([
            'success' => true,
            'message' => 'Chat deleted successfully'
        ]);
    }

    /**
     * Update chat title
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255'
        ]);

        $chat = Chat::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$chat) {
            return response()->json([
                'success' => false,
                'message' => 'Chat not found'
            ], 404);
        }

        $chat->title = $validated['title'];
        $chat->save();

        return response()->json([
            'success' => true,
            'message' => 'Chat updated successfully'
        ]);
    }
}
