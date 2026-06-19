<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OpenAI;

class AiController extends Controller
{
    public function chat(Request $request)
    {
        try {
            $client = OpenAI::client(config('services.openai.api_key'));

            $response = $client->chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'user', 'content' => $request->message]
                ],
            ]);

            return response()->json([
                'reply' => $response['choices'][0]['message']['content']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
