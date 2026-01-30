<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OpenAI;

class AiController extends Controller
{
    public function chat(Request $request)
    {
        try {
            $client = OpenAI::client(env('OPENAI_API_KEY'));

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
